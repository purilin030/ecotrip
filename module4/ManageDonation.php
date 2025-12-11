<?php
session_start();
require '../database.php';
require '../background.php'; // 你的背景特效

// --- 1. 后端处理逻辑 ---

// A. 添加新活动 (Add Campaign)
if (isset($_POST['add_campaign'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $target = $_POST['target_points'];
    
    // 图片上传逻辑
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_donation_" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $targetDir . $fileName;
        }
    }

    $sql = "INSERT INTO donation_campaign (Title, Description, Target_Points, Current_Points, Image, Status) VALUES (?, ?, ?, 0, ?, 'Active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $desc, $target, $imagePath]);
    
    header("Location: manage_donation.php?msg=added"); exit;
}

// B. 编辑活动 (Edit Campaign - Update Photo & State)
if (isset($_POST['edit_campaign'])) {
    $id = $_POST['edit_id'];
    $title = $_POST['edit_title'];
    $desc = $_POST['edit_desc'];
    $target = $_POST['edit_target'];
    $status = $_POST['edit_status'];
    
    // 动态构建 SQL
    $sql = "UPDATE donation_campaign SET Title=?, Description=?, Target_Points=?, Status=?";
    $params = [$title, $desc, $target, $status];

    // 如果上传了新图片，则更新图片字段
    if (!empty($_FILES['edit_image']['name'])) {
        $targetDir = "uploads/";
        $fileName = time() . "_donation_" . basename($_FILES['edit_image']['name']);
        if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $targetDir . $fileName)) {
            $sql .= ", Image=?";
            $params[] = $targetDir . $fileName;
        }
    }

    $sql .= " WHERE Campaign_ID=?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header("Location: manage_donation.php?msg=updated"); exit;
}

// C. 删除活动 (可选)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // 简单删除，实际项目中可能需要检查是否有捐赠记录
    $pdo->prepare("DELETE FROM donation_campaign WHERE Campaign_ID = ?")->execute([$id]);
    header("Location: manage_donation.php?msg=deleted"); exit;
}

// --- 2. 获取数据 ---
$campaigns = $pdo->query("SELECT * FROM donation_campaign ORDER BY Created_At DESC")->fetchAll(PDO::FETCH_ASSOC);

