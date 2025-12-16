<?php
session_start();
require '../database.php';
require '../header.php';
require '../background.php';

// 1. 检查登录
if(!isset($_SESSION['user_id'])){
    echo "<script>window.location.href='../module1/login.php';</script>"; 
    exit;
}
$user_id = $_SESSION['user_id'];

// === 2. 接收筛选参数 ===
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// === 3. 构建动态 SQL ===
$sql = "SELECT r.*, rw.Reward_Photo
        FROM redeemrecord r 
        LEFT JOIN reward rw ON r.Reward_ID = rw.Reward_ID 
        WHERE r.Redeem_By = ?";

$params = [$user_id];

// 筛选状态
if ($filter_status !== 'all') {
    $sql .= " AND r.Status = ?";
    $params[] = $filter_status;
}

// 搜索关键词
if (!empty($search_query)) {
    $sql .= " AND r.Reward_Name LIKE ?";
    $params[] = "%" . $search_query . "%";
}

$sql .= " ORDER BY r.Redeem_Date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <a href="Marketplace.php" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-brand-600 transition group self-start md:self-auto">
                <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center mr-2 shadow-sm group-hover:border-green-300 group-hover:text-green-600">
                    <i class="fa-solid fa-arrow-left"></i>
                </div>
                Back to Shop
            </a>

            <form method="GET" class="flex w-full md:w-auto gap-2">
                <div class="relative flex-grow">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search item..." 
                           class="pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none w-full md:w-48">
                </div>
                <select name="status" onchange="this.form.submit()" class="py-2 px-3 border border-gray-300 rounded-lg text-sm bg-white cursor-pointer hover:border-gray-400">
                    <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Delivered" <?= $filter_status == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
            </form>
        </div>

        <div class="max-w-4xl mx-auto">
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Redemption History</h1>
                <p class="text-gray-500 mt-2">Track your rewards and deliveries.</p>
            </div>

            <?php if (count($rows) === 0): ?>
                <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                    <div class="bg-green-50 w-24 h-24 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-gift text-green-300 text-4xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">No records found</h3>
                    <p class="text-gray-500 mb-6 text-sm">
                        <?= empty($search_query) ? "You haven't redeemed any rewards yet." : "No rewards match your search." ?>
                    </p>
                    <a href="Marketplace.php" class="bg-black text-white px-6 py-2.5 rounded-lg font-bold hover:bg-gray-800 transition shadow-lg text-sm">
                        Go Redeem Something
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-5">
                    <?php foreach($rows as $row): ?>
                        <?php 
                            $status = $row['Status']; 
                            // 状态样式配置
                            if ($status == 'Delivered') {
                                $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200"><i class="fa-solid fa-check mr-1.5"></i> Delivered</span>';
                                $cardClass = 'border-l-4 border-l-green-500'; 
                            } else {
                                $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fa-solid fa-spinner fa-spin-pulse mr-1.5"></i> Pending</span>';
                                $cardClass = 'border-l-4 border-l-yellow-400'; 
                            }
                            
                            // 图片处理
                            $imgSrc = !empty($row['Reward_Photo']) ? $row['Reward_Photo'] : "https://placehold.co/100x100?text=No+Img";
                        ?>
                        
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-300 <?= $cardClass ?>">
                            <div class="p-6">
                                <div class="flex gap-5 items-start">
                                    
                                    <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-100">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-full h-full object-cover">
                                    </div>

                                    <div class="flex-grow">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($row['Reward_Name']); ?></h3>
                                                <div class="text-xs text-gray-400 mt-1 flex items-center gap-2">
                                                    <span>ID: #<?php echo $row['RedeemRecord_ID']; ?></span>
                                                    <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                                    <span><?= date("M d, Y h:i A", strtotime($row['Redeem_Date'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <?= $statusBadge ?>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-50">
                                            
                                            <div class="text-sm font-medium text-gray-600">
                                                Quantity: <span class="text-gray-900 font-bold">x<?php echo $row['Redeem_Quantity']; ?></span>
                                            </div>

                                            <div class="flex gap-2">
                                                <?php if($status == 'Pending'): ?>
                                                    <button onclick="cancelOrder(<?= $row['RedeemRecord_ID'] ?>)" 
                                                            class="px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 rounded border border-red-200 transition">
                                                        Cancel Request
                                                    </button>
                                                <?php endif; ?>

                                                <?php if($status == 'Delivered'): ?>
                                                    <a href="voucher.php?id=<?= $row['RedeemRecord_ID'] ?>" target="_blank" 
                                                       class="inline-flex items-center px-3 py-1.5 rounded text-xs font-bold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-200 transition">
                                                        <i class="fa-solid fa-print mr-1.5"></i> Print Voucher
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($row['Proof_Photo']): ?>
                                                    <button onclick="showProof('<?php echo htmlspecialchars($row['Proof_Photo']); ?>')" 
                                                            class="inline-flex items-center px-3 py-1.5 rounded text-xs font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-300 transition">
                                                        <i class="fa-solid fa-image mr-1.5"></i> Proof
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($row['Admin_Note']): ?>
                                    <div class="mt-4 bg-blue-50 rounded-lg p-3 flex items-start gap-3 text-sm border border-blue-100">
                                        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                                        <div class="flex-grow">
                                            <span class="font-bold text-blue-800">Admin Note:</span>
                                            <span class="text-blue-900 ml-1" id="note-<?= $row['RedeemRecord_ID'] ?>"><?php echo htmlspecialchars($row['Admin_Note']); ?></span>
                                        </div>
                                        <button onclick="copyText('note-<?= $row['RedeemRecord_ID'] ?>')" class="text-blue-400 hover:text-blue-600 text-xs" title="Copy">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div> 
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function showProof(imageUrl) {
            Swal.fire({
                title: 'Delivery Proof',
                imageUrl: imageUrl,
                imageAlt: 'Proof Image',
                imageHeight: 400,
                confirmButtonText: 'Close',
                confirmButtonColor: '#333'
            });
        }

        function copyText(elementId) {
            var text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({ icon: 'success', title: 'Copied to clipboard' });
            });
        }

        function cancelOrder(id) {
  Swal.fire({
    title: 'Cancel Request?',
    text: "Are you sure? Points will be refunded.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, cancel it!'
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    const fd = new FormData();
    fd.append('id', id);

    const res = await fetch('cancel_redemption.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      Swal.fire('Cancelled', data.message, 'success').then(() => location.reload());
    } else {
      Swal.fire('Error', data.message, 'error');
    }
  });
}

    </script>
    
    <?php include '../footer.php'; ?>
</body>
</html>