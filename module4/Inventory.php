<?php
session_start();
require '../database.php';
// --- 1. 后端处理逻辑 (放在 Header 之前) ---

// A. 添加新商品
if (isset($_POST['add_reward'])) {
    $name = $_POST['name'];
    $points = $_POST['points'];
    $stock = $_POST['stock'];
    $desc = $_POST['desc'];
    $type = $_POST['type']; 
    
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $targetDir . $fileName;
        }
    }

    // 默认 Status 是 Active
    $stmt = $pdo->prepare("INSERT INTO reward (Reward_name, Points_Required, Stock, Description, Type, Reward_Photo, Status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
    $stmt->execute([$name, $points, $stock, $desc, $type, $imagePath]);
    header("Location: Inventory.php?msg=added"); exit;
}

// B. ❌ 删除逻辑已移除 -> ✅ 替换为：切换上下架状态 (Toggle Status)
if (isset($_POST['toggle_status'])) {
    $id = $_POST['reward_id'];
    $current_status = $_POST['current_status'];
    
    // 如果是 Active 就变 Inactive，反之亦然
    $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';

    $stmt = $pdo->prepare("UPDATE reward SET Status = ? WHERE Reward_ID = ?");
    $stmt->execute([$new_status, $id]);
    header("Location: Inventory.php?msg=status_changed"); exit;
}

// C. 更新库存
if (isset($_POST['update_stock'])) {
    $stmt = $pdo->prepare("UPDATE reward SET Stock = ? WHERE Reward_ID = ?");
    $stmt->execute([$_POST['stock'], $_POST['reward_id']]);
    header("Location: Inventory.php?msg=updated"); exit;
}

// D. 编辑商品详情
if (isset($_POST['edit_details'])) {
    $stmt = $pdo->prepare("UPDATE reward SET Reward_name = ?, Points_Required = ?, Description = ? WHERE Reward_ID = ?");
    $stmt->execute([$_POST['edit_name'], $_POST['edit_points'], $_POST['edit_desc'], $_POST['edit_id']]);
    header("Location: Inventory.php?msg=edited"); exit;
}

// --- 2. 数据查询 ---
$rewards = $pdo->query("SELECT * FROM reward ORDER BY Reward_ID DESC")->fetchAll(PDO::FETCH_ASSOC);

