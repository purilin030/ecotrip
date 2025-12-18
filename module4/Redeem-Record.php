<?php
session_start();
require '../database.php';

// --- 1. 權限與安全檢查 ---
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
    $auth_res = mysqli_query($con, $auth_sql);
    
    if ($auth_row = mysqli_fetch_assoc($auth_res)) {
        if ($auth_row['Role'] == 1) {
            header("Location: /ecotrip/module4/Redemption_List.php");
            exit();
        }
    }
}

if(!isset($_SESSION['user_id'])){
    echo "<script>window.location.href='../module1/login.php';</script>"; 
    exit;
}
$user_id = $_SESSION['user_id'];

// --- 2. 接收過濾參數 (新增日期參數) ---
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : ''; // 🌟 新增
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';     // 🌟 新增

// --- 3. 構建動態 SQL ---
$sql = "SELECT r.*, rw.Reward_Photo, rw.Type
        FROM redeemrecord r 
        LEFT JOIN reward rw ON r.Reward_ID = rw.Reward_ID 
        WHERE r.Redeem_By = ?";

$params = [$user_id];

// 狀態篩選
if ($filter_status !== 'all') {
    $sql .= " AND r.Status = ?";
    $params[] = $filter_status;
}

// 關鍵字搜尋
if (!empty($search_query)) {
    $sql .= " AND r.Reward_Name LIKE ?";
    $params[] = "%" . $search_query . "%";
}

// 🌟 新增：時間區間篩選邏輯
if (!empty($from_date)) {
    $sql .= " AND r.Redeem_Date >= ?";
    $params[] = $from_date . " 00:00:00";
}
if (!empty($to_date)) {
    $sql .= " AND r.Redeem_Date <= ?";
    $params[] = $to_date . " 23:59:59";
}

$sql .= " ORDER BY r.Redeem_Date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

require '../header.php';
require '../background.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Redemption History</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans flex flex-col min-h-screen">
    
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="flex flex-col gap-6 mb-8">
            <div class="flex justify-between items-center">
                <a href="Marketplace.php" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-green-600 transition group">
                    <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center mr-2 shadow-sm group-hover:border-green-300">
                        <i class="fa-solid fa-arrow-left"></i>
                    </div>
                    Back to Shop
                </a>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight hidden md:block">Redemption History</h1>
            </div>

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-wrap gap-4 items-end">
                <div class="flex-grow min-w-[200px]">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Search</label>
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Reward name..." 
                               class="pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none w-full bg-gray-50">
                    </div>
                </div>

                <div class="w-full md:w-40">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Status</label>
                    <select name="status" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm bg-gray-50 cursor-pointer outline-none focus:ring-2 focus:ring-green-500">
                        <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Delivered" <?= $filter_status == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    </select>
                </div>

                <div class="w-full md:w-40">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">From Date</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" 
                           class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="w-full md:w-40">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">To Date</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" 
                           class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="flex-grow md:flex-none bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-green-700 transition">
                        Filter
                    </button>
                    <a href="Redeem-Record.php" class="bg-gray-100 text-gray-500 px-5 py-2 rounded-lg text-sm font-bold hover:bg-gray-200 transition text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="max-w-4xl mx-auto">
            <?php if (count($rows) === 0): ?>
                <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                    <div class="bg-green-50 w-24 h-24 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-calendar-xmark text-green-300 text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">No redemptions found</h3>
                    <p class="text-gray-500 text-sm">Try adjusting your filters or date range.</p>
                </div>
            <?php else: ?>
                <div class="space-y-5">
                    <?php foreach($rows as $row): ?>
                        <?php 
                            $status = $row['Status']; 
                            if ($status == 'Delivered') {
                                $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200"><i class="fa-solid fa-check mr-1.5"></i> Delivered</span>';
                                $cardClass = 'border-l-4 border-l-green-500'; 
                            } else {
                                $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fa-solid fa-spinner fa-spin-pulse mr-1.5"></i> Pending</span>';
                                $cardClass = 'border-l-4 border-l-yellow-400'; 
                            }
                            $imgSrc = !empty($row['Reward_Photo']) ? $row['Reward_Photo'] : "https://placehold.co/100x100?text=No+Img";
                        ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-300 <?= $cardClass ?>">
                            <div class="p-6">
                                <div class="flex gap-5 items-start">
                                    <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-100 shadow-sm">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($row['Reward_Name']); ?></h3>
                                                <p class="text-xs text-gray-400 mt-1"><?= date("M d, Y h:i A", strtotime($row['Redeem_Date'])); ?></p>
                                            </div>
                                            <?= $statusBadge ?>
                                        </div>
                                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-50">
                                            <span class="text-sm font-medium text-gray-600">Quantity: <b>x<?= $row['Redeem_Quantity'] ?></b></span>
                                            <div class="flex gap-2">
                                                <?php if($status == 'Delivered' && $row['Type'] == 'Virtual'): ?>
                                                    <a href="voucher.php?id=<?= $row['RedeemRecord_ID'] ?>" target="_blank" class="px-3 py-1.5 rounded text-xs font-bold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">Print Voucher</a>
                                                <?php elseif($status == 'Delivered' && $row['Type'] == 'Physical' && !empty($row['Proof_Photo'])): ?>
                                                    <button onclick="showProof('<?= $row['Proof_Photo'] ?>')" class="px-3 py-1.5 rounded text-xs font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">View Proof</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // JS 函數保持不變 (showProof, copyText, cancelOrder)
        function showProof(imageUrl) {
            Swal.fire({ title: 'Delivery Proof', imageUrl: imageUrl, imageHeight: 400, confirmButtonText: 'Close', confirmButtonColor: '#333' });
        }
    </script>
    
    <?php include '../footer.php'; ?>
</body>
</html>