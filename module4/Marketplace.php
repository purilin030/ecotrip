<?php
// 1. 开启 Session (如果 header.php 里没开，这里保底开一下)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 引入数据库连接 (提供 $pdo)
require '../database.php';

// 3. 设置页面标题 (header.php 会用到这个变量)
$page_title = "Rewards Marketplace";

// 4. 检查登录状态
// header.php 里可能已经检查过了，但这里为了逻辑严密，再次确认
if (!isset($_SESSION['user_id'])) {
    // 如果没登录，可以在这里跳转，或者暂时模拟一个 (测试用)
    // $_SESSION['user_id'] = 18; // 测试完记得删掉这行！
    header("Location:../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 5. 获取当前用户的积分 (专门为了 Marketplace 显示余额)
// 注意：header.php 可能只查了头像，没查 RedeemPoint，所以这里要单查一次
$stmtUser = $pdo->prepare("SELECT RedeemPoint FROM user WHERE User_ID = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

// 如果查不到用户 (比如被删了)，给个默认值防止报错
if (!$currentUser) {
    $currentUser = ['RedeemPoint' => 0];
}

// 6. 获取商品列表
$stmtRewards = $pdo->query("SELECT * FROM reward WHERE Status = 'Active'");
$rewards = $stmtRewards->fetchAll(PDO::FETCH_ASSOC);

// --- 关键点：先准备好数据，再引入 Header ---
include '../header.php'; 
?>

<main class="flex-grow max-w-7xl mx-auto px-8 py-10 w-full">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Rewards Marketplace</h1>
            <p class="mt-1 text-gray-500">Redeem your hard-earned points for eco-friendly goodies.</p>
        </div>
        
        <div class="bg-brand-100 text-brand-900 px-6 py-3 rounded-xl font-bold flex items-center shadow-sm border border-brand-200">
            <div class="p-2 bg-white rounded-full mr-3 text-brand-600">
                <i class="fa-solid fa-wallet"></i>
            </div>
            
           <a href="Redeem-Record.php" class="group block transform transition-all hover:-translate-y-1">
    
    <div class="relative overflow-hidden bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-4 rounded-2xl shadow-lg flex items-center justify-between">
        
        <div class="flex items-center">
            <div class="p-3 bg-white/20 backdrop-blur-sm rounded-full mr-4 group-hover:bg-white/30 transition-colors">
                <i class="fa-solid fa-wallet text-xl"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-emerald-100 text-xs font-bold uppercase tracking-wider">Your Balance</span>
                <span class="text-2xl font-bold font-mono"><?php echo number_format($currentUser['RedeemPoint']); ?> pts</span>
            </div>
        </div>

        <div class="text-white/50 group-hover:text-white group-hover:translate-x-1 transition-all duration-300">
            <div class="flex flex-col items-end">
                <span class="text-[10px] uppercase font-bold opacity-0 group-hover:opacity-100 transition-opacity mb-1">History</span>
                <i class="fa-solid fa-chevron-right text-xl"></i>
            </div>
        </div>

        <div class="absolute -top-6 -right-6 w-20 h-20 bg-white opacity-10 rounded-full blur-xl group-hover:opacity-20 transition-opacity"></div>
    </div>
</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($rewards as $reward): ?>
            <?php 
                // 逻辑判断
                $isOutOfStock = $reward['Stock'] <= 0;
                $notEnoughPoints = $currentUser['RedeemPoint'] < $reward['Points_Required'];
                $isDisabled = $isOutOfStock || $notEnoughPoints;
                
                // 按钮样式
                if ($isOutOfStock) {
                    $btnClass = "bg-gray-300 text-gray-500 cursor-not-allowed";
                    $btnText = "Out of Stock";
                } elseif ($notEnoughPoints) {
                    $btnClass = "bg-gray-200 text-gray-400 cursor-not-allowed";
                    // 计算还差多少分
                    $diff = $reward['Points_Required'] - $currentUser['RedeemPoint'];
                    $btnText = "Need {$diff} more";
                } else {
                    $btnClass = "bg-gray-900 text-white hover:bg-gray-800 transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5";
                    $btnText = "Redeem Now";
                }
            ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col group hover:shadow-md transition duration-300">
                <div class="h-48 overflow-hidden bg-gradient-to-r from-green-500 to-teal-600 text-white relative">
                    <img src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=500&q=80" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    
                    <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-bold text-gray-900 shadow-sm">
                        <?php echo $reward['Points_Required']; ?> pts
                    </div>
                </div>

                <div class="p-5 flex-1 flex flex-col">
                    <div class="mb-2">
                        <div class="text-xs text-brand-600 font-bold uppercase tracking-wide mb-1">Reward</div>
                        <h3 class="font-bold text-gray-900 text-lg leading-tight">
                            <?php echo htmlspecialchars($reward['Reward_name']); ?>
                        </h3>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-4 line-clamp-2">
                        <?php echo htmlspecialchars($reward['Description']); ?>
                    </p>
                    
                    <div class="mt-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-xs font-medium <?php echo $reward['Stock'] < 10 ? 'text-red-600 bg-red-50' : 'text-green-700 bg-green-50'; ?> px-2.5 py-1 rounded-md">
                                <?php echo $reward['Stock']; ?> in stock
                            </div>
                        </div>

                        <button 
                            class="w-full py-2.5 rounded-lg text-sm font-bold <?php echo $btnClass; ?>"
                            <?php if (!$isDisabled): ?>
                                onclick="redeemItem(<?php echo $reward['Reward_ID']; ?>, '<?php echo addslashes($reward['Reward_name']); ?>')"
                            <?php else: ?>
                                disabled
                            <?php endif; ?>
                        >
                            <?php echo $btnText; ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>  

</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">
            &copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.
        </p>
    </div>
</footer>

<script src="marketplace.js"></script>

</body>
</html>