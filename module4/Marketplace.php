<?php
// 1. Start session (fallback if header.php didn't)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Include database connection (provides $pdo)

require '../database.php';

if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];

    // 3. Query Role from database
    // Even if Role is in Session, re-query database to avoid stale permissions
    $auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
    $auth_res = mysqli_query($con, $auth_sql);
    
    if ($auth_row = mysqli_fetch_assoc($auth_res)) {
        
        // 4. Check: if Role equals 1 (Admin)
        if ($auth_row['Role'] == 1) {
            
            // Redirect to target page (remember to use your actual filename)
            header("Location: /ecotrip/module4/Redeem-Record.php");
            exit(); // Must add exit to stop further execution
        }
    }
}

include '../header.php';
include '../background.php'; 



// 3. Set page title (header.php will use this variable)
$page_title = "Rewards Marketplace";

// 4. Check login status
// header.php may have already checked, but verify again for robustness
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect here or temporarily simulate a user (for testing)
    // $_SESSION['user_id'] = 18; // Remove this line after testing!
    header("Location:../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 5. Get current user's points (for Marketplace balance display)
// Note: header.php may only fetch avatar, not RedeemPoint, so query it here
$stmtUser = $pdo->prepare("SELECT RedeemPoint FROM user WHERE User_ID = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

// If user not found (e.g., deleted), provide a default to avoid errors
if (!$currentUser) {
    $currentUser = ['RedeemPoint' => 0];
}

// 6. Fetch product list
$stmtRewards = $pdo->query("SELECT * FROM reward WHERE Status = 'Active'");
$rewards = $stmtRewards->fetchAll(PDO::FETCH_ASSOC);

// --- Key point: prepare data first, then include Header ---
?>

<main class="flex-grow max-w-7xl mx-auto px-8 py-10 w-full">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Rewards Marketplace</h1>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-8 flex flex-col md:flex-row gap-4 items-center">
    
    <div class="relative flex-grow w-full">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        <input type="text" id="searchReward" placeholder="Search rewards..." 
               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-sm">
    </div>

    <div class="w-full md:w-auto">
        <select id="typeFilter" class="w-full md:w-40 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-500 outline-none cursor-pointer">
            <option value="all">All Types</option>
            <option value="Physical">📦 Physical</option>
            <option value="Virtual">🎟️ Virtual</option>
        </select>
    </div>

    <div class="w-full md:w-auto">
        <select id="priceFilter" class="w-full md:w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-500 outline-none cursor-pointer">
            <option value="all">Any Points</option>
            <option value="affordable">Affordable (< 500)</option>
            <option value="medium">Medium (500 - 1000)</option>
            <option value="premium">Premium (> 1000)</option>
        </select>
    </div>
</div>
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
                // Logical conditional
                $isOutOfStock = $reward['Stock'] <= 0;
                $notEnoughPoints = $currentUser['RedeemPoint'] < $reward['Points_Required'];
                $isDisabled = $isOutOfStock || $notEnoughPoints;
                
                // Button styling
                if ($isOutOfStock) {
                    $btnClass = "bg-gray-300 text-gray-500 cursor-not-allowed";
                    $btnText = "Out of Stock";
                } elseif ($notEnoughPoints) {
                    $btnClass = "bg-gray-200 text-gray-400 cursor-not-allowed";
                    // Calculate remaining points required
                    $diff = $reward['Points_Required'] - $currentUser['RedeemPoint'];
                    $btnText = "Need {$diff} more";
                } else {
                    $btnClass = "bg-gray-900 text-white hover:bg-gray-800 transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5";
                    $btnText = "Redeem Now";
                }
            ?>
    <div class="reward-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col group hover:shadow-md transition duration-300"
         data-name="<?php echo strtolower(htmlspecialchars($reward['Reward_name'])); ?>"
         data-type="<?php echo $reward['Type']; ?>"
         data-points="<?php echo $reward['Points_Required']; ?>">
            <div class="h-48 w-full bg-gray-100 overflow-hidden relative">
    <?php 
        // 1. Get image path
        $imgSrc = htmlspecialchars($reward['Reward_Photo']);
        
        // 2. If empty in DB, use default image
        if (empty($imgSrc)) {
            $imgSrc = "https://placehold.co/600x400/e2e8f0/94a3b8?text=No+Image";
        }
    ?>
    
    <img src="<?php echo $imgSrc; ?>" 
         alt="<?php echo htmlspecialchars($reward['Reward_name']); ?>" 
         class="w-full h-full object-cover transform group-hover:scale-105 transition duration-300"
         onerror="this.onerror=null; this.src='https://placehold.co/600x400/e2e8f0/94a3b8?text=Image+Error';">
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
                            <div class="text-xs font-bold text-gray-700 bg-gray-100 px-2.5 py-1 rounded-md">
                                <?php echo $reward['Points_Required']; ?> pts
                            </div>
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
<script src="marketplace.js"></script>
<?php
include '../footer.php';
?>
</body>
</html>