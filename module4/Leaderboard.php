<?php
require '../database.php'; 
include '../header.php';
include '../background.php'; 

// --- 逻辑处理区域 ---

// 1. 称号获取函数
function getEcoTitle($points) {
    if ($points >= 5000) return ['👑 Planet Hero', 'bg-yellow-100 text-yellow-800 border-yellow-200'];
    if ($points >= 2000) return ['🌳 Forest Guardian', 'bg-green-100 text-green-800 border-green-200'];
    if ($points >= 1000) return ['🌱 Tree Hugger', 'bg-emerald-100 text-emerald-800 border-emerald-200'];
    if ($points >= 500)  return ['🌿 Eco Scout', 'bg-blue-100 text-blue-800 border-blue-200'];
    return ['🌰 Sprout', 'bg-gray-100 text-gray-600 border-gray-200'];
}

// 2. 获取当前用户排名 (底部悬浮条逻辑)
$myRankData = null;
if (isset($_SESSION['user_id'])) {
    $myId = $_SESSION['user_id'];
    
    // 查我的分数
    $stmtMe = $pdo->prepare("SELECT Point, Avatar, CONCAT(First_Name, ' ', Last_Name) as Name FROM user WHERE User_ID = ?");
    $stmtMe->execute([$myId]);
    $me = $stmtMe->fetch(PDO::FETCH_ASSOC);
    
    if ($me) {
        $myPoints = $me['Point'];
        // 查排名 (分数比我高的人数 + 1)
        $stmtRank = $pdo->prepare("SELECT COUNT(*) as rank_above FROM user WHERE Point > ?");
        $stmtRank->execute([$myPoints]);
        $myRank = $stmtRank->fetch(PDO::FETCH_ASSOC)['rank_above'] + 1;
        
        // 查前一名 (激励机制)
        $stmtNext = $pdo->prepare("SELECT Point FROM user WHERE Point > ? ORDER BY Point ASC LIMIT 1");
        $stmtNext->execute([$myPoints]);
        $nextPlayer = $stmtNext->fetch(PDO::FETCH_ASSOC);
        $pointsToNext = $nextPlayer ? ($nextPlayer['Point'] - $myPoints) : 0;
        
        $myRankData = [
            'rank' => $myRank,
            'points' => $myPoints,
            'avatar' => getAvatarUrl($me['Avatar'], $me['Name'], 'individual'),
            'gap' => $pointsToNext
        ];
    }
}

// 3. 获取主列表数据
$mode = $_GET['mode'] ?? 'individual';
$period = $_GET['period'] ?? 'all';

