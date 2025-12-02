<?php
session_start();
require '../database.php';

// 1. 处理添加新商品
if (isset($_POST['add_reward'])) {
    $name = $_POST['name'];
    $points = $_POST['points'];
    $stock = $_POST['stock'];
    $desc = $_POST['desc'];
    $type = "physical"; // 默认插入带引号的 physical

    $stmt = $pdo->prepare("INSERT INTO reward (Reward_name, Points_Required, Stock, Description, Type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $points, $stock, $desc, $type]);
    header("Location: Inventory.php"); exit;
}

// 2. 处理修改库存
if (isset($_POST['update_stock'])) {
    $id = $_POST['reward_id'];
    $new_stock = $_POST['stock'];
    $stmt = $pdo->prepare("UPDATE reward SET Stock = ? WHERE Reward_ID = ?");
    $stmt->execute([$new_stock, $id]);
    header("Location: Inventory.php"); exit;
}

// 查询所有商品
$rewards = $pdo->query("SELECT * FROM reward")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8 font-sans">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Add New Reward</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reward Name</label>
                        <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Points Cost</label>
                        <input type="number" name="points" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Initial Stock</label>
                        <input type="number" name="stock" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="desc" required class="mt-1 block w-full border border-gray-300 rounded-md p-2"></textarea>
                    </div>
                    <button type="submit" name="add_reward" class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 font-bold">
                        Add Reward
                    </button>
                </form>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 text-sm text-gray-500">Item</th>
                            <th class="p-4 text-sm text-gray-500">Points</th>
                            <th class="p-4 text-sm text-gray-500">Current Stock</th>
                            <th class="p-4 text-sm text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($rewards as $r): ?>
                        <tr>
                            <td class="p-4">
                                <div class="font-bold text-gray-800"><?php echo htmlspecialchars($r['Reward_name']); ?></div>
                                <div class="text-xs text-gray-500 truncate w-48"><?php echo htmlspecialchars($r['Description']); ?></div>
                            </td>
                            <td class="p-4 text-gray-600 font-bold"><?php echo $r['Points_Required']; ?> pts</td>
                            
                            <form method="POST">
                                <td class="p-4">
                                    <input type="number" name="stock" value="<?php echo $r['Stock']; ?>" class="w-20 border border-gray-300 rounded p-1 text-center">
                                    <input type="hidden" name="reward_id" value="<?php echo $r['Reward_ID']; ?>">
                                </td>
                                <td class="p-4">
                                    <button type="submit" name="update_stock" class="text-blue-600 hover:text-blue-800 text-sm font-bold">
                                        Update
                                    </button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>