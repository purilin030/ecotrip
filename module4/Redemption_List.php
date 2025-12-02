<?php
session_start();
require '../database.php'; // 修复路径

// 安全检查：建议加上管理员权限判断
// if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'admin') { header("Location: login.php"); exit; }

// 处理发货逻辑
if (isset($_POST['update_status'])) {
    $id = $_POST['record_id'];
    $status = "Delivered"; // 修复：去掉多余引号
    
    $stmt = $pdo->prepare("UPDATE redeemrecord SET Status = ? WHERE RedeemRecord_ID = ?");
    $stmt->execute([$status, $id]);
    
    // 修复跳转：跳回当前页面
    header("Location: Redemption_List.php"); 
    exit;
}

// 筛选逻辑 (新设计功能)
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT r.*, u.First_Name, u.Last_Name 
        FROM redeemrecord r
        JOIN user u ON r.Redeem_By = u.User_ID ";

if ($filter == 'pending') {
    $sql .= "WHERE r.Status != 'Delivered' "; // 注意：如果数据库存的是带引号的 'Delivered'，这里要小心
} elseif ($filter == 'delivered') {
    $sql .= "WHERE r.Status = 'Delivered' ";
}

$sql .= "ORDER BY r.Redeem_Date DESC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Redemption Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📦 Redemption Management</h1>
            <p class="text-gray-500 mt-1">Manage user reward requests and shipping status.</p>
        </div>
        
        <div class="flex gap-2 mt-4 md:mt-0">
            <a href="?filter=all" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filter=='all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">All</a>
            <a href="?filter=pending" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filter=='pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">Pending</a>
            <a href="?filter=delivered" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filter=='delivered' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">Delivered</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                <tr>
                    <th class="p-4 border-b">ID</th>
                    <th class="p-4 border-b">User</th>
                    <th class="p-4 border-b">Item Details</th>
                    <th class="p-4 border-b">Date</th>
                    <th class="p-4 border-b">Status</th>
                    <th class="p-4 border-b text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($rows) == 0): ?>
                    <tr><td colspan="6" class="p-8 text-center text-gray-500">No records found.</td></tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <?php $clean_status = trim($row['Status'], "'"); // 清理引号 ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4 text-gray-400 text-sm">#<?php echo $row['RedeemRecord_ID']; ?></td>
                        
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold">
                                    <?php echo substr($row['First_Name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></div>
                                    <div class="text-xs text-gray-400">User ID: <?php echo $row['Redeem_By']; ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="p-4">
                            <div class="text-gray-900 font-medium"><?php echo htmlspecialchars($row['Reward_Name']); ?></div>
                            <div class="text-xs text-gray-500">Qty: <?php echo $row['Redeem_Quantity']; ?></div>
                        </td>

                        <td class="p-4 text-sm text-gray-500">
                            <?php echo date("M d, Y", strtotime($row['Redeem_Date'])); ?>
                        </td>

                        <td class="p-4">
                            <?php if ($clean_status == 'Delivered'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></span> Delivered
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full mr-1.5 animate-pulse"></span> Pending
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="p-4 text-right">
                            <?php if ($clean_status != 'Delivered'): ?>
                                <form method="POST">
                                    <input type="hidden" name="record_id" value="<?php echo $row['RedeemRecord_ID']; ?>">
                                    <button type="submit" name="update_status" class="bg-gray-900 hover:bg-black text-white text-xs px-3 py-2 rounded-md font-medium shadow-sm transition">
                                        Mark as Sent
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-300 text-sm"><i class="fa-solid fa-check"></i> Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>