$dateCondition = "";
if ($period === '7d') {
    $dateCondition = "AND p.Earned_Date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period === '30d') {
    $dateCondition = "AND p.Earned_Date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

if ($mode === 'individual') {
    if ($period === 'all') {
        // 增加 User_ID ASC 作为第二排序，防止同分随机排序
        $sql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS Name, Avatar, Point AS totalPoints, NULL AS LastUpdate FROM user ORDER BY Point DESC, User_ID ASC LIMIT 50";
    } else {
        $sql = "SELECT CONCAT(u.First_Name, ' ', u.Last_Name) AS Name, u.Avatar, COALESCE(SUM(p.Points_Earned), 0) AS totalPoints, MAX(p.Earned_Date) AS LastUpdate FROM user u LEFT JOIN pointsledger p ON u.User_ID = p.User_ID $dateCondition GROUP BY u.User_ID ORDER BY totalPoints DESC, MIN(p.Earned_Date) ASC LIMIT 50";
    }
} else {
    $joinPart = ($period === 'all') ? "LEFT JOIN pointsledger p ON u.User_ID = p.User_ID" : "LEFT JOIN pointsledger p ON u.User_ID = p.User_ID $dateCondition";
    $calcPart = ($period === 'all') ? "COALESCE(SUM(u.Point), 0)" : "COALESCE(SUM(p.Points_Earned), 0)";
    $sql = "SELECT t.Team_ID, t.Team_name AS Name, NULL as Avatar, $calcPart AS totalPoints, MAX(p.Earned_Date) AS LastUpdate FROM team t LEFT JOIN user u ON t.Team_ID = u.Team_ID $joinPart GROUP BY t.Team_ID, t.Team_name ORDER BY totalPoints DESC, MIN(p.Earned_Date) ASC LIMIT 50";
}

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 辅助函数：处理头像
function getAvatarUrl($avatarPath, $name, $mode) {
    $default = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=random&color=fff&size=128";
    if ($mode === 'team') return $default;
    if (!empty($avatarPath)) return "/ecotrip/avatars/" . basename($avatarPath);
    return $default;
}

foreach ($rows as &$row) {
    $row['display_avatar'] = getAvatarUrl($row['Avatar'] ?? '', $row['Name'], $mode);
}
unset($row);

$top3 = array_slice($rows, 0, 3);
$rest = array_slice($rows, 3);
$rank = 4;

// 样式定义
$activeTab = "flex-1 py-4 text-center text-sm font-bold text-green-700 border-b-4 border-green-600 bg-green-50/50 backdrop-blur-sm";
$inactiveTab = "flex-1 py-4 text-center text-sm font-medium text-gray-500 hover:text-green-600 hover:bg-white/30 transition-all";
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 relative z-10">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
        <div class="text-center md:text-left">
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight drop-shadow-sm">Leaderboard</h1>
            <p class="mt-2 text-gray-600 font-medium">Top eco-warriors making a difference.</p>
        </div>
        
        <form action="" method="GET" class="relative">
            <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
            <div class="flex items-center gap-3 bg-white/70 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/50 shadow-sm hover:shadow-md transition-shadow">
                <i class="fa-regular fa-calendar text-green-600"></i>
                <select name="period" onchange="this.form.submit()" class="text-sm font-bold text-gray-700 bg-transparent outline-none cursor-pointer">
                    <option value="all" <?= $period == 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="7d" <?= $period == '7d' ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="30d" <?= $period == '30d' ? 'selected' : '' ?>>Last 30 days</option>
                </select>
            </div>
        </form>
    </div>

    <?php if (!empty($top3)): ?>
    <div class="flex justify-center items-end gap-2 sm:gap-6 mb-16">
        
        <?php if (isset($top3[1])): $p2 = $top3[1]; list($t2, $c2) = getEcoTitle($p2['totalPoints']); ?>
        <div class="flex flex-col items-center order-1 group cursor-default">
            <div class="relative mb-2 transition-transform duration-300 group-hover:-translate-y-2">
                <img src="<?= $p2['display_avatar'] ?>" class="w-20 h-20 rounded-full border-4 border-gray-300 shadow-lg object-cover bg-white">
                <div class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 bg-gray-200 text-gray-700 text-xs font-bold px-3 py-1 rounded-full border border-gray-300 shadow-sm">#2</div>
            </div>
            <div class="w-24 sm:w-28 h-24 bg-gradient-to-t from-gray-300/80 to-gray-100/30 backdrop-blur-sm rounded-t-lg border-t border-white/50 flex flex-col items-center justify-start pt-4 shadow-sm">
                <p class="font-bold text-gray-800 text-sm truncate w-20 text-center"><?= htmlspecialchars($p2['Name']) ?></p>
                <span class="text-[10px] px-1.5 py-0.5 rounded border <?= $c2 ?> font-semibold mt-1 scale-90 origin-top">
                    <?= $t2 ?>
                </span>
                <p class="text-gray-600 text-xs font-bold mt-1"><?= number_format($p2['totalPoints']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($top3[0])): $p1 = $top3[0]; list($t1, $c1) = getEcoTitle($p1['totalPoints']); ?>
        <div class="flex flex-col items-center order-2 z-10 group cursor-default">
            <div class="relative mb-3 transition-transform duration-300 group-hover:-translate-y-2">
                <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 text-4xl animate-bounce drop-shadow-md">👑</div>
                <img src="<?= $p1['display_avatar'] ?>" class="w-28 h-28 rounded-full border-4 border-yellow-400 shadow-xl object-cover bg-white ring-4 ring-yellow-400/30">
                <div class="absolute -bottom-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-yellow-900 text-sm font-extrabold px-4 py-1 rounded-full border-2 border-white shadow-md">#1</div>
            </div>
            <div class="w-28 sm:w-36 h-32 bg-gradient-to-t from-yellow-300/70 to-yellow-100/30 backdrop-blur-sm rounded-t-xl border-t border-white/50 flex flex-col items-center justify-start pt-6 shadow-lg">
                <p class="font-bold text-gray-900 text-base truncate w-28 text-center"><?= htmlspecialchars($p1['Name']) ?></p>
                <span class="text-[10px] px-1.5 py-0.5 rounded border <?= $c1 ?> font-semibold mt-1">
                    <?= $t1 ?>
                </span>
                <p class="text-yellow-700 text-sm font-extrabold mt-1"><?= number_format($p1['totalPoints']) ?> pts</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($top3[2])): $p3 = $top3[2]; list($t3, $c3) = getEcoTitle($p3['totalPoints']); ?>
        <div class="flex flex-col items-center order-3 group cursor-default">
            <div class="relative mb-2 transition-transform duration-300 group-hover:-translate-y-2">
                <img src="<?= $p3['display_avatar'] ?>" class="w-20 h-20 rounded-full border-4 border-orange-300 shadow-lg object-cover bg-white">
                <div class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 bg-orange-100 text-orange-800 text-xs font-bold px-3 py-1 rounded-full border border-orange-200 shadow-sm">#3</div>
            </div>
            <div class="w-24 sm:w-28 h-20 bg-gradient-to-t from-orange-200/70 to-orange-100/30 backdrop-blur-sm rounded-t-lg border-t border-white/50 flex flex-col items-center justify-start pt-4 shadow-sm">
                <p class="font-bold text-gray-800 text-sm truncate w-20 text-center"><?= htmlspecialchars($p3['Name']) ?></p>
                <span class="text-[10px] px-1.5 py-0.5 rounded border <?= $c3 ?> font-semibold mt-1 scale-90 origin-top">
                    <?= $t3 ?>
                </span>
                <p class="text-gray-600 text-xs font-bold mt-1"><?= number_format($p3['totalPoints']) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    <?php endif; ?>

    <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/60 overflow-hidden">
        
        <div class="flex border-b border-gray-200/60">
            <a href="?mode=individual&period=<?= $period ?>" class="<?= $mode === 'individual' ? $activeTab : $inactiveTab ?>">Individual</a>
            <a href="?mode=team&period=<?= $period ?>" class="<?= $mode === 'team' ? $activeTab : $inactiveTab ?>">Teams</a>
        </div>

        <div class="grid grid-cols-12 gap-4 px-6 py-4 bg-gray-50/50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 sticky top-0 backdrop-blur-sm">
            <div class="col-span-2 md:col-span-1">Rank</div>
            <div class="col-span-6 md:col-span-7">User</div>
            <div class="col-span-2 text-right">Points</div>
            <div class="col-span-2 text-right hidden md:block">Update</div>
        </div>

        <div class="divide-y divide-gray-100/50">
            <?php if(empty($rows) || ($rows[0]['totalPoints'] == 0 && count($rows) == 1 && $rows[0]['totalPoints'] !== null)): ?> 
                <div class="p-16 text-center flex flex-col items-center justify-center text-gray-500">
                    <div class="bg-gray-100 p-4 rounded-full mb-3 shadow-inner">
                        <i class="fa-solid fa-chart-simple text-gray-400 text-xl"></i>
                    </div>
                    <p class="font-medium">No points recorded for this period yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($rest as $row): 
                    if ($row['totalPoints'] == 0) continue; 
                    $rowClass = "hover:bg-white/90 hover:scale-[1.01] hover:shadow-sm transition-all duration-200 cursor-default"; 
                    // 获取列表中的称号
                    list($titleText, $titleClass) = getEcoTitle($row['totalPoints']);
                ?>
                <div class="grid grid-cols-12 gap-4 px-6 py-4 items-center <?php echo $rowClass; ?>">
                    
                    <div class="col-span-2 md:col-span-1 flex items-center">
                        <?php if ($rank <= 10): ?>
                            <span class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center font-bold text-xs border border-green-200 shadow-sm"><?= $rank ?></span>
                        <?php else: ?>
                            <span class="w-8 h-8 flex items-center justify-center text-gray-400 font-bold text-sm"><?= $rank ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="col-span-6 md:col-span-7 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                        <div class="flex items-center gap-4">
                            <img src="<?= htmlspecialchars($row['display_avatar']) ?>" class="w-10 h-10 md:w-11 md:h-11 rounded-full object-cover shadow-sm bg-white border border-gray-100" alt="Avatar">
                            <span class="font-bold text-gray-800 text-sm md:text-base truncate">
                                <?= htmlspecialchars($row['Name']) ?>
                            </span>
                        </div>
                        
                        <span class="text-[10px] px-1.5 py-0.5 rounded border <?= $titleClass ?> font-semibold w-fit">
                            <?= $titleText ?>
                        </span>
                    </div>

                    <div class="col-span-2 flex items-center justify-end">
                        <span class="font-bold text-green-700 text-sm md:text-base bg-green-50 px-2 py-1 rounded-md border border-green-100">
                            <?= number_format($row['totalPoints']) ?>
                        </span>
                    </div>

                    <div class="col-span-2 hidden md:flex items-center justify-end text-xs font-medium text-gray-400">
                        <?= $row['LastUpdate'] ? date('M d', strtotime($row['LastUpdate'])) : '-' ?>
                    </div>
                </div>
                <?php $rank++; endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="bg-gray-50/80 px-6 py-4 border-t border-gray-200/60 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">
            Keep pushing to reach the top!
        </div>
    </div>
</main>



<?php if ($myRankData && $mode === 'individual'): ?>
<div class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50 px-4 py-3">
    <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="flex flex-col items-center justify-center w-10">
                <span class="text-xs text-gray-400 font-bold uppercase">Rank</span>
                <span class="text-xl font-black text-brand-600">#<?= $myRankData['rank'] ?></span>
            </div>
            <img src="<?= $myRankData['avatar'] ?>" class="w-10 h-10 rounded-full border border-gray-200">
            <div class="hidden sm:block">
                <p class="text-sm font-bold text-gray-800">You</p>
                <?php if ($myRankData['gap'] > 0): ?>
                    <p class="text-xs text-orange-500 font-medium">Need <?= $myRankData['gap'] ?> pts to rank up!</p>
                <?php else: ?>
                    <p class="text-xs text-green-600 font-medium">You are the champion!</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-right">
            <span class="block text-lg font-bold text-gray-900"><?= number_format($myRankData['points']) ?> pts</span>
        </div>
    </div>
</div>
<div class="h-20"></div>
<?php endif; ?>
</body>
</html>