require '../header.php'; 
require '../background.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-800">

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Inventory Control</h1>
            <p class="text-gray-500 text-sm mt-1">Manage catalog, stock levels, and item visibility.</p>
        </div>
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa-solid fa-search"></i></span>
            <input type="text" id="searchInput" placeholder="Search items..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none transition text-sm">
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fa-solid fa-chart-bar text-indigo-600"></i> Stock Overview</h3>
        <div class="h-64 w-full"><canvas id="stockChart"></canvas></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 sticky top-6">
                <h2 class="text-lg font-bold mb-5 text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs"><i class="fa-solid fa-plus"></i></div>
                    Add New Item
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="group relative w-full h-36 border-2 border-dashed border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition flex flex-col items-center justify-center cursor-pointer overflow-hidden bg-white">
                <img id="previewImg" class="absolute inset-0 w-full h-full object-cover hidden z-0"> 
                    <div id="uploadPlaceholder" class="flex flex-col items-center z-10 pointer-events-none transition-opacity group-hover:opacity-100">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-gray-600 mb-2 transition"></i>
                    <span class="text-xs text-gray-500 group-hover:text-gray-700" id="fileNameDisplay">Upload Image</span>
                </div>

                            <input type="file" name="image" accept="image/*" 
                            class="absolute inset-0 opacity-0 cursor-pointer z-20" 
                            onchange="handleImageUpload(this)">
                </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Item Name</label>
                        <input type="text" name="name" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Reward Type</label>
                        <select name="type" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm bg-white">
                            <option value="Physical">📦 Physical (Requires Photo)</option>
                            <option value="Virtual">🎟️ Virtual (Auto-Generated)</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Points</label>
                            <input type="number" name="points" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Stock</label>
                            <input type="number" name="stock" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Description</label>
                        <textarea name="desc" rows="2" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm"></textarea>
                    </div>

                    <button type="submit" name="add_reward" class="w-full bg-black hover:bg-gray-800 text-white font-bold py-3 rounded-lg shadow-lg transition transform hover:-translate-y-0.5 text-sm">
                        Add to Inventory
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-left" id="inventoryTable">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                        <tr>
                            <th class="p-4 pl-6">Product</th>
                            <th class="p-4 text-center">Cost</th>
                            <th class="p-4 text-center">Status</th> <th class="p-4 text-right pr-6">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($rewards as $r): 
                            // 样式处理：如果 Inactive，整行半透明
                            $rowOpacity = ($r['Status'] === 'Inactive') ? 'opacity-60 bg-gray-50' : 'hover:bg-gray-50';
                        ?>
                        <tr class="<?= $rowOpacity ?> transition group">
                            <td class="p-4 pl-6 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex-shrink-0 overflow-hidden">
                                    <?php if($r['Reward_Photo']): ?>
                                        <img src="<?= htmlspecialchars($r['Reward_Photo']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 item-name"><?php echo htmlspecialchars($r['Reward_name']); ?></div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded border <?php echo strtolower($r['Type'])=='physical' ? 'bg-orange-50 text-orange-600 border-orange-200' : 'bg-blue-50 text-blue-600 border-blue-200'; ?>">
                                            <?= htmlspecialchars($r['Type']) ?>
                                        </span>
                                        <span class="text-xs text-gray-500">Stock: <b><?= $r['Stock'] ?></b></span>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="p-4 text-center">
                                <span class="bg-indigo-50 text-indigo-700 py-1 px-2 rounded text-xs font-bold border border-indigo-100">
                                    <?php echo $r['Points_Required']; ?> pts
                                </span>
                            </td>
                            
                            <td class="p-4 text-center">
                                <?php if($r['Status'] === 'Active'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-500">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    <button type="button" 
                                            onclick="openEditModal('<?= $r['Reward_ID'] ?>', '<?= htmlspecialchars($r['Reward_name'], ENT_QUOTES) ?>', '<?= $r['Points_Required'] ?>', '<?= htmlspecialchars($r['Description'], ENT_QUOTES) ?>')"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    
                                    <button type="button" onclick="quickStock(<?= $r['Reward_ID'] ?>, <?= $r['Stock'] ?>)" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition" title="Update Stock">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </button>

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="reward_id" value="<?= $r['Reward_ID'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $r['Status'] ?>">
                                        
                                        <?php if($r['Status'] === 'Active'): ?>
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Deactivate (Hide)">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="p-2 text-red-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition" title="Activate (Show)">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // 搜索
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toUpperCase();
        let rows = document.querySelectorAll("#inventoryTable tbody tr");
        rows.forEach(row => {
            let name = row.querySelector(".item-name").textContent;
            if (name.toUpperCase().indexOf(filter) > -1) row.style.display = "";
            else row.style.display = "none";
        });
    });

    // 编辑弹窗
    function openEditModal(id, name, points, desc) {
        Swal.fire({
            title: 'Edit Reward',
            html: `
                <form id="editForm" method="POST" class="text-left space-y-3 mt-4">
                    <input type="hidden" name="edit_details" value="1"><input type="hidden" name="edit_id" value="${id}">
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Name</label><input name="edit_name" class="w-full p-2 border border-gray-300 rounded" value="${name}"></div>
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Points</label><input type="number" name="edit_points" class="w-full p-2 border border-gray-300 rounded" value="${points}"></div>
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Description</label><textarea name="edit_desc" class="w-full p-2 border border-gray-300 rounded" rows="2">${desc}</textarea></div>
                </form>`,
            showCancelButton: true, confirmButtonText: 'Save', confirmButtonColor: '#000',
            preConfirm: () => document.getElementById('editForm').submit()
        });
    }

    // 快捷改库存弹窗
    function quickStock(id, current) {
        Swal.fire({
            title: 'Update Stock',
            input: 'number',
            inputValue: current,
            showCancelButton: true,
            confirmButtonText: 'Update',
            preConfirm: (val) => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="update_stock" value="1"><input type="hidden" name="reward_id" value="${id}"><input type="hidden" name="stock" value="${val}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // 图表
    const ctx = document.getElementById('stockChart');
    const labels = <?php echo json_encode(array_column($rewards, 'Reward_name')); ?>;
    const dataPoints = <?php echo json_encode(array_column($rewards, 'Stock')); ?>;
    new Chart(ctx, { type: 'bar', data: { labels: labels, datasets: [{ label: 'Stock', data: dataPoints, backgroundColor: '#10b981', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });
    
    function handleImageUpload(input) {
    const file = input.files[0];
    const preview = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    const nameDisplay = document.getElementById('fileNameDisplay');

    if (file) {
        // 1. 显示文件名
        nameDisplay.innerHTML = file.name;
        
        // 2. 读取并显示图片
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden'); // 显示图片
            
            // 可选：为了让图片看得更清楚，可以把图标隐藏，或者加个半透明背景
            // placeholder.classList.add('hidden'); 
            placeholder.classList.add('bg-white/80', 'p-2', 'rounded'); // 给文字加个背景防止看不清
        }
        reader.readAsDataURL(file);
    }
}
</script>
<?php
include '../footer.php';
?>
</body>
</html>