require '../header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Donations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-800">

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">❤️ Donation Management</h1>
        <p class="text-gray-500 text-sm mt-1">Create causes, update progress, and manage campaign statuses.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 sticky top-6">
                <h2 class="text-lg font-bold mb-5 text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs"><i class="fa-solid fa-hand-holding-heart"></i></div>
                    Create New Campaign
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="group relative w-full h-40 border-2 border-dashed border-gray-300 rounded-lg hover:bg-gray-50 hover:border-pink-400 transition flex flex-col items-center justify-center cursor-pointer overflow-hidden">
                        <img id="previewImg" class="absolute inset-0 w-full h-full object-cover hidden">
                        <div id="uploadPlaceholder" class="flex flex-col items-center">
                            <i class="fa-solid fa-image text-3xl text-gray-400 mb-2"></i>
                            <span class="text-xs text-gray-500">Upload Cover Photo</span>
                        </div>
                        <input type="file" name="image" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewFile(this)">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Campaign Title</label>
                        <input type="text" name="title" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Target Points</label>
                        <input type="number" name="target_points" required placeholder="e.g. 50000" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Description</label>
                        <textarea name="description" required rows="3" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"></textarea>
                    </div>

                    <button type="submit" name="add_campaign" class="w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-3 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-sm">
                        Launch Campaign
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <?php if (count($campaigns) == 0): ?>
                <div class="text-center py-10 text-gray-400">No campaigns found. Start by creating one!</div>
            <?php endif; ?>

            <?php foreach ($campaigns as $camp): 
                $percent = ($camp['Target_Points'] > 0) ? min(100, round(($camp['Current_Points'] / $camp['Target_Points']) * 100)) : 0;
                
                // 状态颜色逻辑
                $statusColor = 'bg-green-100 text-green-700';
                if ($camp['Status'] == 'Completed') $statusColor = 'bg-blue-100 text-blue-700';
                if ($camp['Status'] == 'Closed') $statusColor = 'bg-gray-100 text-gray-500';
            ?>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-5 items-start transition hover:shadow-md">
                
                <div class="w-full sm:w-48 h-32 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 relative">
                    <?php if($camp['Image']): ?>
                        <img src="<?= htmlspecialchars($camp['Image']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="flex items-center justify-center h-full text-gray-300"><i class="fa-solid fa-image text-2xl"></i></div>
                    <?php endif; ?>
                    <span class="absolute top-2 left-2 text-[10px] font-bold px-2 py-0.5 rounded <?= $statusColor ?>">
                        <?= $camp['Status'] ?>
                    </span>
                </div>

                <div class="flex-grow w-full">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($camp['Title']) ?></h3>
                        <div class="flex gap-2">
                            <button onclick='openEditModal(<?= json_encode($camp) ?>)' class="text-gray-400 hover:text-blue-600 transition p-1">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <a href="?delete=<?= $camp['Campaign_ID'] ?>" onclick="return confirm('Delete this campaign?')" class="text-gray-400 hover:text-red-600 transition p-1">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($camp['Description']) ?></p>

                    <div class="mt-4">
                        <div class="flex justify-between text-xs font-bold mb-1">
                            <span class="text-pink-600"><?= number_format($camp['Current_Points']) ?> raised</span>
                            <span class="text-gray-400"><?= number_format($camp['Target_Points']) ?> goal</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-pink-500 h-2 rounded-full" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // 1. 图片上传预览逻辑
    function previewFile(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // 如果是左侧添加表单
                const preview = document.getElementById('previewImg');
                const placeholder = document.getElementById('uploadPlaceholder');
                if (preview && placeholder) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                }
            }
            reader.readAsDataURL(file);
        }
    }

    // 2. 编辑弹窗逻辑 (使用 SweetAlert2)
    function openEditModal(camp) {
        Swal.fire({
            title: 'Edit Campaign',
            width: '600px',
            html: `
                <form id="editForm" method="POST" enctype="multipart/form-data" class="text-left space-y-4 mt-2">
                    <input type="hidden" name="edit_campaign" value="1">
                    <input type="hidden" name="edit_id" value="${camp.Campaign_ID}">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Status</label>
                            <select name="edit_status" class="w-full mt-1 p-2 border border-gray-300 rounded text-sm">
                                <option value="Active" ${camp.Status === 'Active' ? 'selected' : ''}>Active (Ongoing)</option>
                                <option value="Completed" ${camp.Status === 'Completed' ? 'selected' : ''}>Completed (Success)</option>
                                <option value="Closed" ${camp.Status === 'Closed' ? 'selected' : ''}>Closed (Stopped)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Target Points</label>
                            <input type="number" name="edit_target" value="${camp.Target_Points}" class="w-full mt-1 p-2 border border-gray-300 rounded text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Title</label>
                        <input type="text" name="edit_title" value="${camp.Title.replace(/"/g, '&quot;')}" class="w-full mt-1 p-2 border border-gray-300 rounded text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Description</label>
                        <textarea name="edit_desc" rows="3" class="w-full mt-1 p-2 border border-gray-300 rounded text-sm">${camp.Description}</textarea>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Update Photo (Optional)</label>
                        <input type="file" name="edit_image" accept="image/*" class="w-full mt-1 p-2 border border-gray-300 rounded text-sm bg-gray-50">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            confirmButtonColor: '#db2777', // Pink-600
            preConfirm: () => {
                document.getElementById('editForm').submit();
            }
        });
    }
</script>


<?php include '../footer.php'; ?>
</body>
</html>