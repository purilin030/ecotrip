<?php
session_start();
require '../database.php';
require '../header.php';

// --- 1. 后端处理逻辑 ---

// A. 添加新商品
if (isset($_POST['add_reward'])) {
    $name = $_POST['name'];
    $points = $_POST['points'];
    $stock = $_POST['stock'];
    $desc = $_POST['desc'];
    $type = $_POST['type']; // 获取用户选择的类型 (Physical/Virtual)
    
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $targetDir . $fileName;
        }
    }

   $stmt = $pdo->prepare("INSERT INTO reward (Reward_name, Points_Required, Stock, Description, Type, Image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $points, $stock, $desc, $type, $imagePath]);
    header("Location: Inventory.php?msg=added"); exit;
}

// B. 删除商品
if (isset($_POST['delete_reward'])) {
    $stmt = $pdo->prepare("DELETE FROM reward WHERE Reward_ID = ?");
    $stmt->execute([$_POST['reward_id']]);
    header("Location: Inventory.php?msg=deleted"); exit;
}

// C. 更新库存 (快速)
if (isset($_POST['update_stock'])) {
    $stmt = $pdo->prepare("UPDATE reward SET Stock = ? WHERE Reward_ID = ?");
    $stmt->execute([$_POST['stock'], $_POST['reward_id']]);
    header("Location: Inventory.php?msg=updated"); exit;
}

// D. 编辑商品详情 (核心补充功能)
if (isset($_POST['edit_details'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $points = $_POST['edit_points'];
    $desc = $_POST['edit_desc'];

    $stmt = $pdo->prepare("UPDATE reward SET Reward_name = ?, Points_Required = ?, Description = ? WHERE Reward_ID = ?");
    $stmt->execute([$name, $points, $desc, $id]);
    header("Location: Inventory.php?msg=edited"); exit;
}

// --- 2. 数据查询 ---
$rewards = $pdo->query("SELECT * FROM reward ORDER BY Reward_ID DESC")->fetchAll(PDO::FETCH_ASSOC);
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
            <p class="text-gray-500 text-sm mt-1">Manage catalog, stock levels, and item details.</p>
        </div>
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                <i class="fa-solid fa-search"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Search items..." 
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black outline-none transition text-sm">
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-chart-bar text-indigo-600"></i> Stock Overview
        </h3>
        <div class="h-64 w-full">
            <canvas id="stockChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 sticky top-6">
                <h2 class="text-lg font-bold mb-5 text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs"><i class="fa-solid fa-plus"></i></div>
                    Add New Item
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
    
    <div class="group relative w-full h-36 border-2 border-dashed border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition flex flex-col items-center justify-center cursor-pointer">
        <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 group-hover:text-gray-600 mb-2 transition"></i>
        <span class="text-xs text-gray-500 group-hover:text-gray-700">Upload Image</span>
        <input type="file" name="image" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" 
               onchange="this.previousElementSibling.innerHTML = this.files[0].name">
    </div>

    <div>
        <label class="text-xs font-bold text-gray-500 uppercase">Item Name</label>
        <input type="text" name="name" required class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm">
    </div>

    <div>
        <label class="text-xs font-bold text-gray-500 uppercase">Reward Type</label>
        <select name="type" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black outline-none text-sm bg-white">
            <option value="Physical">📦 Physical (Requires Photo Proof)</option>
            <option value="Virtual">🎟️ Virtual (Auto-Generated Code)</option>
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
                            <th class="p-4 text-center">Stock</th>
                            <th class="p-4 text-right pr-6">Manage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($rewards as $r): ?>
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="p-4 pl-6 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex-shrink-0 overflow-hidden">
                                    <?php if($r['Image']): ?>
                                        <img src="<?= htmlspecialchars($r['Image']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 item-name"><?php echo htmlspecialchars($r['Reward_name']); ?></div>
                                    <div class="text-xs text-gray-500 truncate max-w-[150px]"><?php echo htmlspecialchars($r['Description']); ?></div>
                                </div>
                            </td>
                            
                            <td class="p-4 text-center">
                                <span class="bg-indigo-50 text-indigo-700 py-1 px-2 rounded text-xs font-bold border border-indigo-100">
                                    <?php echo $r['Points_Required']; ?> pts
                                </span>
                            </td>
                            
                            <td class="p-4 text-center">
                                <form method="POST" class="flex items-center justify-center">
                                    <input type="hidden" name="reward_id" value="<?php echo $r['Reward_ID']; ?>">
                                    <div class="flex items-center border border-gray-300 rounded-md overflow-hidden shadow-sm w-24">
                                        <input type="number" name="stock" value="<?php echo $r['Stock']; ?>" 
                                            class="w-full px-1 py-1 text-center text-sm font-bold outline-none border-none bg-white focus:bg-gray-50">
                                        <button type="submit" name="update_stock" class="bg-gray-50 hover:bg-green-100 text-gray-400 hover:text-green-600 px-2 py-1 border-l border-gray-300 transition h-full" title="Save Stock">
                                            <i class="fa-solid fa-check text-xs"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>

                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" 
                                            onclick="openEditModal(
                                                '<?= $r['Reward_ID'] ?>', 
                                                '<?= htmlspecialchars($r['Reward_name'], ENT_QUOTES) ?>', 
                                                '<?= $r['Points_Required'] ?>', 
                                                '<?= htmlspecialchars($r['Description'], ENT_QUOTES) ?>'
                                            )"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Details">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                        <input type="hidden" name="reward_id" value="<?php echo $r['Reward_ID']; ?>">
                                        <button type="submit" name="delete_reward" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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
    // 1. 搜索功能
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toUpperCase();
        let rows = document.querySelectorAll("#inventoryTable tbody tr");
        rows.forEach(row => {
            let name = row.querySelector(".item-name").textContent;
            if (name.toUpperCase().indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });

    // 2. 核心编辑功能 (SweetAlert2)
    function openEditModal(id, name, points, desc) {
        Swal.fire({
            title: 'Edit Reward Details',
            html: `
                <form id="editForm" method="POST" class="text-left space-y-3 mt-4">
                    <input type="hidden" name="edit_details" value="1">
                    <input type="hidden" name="edit_id" value="${id}">
                    
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Name</label>
                        <input name="edit_name" class="w-full p-2 border border-gray-300 rounded outline-none focus:border-black" value="${name}">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Points Required</label>
                        <input type="number" name="edit_points" class="w-full p-2 border border-gray-300 rounded outline-none focus:border-black" value="${points}">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Description</label>
                        <textarea name="edit_desc" class="w-full p-2 border border-gray-300 rounded outline-none focus:border-black" rows="3">${desc}</textarea>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            confirmButtonColor: '#000000',
            cancelButtonColor: '#d33',
            preConfirm: () => {
                document.getElementById('editForm').submit();
            }
        });
    }

    // 3. 图表配置
    const ctx = document.getElementById('stockChart');
    const labels = <?php echo json_encode(array_column($rewards, 'Reward_name')); ?>;
    const dataPoints = <?php echo json_encode(array_column($rewards, 'Stock')); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Stock Level',
                data: dataPoints,
                backgroundColor: dataPoints.map(s => s < 10 ? '#ef4444' : '#10b981'),
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                x: { grid: { display: false } } 
            }
        }
    });
</script>
</body>
</html>