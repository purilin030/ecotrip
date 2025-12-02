<?php
session_start();
require '../database.php';
require '../header.php';

// 1. 检查登录
if(!isset($_SESSION['user_id'])){
    // 如果没登录，通常是跳转回登录页，这里简单处理
    echo "Please login first.";
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. 准备 SQL (修复了语法错误)
// 我们需要按时间倒序排列 (最新的在最上面)
$sql = "SELECT * FROM redeemrecord WHERE Redeem_By = ? ORDER BY Redeem_Date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]); // 3. 传入参数
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Redemption History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { brand: { 500: '#22c55e', 600: '#16a34a', 50: '#f0fdf4' } }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <main class="max-w-4xl mx-auto px-4 py-10">
        
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Redemption History</h1>
            <p class="text-gray-500">Track all your rewards and claimed items here.</p>
        </div>

        <?php if (count($rows) === 0): ?>
            <div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
                <i class="fa-solid fa-box-open text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">No redemption records found.</p>
                <a href="Marketplace.php" class="text-brand-600 font-bold hover:underline mt-2 inline-block">Go Redeem Something!</a>
            </div>
        <?php else: ?>
            
            <div class="space-y-6">
                <?php foreach($rows as $row): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row gap-6 items-start">
                        
                        <div class="w-full md:w-48 h-32 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            <i class="fa-solid fa-gift text-brand-600 text-4xl"></i>
                        </div>

                        <div class="flex-1 w-full">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($row['Reward_Name']); ?></h3>
                                    <span class="text-xs text-gray-400">Record ID: #<?php echo $row['RedeemRecord_ID']; ?></span>
                                </div>
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full border border-green-200">
                                    Success
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-y-4 gap-x-8 text-sm">
                                
                                <div class="flex flex-col">
                                    <span class="text-gray-400 text-xs uppercase font-semibold">Redeem Date</span>
                                    <span class="font-medium text-gray-700">
                                        <?php echo date("d M Y", strtotime($row['Redeem_Date'])); ?>
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-gray-400 text-xs uppercase font-semibold">Reward ID</span>
                                    <span class="font-medium text-gray-700">
                                        ITEM-<?php echo $row['Reward_ID']; ?>
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-gray-400 text-xs uppercase font-semibold">Redeemed By</span>
                                    <span class="font-medium text-gray-700">User #<?php echo $row['Redeem_By']; ?></span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-gray-400 text-xs uppercase font-semibold">Quantity</span>
                                    <span class="font-medium text-gray-700"> <?php echo $row['Redeem_Quantity']; ?></span>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>
</body>
</html>