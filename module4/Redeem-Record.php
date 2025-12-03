<?php
session_start();
require '../database.php';
require '../header.php';

// 1. 检查登录
if(!isset($_SESSION['user_id'])){
    echo "<script>window.location.href='../module1/login.php';</script>"; 
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. 查询数据
$sql = "SELECT r.*, rw.Reward_Photo
        FROM redeemrecord r 
        LEFT JOIN reward rw ON r.Reward_ID = rw.Reward_ID 
        WHERE r.Redeem_By = ? 
        ORDER BY r.Redeem_Date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
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
<body class="bg-gray-50 text-gray-800 font-sans">
    <main class="max-w-4xl mx-auto px-4 py-10">
        
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Redemption History</h1>
                <p class="text-gray-500 mt-1">Track the status of your rewards.</p>
            </div>
            <a href="Marketplace.php" class="text-sm font-bold text-brand-600 hover:text-brand-700 underline">
                &larr; Back to Shop
            </a>
        </div>

        <?php if (count($rows) === 0): ?>
            <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-basket-shopping text-gray-300 text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">No records yet</h3>
                <p class="text-gray-500 mb-6">You haven't redeemed any rewards yet.</p>
                <a href="Marketplace.php" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 transition shadow-md">
                    Go Redeem Something
                </a>
            </div>
        <?php else: ?>
            
            <div class="space-y-4">
                <?php foreach($rows as $row): ?>
                    <?php 
                        // 状态样式逻辑
                        $status = $row['Status']; 
                        if ($status == 'Delivered') {
                            $statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200"><i class="fa-solid fa-check mr-1"></i> Delivered</span>';
                            $cardBorder = 'border-gray-200'; 
                        } else {
                            $statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200"><i class="fa-solid fa-clock mr-1 animate-pulse"></i> Pending</span>';
                            $cardBorder = 'border-l-4 border-l-yellow-400 border-gray-200'; 
                        }
                    ?>

                    <div class="bg-white p-6 rounded-xl shadow-sm border <?php echo $cardBorder; ?> transition hover:shadow-md">
                        
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 line-clamp-1"><?php echo htmlspecialchars($row['Reward_Name']); ?></h3>
                                <p class="text-xs text-gray-400 mt-0.5">Order ID: #<?php echo $row['RedeemRecord_ID']; ?></p>
                            </div>
                            
                            <div class="flex flex-col items-end gap-2">
                                <?php echo $statusBadge; ?>
                                
                                <?php if($row['Status'] == 'Delivered'): ?>
                                    <a href="voucher.php?id=<?= $row['RedeemRecord_ID'] ?>" target="_blank" 
                                       class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">
                                        <i class="fa-solid fa-ticket mr-1"></i> E-Voucher
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mt-4 border-t border-gray-50 pt-4">
                            <div>
                                <span class="block text-xs text-gray-400 uppercase font-bold">Date</span>
                                <span class="font-medium"><?php echo date("M d, Y", strtotime($row['Redeem_Date'])); ?></span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-400 uppercase font-bold">Qty</span>
                                <span class="font-medium">x<?php echo $row['Redeem_Quantity']; ?></span>
                            </div>
                        </div>

                        <?php if ($row['Admin_Note'] || $row['Proof_Photo']): ?>
                            <div class="mt-4 bg-blue-50/50 rounded-lg p-3 border border-blue-100 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                                
                                <div class="text-sm text-gray-700">
                                    <?php if ($row['Admin_Note']): ?>
                                        <span class="font-bold text-blue-800"><i class="fa-solid fa-message mr-1"></i> Admin Note:</span> 
                                        <span class="font-mono bg-white px-1 rounded ml-1 select-all"><?php echo htmlspecialchars($row['Admin_Note']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($row['Proof_Photo']): ?>
                                    <button onclick="showProof('<?php echo htmlspecialchars($row['Proof_Photo']); ?>')" 
                                            class="flex-shrink-0 bg-white text-blue-600 hover:text-blue-700 border border-blue-200 px-3 py-1.5 rounded-md text-xs font-bold shadow-sm transition flex items-center gap-2">
                                        <i class="fa-solid fa-image"></i> View Proof
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div> <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function showProof(imageUrl) {
            Swal.fire({
                title: 'Proof of Delivery',
                imageUrl: imageUrl,
                imageAlt: 'Proof Image',
                imageWidth: 600,
                imageHeight: 400,
                imageClass: 'object-contain', 
                showCloseButton: true,
                showConfirmButton: false, 
                backdrop: `rgba(0,0,123,0.4)`
            });
        }
    </script>
</body>
</html>