<?php
session_start();
require '../database.php';

// 1. Security Check
if (!isset($_SESSION['Firstname']) || !isset($_SESSION['user_id'])) {
    header("Location: /ecotrip/module1/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['Firstname'];

// 2. Include Header
$page_title = "ecoTrip - Dashboard";
include '../header.php';

// ---------------------------------------------------------
// DATA FETCHING LOGIC
// ---------------------------------------------------------

// --- USER SPECIFIC DATA ---
$sql_user = "SELECT Point, RedeemPoint, Team_ID, 
            (SELECT COUNT(*) + 1 FROM user WHERE Point > u.Point) as `Rank` 
            FROM user u WHERE User_ID = '$user_id'";
$res_user = mysqli_query($con, $sql_user);
$user_data = ($res_user) ? mysqli_fetch_assoc($res_user) : ['Point'=>0, 'RedeemPoint'=>0, 'Team_ID'=>null, 'Rank'=>0];

$my_wallet = $user_data['RedeemPoint'];
$my_total_points = $user_data['Point'];
$my_rank = $user_data['Rank'];
$my_team_id = $user_data['Team_ID'];

// Submissions count
$res_sub = mysqli_query($con, "SELECT COUNT(*) as cnt FROM submissions WHERE User_ID = '$user_id'");
$my_submissions = ($res_sub) ? mysqli_fetch_assoc($res_sub)['cnt'] : 0;

// --- CHART DATA 1: POINTS GROWTH (Last 30 Days) ---
$sql_chart = "SELECT DATE_FORMAT(Earned_Date, '%m-%d') as day_label, SUM(Points_Earned) as daily_total
              FROM pointsledger
              WHERE User_ID = '$user_id' AND Earned_Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY day_label ORDER BY Earned_Date ASC";
$res_chart = mysqli_query($con, $sql_chart);
$chart_labels = [];
$chart_data = [];
if ($res_chart) {
    while($row = mysqli_fetch_assoc($res_chart)) {
        $chart_labels[] = $row['day_label'];
        $chart_data[] = $row['daily_total'];
    }
}

// --- NEW STATS: USER SUBMISSION STATUS (Pie Chart Data) ---
$sql_user_status = "SELECT Status, COUNT(*) as cnt FROM submissions WHERE User_ID = '$user_id' GROUP BY Status";
$res_user_status = mysqli_query($con, $sql_user_status);
$user_status_data = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
while ($row = mysqli_fetch_assoc($res_user_status)) {
    // Ensure the key matches the casing used in JS (e.g. 'Approved')
    $key = ucfirst(strtolower($row['Status'])); 
    if (isset($user_status_data[$key])) {
        $user_status_data[$key] = $row['cnt'];
    }
}

// --- NEW STATS: GLOBAL TOP 5 CHALLENGES (Bar Chart Data) ---
$sql_top5_global = "SELECT c.Title, COUNT(s.Submission_ID) as cnt 
                    FROM challenge c 
                    JOIN submissions s ON c.Challenge_ID = s.Challenge_ID 
                    GROUP BY c.Challenge_ID 
                    ORDER BY cnt DESC LIMIT 5";
$res_top5 = mysqli_query($con, $sql_top5_global);
$top5_labels = [];
$top5_data = [];
while ($row = mysqli_fetch_assoc($res_top5)) {
    // Truncate long titles for better chart display
    $title = (strlen($row['Title']) > 15) ? substr($row['Title'], 0, 15) . '...' : $row['Title'];
    $top5_labels[] = $title;
    $top5_data[] = $row['cnt'];
}

// 3. Consistency (Submissions per week)
$sql_consist = "SELECT DATE_FORMAT(Submission_Date, 'Wk %u') as wk, COUNT(*) as cnt 
                FROM submissions 
                WHERE User_ID = '$user_id' 
                GROUP BY wk ORDER BY Submission_Date ASC LIMIT 8";
$res_consist = mysqli_query($con, $sql_consist);
$weekly_labels = [];
$weekly_counts = [];
while($row = mysqli_fetch_assoc($res_consist)) {
    $weekly_labels[] = $row['wk'];
    $weekly_counts[] = $row['cnt'];
}


// --- HISTORY DATA ---
$check_table = mysqli_query($con, "SHOW TABLES LIKE 'donation_record'");
$has_donations = ($check_table && mysqli_num_rows($check_table) > 0);

$sql_history = "
    (SELECT 'Earned' as Type, pl.Points_Earned as Points, pl.Earned_Date as Date, c.Title as Description
     FROM pointsledger pl JOIN submissions s ON pl.Submission_ID = s.Submission_ID JOIN challenge c ON s.Challenge_ID = c.Challenge_ID
     WHERE pl.User_ID = '$user_id')
    UNION ALL
    (SELECT 'Spent' as Type, (r.Redeem_Quantity * rw.Points_Required) as Points, r.Redeem_Date as Date, CONCAT('Redeemed: ', rw.Reward_name) as Description
     FROM redeemrecord r JOIN reward rw ON r.Reward_ID = rw.Reward_ID WHERE r.Redeem_By = '$user_id')
";
if ($has_donations) {
    $sql_history .= " UNION ALL (SELECT 'Donated' as Type, d.Amount as Points, d.Donation_Date as Date, CONCAT('Donated to: ', dc.Title) as Description
     FROM donation_record d JOIN donation_campaign dc ON d.Campaign_ID = dc.Campaign_ID WHERE d.User_ID = '$user_id')";
}
$sql_history .= " ORDER BY Date DESC LIMIT 20";
$res_history = mysqli_query($con, $sql_history);
$history_items = [];
if ($res_history) { while($row = mysqli_fetch_assoc($res_history)) { $history_items[] = $row; } }

// --- BADGES LOGIC ---
$badges_config = [
    ['name' => 'Eco Starter', 'points' => 0, 'icon' => 'fa-seedling', 'desc' => 'Joined the movement'],
    ['name' => 'Green Walker', 'points' => 500, 'icon' => 'fa-shoe-prints', 'desc' => 'Earned 500+ points'],
    ['name' => 'Planet Protector', 'points' => 1500, 'icon' => 'fa-earth-americas', 'desc' => 'Earned 1,500+ points'],
    ['name' => 'Eco Legend', 'points' => 5000, 'icon' => 'fa-crown', 'desc' => 'Earned 5,000+ points'],
];

// --- IMPACT LOGIC ---
$impact_co2 = round($my_total_points * 0.01, 1);
$impact_plastic = round($my_total_points * 0.005, 1);
$impact_water = round($my_total_points * 0.1, 1);

// --- TEAM INFO ---
$team_members = [];
$team_info = null;
if (!empty($my_team_id)) {
    $res_t = mysqli_query($con, "SELECT * FROM team WHERE Team_ID = '$my_team_id'");
    if ($res_t) $team_info = mysqli_fetch_assoc($res_t);
    $res_m = mysqli_query($con, "SELECT First_Name, Last_Name, Point, Role, Avatar, User_ID FROM user WHERE Team_ID = '$my_team_id' ORDER BY Point DESC");
    if ($res_m) { while($row = mysqli_fetch_assoc($res_m)) { $team_members[] = $row; } }
}
?>

<link rel="stylesheet" href="../css1/dashboard_user.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-10">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($user_name); ?>! </h1>
            <p class="text-gray-500 mt-1">Here's your personal eco-impact summary.</p>
        </div>

        <div class="mb-8 border-b border-gray-200 overflow-x-auto">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchTab('overview')" id="tab-overview" 
                    class="tab-btn border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-house-user mr-2"></i> Overview
                </button>
                <button onclick="switchTab('achievements')" id="tab-achievements" 
                    class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-medal mr-2"></i> Achievements
                </button>
                <button onclick="switchTab('impact')" id="tab-impact" 
                    class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-earth-asia mr-2"></i> My Impact
                </button>
                <button onclick="switchTab('history')" id="tab-history" 
                    class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i> Points History
                </button>
                <?php if ($my_team_id): ?>
                <button onclick="switchTab('team')" id="tab-team" 
                    class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-users mr-2"></i> My Team
                </button>
                <?php endif; ?>
            </nav>
        </div>

        <div id="view-overview" class="dashboard-section animate-fade-in">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-2xl shadow-md text-white transform transition hover:scale-105">
                    <p class="text-sm font-medium text-green-100 uppercase tracking-wider">Wallet Balance</p>
                    <p class="text-3xl font-bold mt-1"><?php echo number_format($my_wallet); ?> <span class="text-lg font-normal">pts</span></p>
                    <a href="../module4/Marketplace.php" class="inline-block mt-4 text-xs bg-white/20 hover:bg-white/30 py-1.5 px-3 rounded transition">Redeem Rewards &rarr;</a>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Global Rank</p><p class="text-3xl font-bold text-gray-900 mt-1">#<?php echo $my_rank; ?></p></div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-full"><i class="fa-solid fa-trophy text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Lifetime Points</p><p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($my_total_points); ?></p></div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-full"><i class="fa-solid fa-star text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Challenges</p><p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $my_submissions; ?></p></div>
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-full"><i class="fa-solid fa-check-double text-xl"></i></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Trending Challenges</h3>
                        <p class="text-xs text-gray-400">Top 5 with most submissions globally</p>
                    </div>
                    <div class="relative h-72 w-full">
                        <canvas id="userTopChallengesChart"></canvas>
                    </div>
                </div>
                
                <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Consistency Score</h3>
                        <p class="text-xs text-gray-400">Your weekly submission streak</p>
                    </div>
                    <div class="relative flex-grow h-48">
                        <canvas id="consistencyChart"></canvas>
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-400">Keep the line going up! 🚀</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-achievements" class="dashboard-section hidden animate-fade-in space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">My Eco Journey</h3>
                            <p class="text-xs text-gray-400">Points Earned (Last 30 Days)</p>
                        </div>
                    </div>
                    <div class="relative h-64 w-full"><canvas id="myGrowthChart"></canvas></div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800">My Submission Status</h3>
                        <p class="text-xs text-gray-400">Breakdown of your activity (Counts)</p>
                    </div>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="userStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800">Badge Collection</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <?php foreach($badges_config as $badge): 
                            $unlocked = ($my_total_points >= $badge['points']);
                            $status_class = $unlocked ? 'bg-white border-green-200' : 'badge-locked border-gray-100';
                            $icon_color = $unlocked ? 'text-green-500 bg-green-50' : 'text-gray-400 bg-gray-100';
                        ?>
                        <div class="badge-card border rounded-2xl p-6 text-center <?php echo $status_class; ?>">
                            <div class="w-16 h-16 mx-auto rounded-full <?php echo $icon_color; ?> flex items-center justify-center mb-4 text-2xl shadow-sm">
                                <i class="fa-solid <?php echo $badge['icon']; ?>"></i>
                            </div>
                            <h4 class="font-bold text-gray-800"><?php echo $badge['name']; ?></h4>
                            <p class="text-xs text-gray-400 mt-2"><?php echo $badge['desc']; ?></p>
                            <?php if($unlocked): ?>
                                <span class="inline-block mt-3 text-[10px] font-bold text-green-600 bg-green-100 px-2 py-1 rounded-full">Unlocked</span>
                            <?php else: ?>
                                <span class="inline-block mt-3 text-[10px] font-bold text-gray-500 bg-gray-200 px-2 py-1 rounded-full"><?php echo number_format($badge['points']); ?> pts needed</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-impact" class="dashboard-section hidden animate-fade-in">
             <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Real World Impact</h3>
                    <span class="text-xs text-gray-500">Based on lifetime points</span>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="impact-card p-6 rounded-2xl shadow-sm flex flex-col items-center text-center">
                            <div class="p-4 bg-white rounded-full shadow-sm mb-4"><i class="fa-solid fa-cloud text-3xl text-gray-400"></i></div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $impact_co2; ?> <span class="text-lg font-normal text-gray-500">kg</span></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">CO2 Emissions Avoided</p>
                        </div>
                        <div class="impact-card p-6 rounded-2xl shadow-sm flex flex-col items-center text-center">
                            <div class="p-4 bg-white rounded-full shadow-sm mb-4"><i class="fa-solid fa-bottle-water text-3xl text-blue-400"></i></div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $impact_plastic; ?> <span class="text-lg font-normal text-gray-500">kg</span></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Plastic Waste Saved</p>
                        </div>
                        <div class="impact-card p-6 rounded-2xl shadow-sm flex flex-col items-center text-center">
                            <div class="p-4 bg-white rounded-full shadow-sm mb-4"><i class="fa-solid fa-droplet text-3xl text-blue-600"></i></div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $impact_water; ?> <span class="text-lg font-normal text-gray-500">L</span></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Water Conserved</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-history" class="dashboard-section hidden animate-fade-in">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h3 class="font-bold text-gray-800">Transaction History</h3></div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($history_items)): ?>
                        <div class="p-8 text-center text-gray-400">No history found yet.</div>
                    <?php else: ?>
                        <?php foreach($history_items as $item): 
                            $type = $item['Type'];
                            $color = ($type == 'Earned') ? 'text-green-600' : (($type == 'Donated') ? 'text-pink-500' : 'text-red-500');
                            $sign = ($type == 'Earned') ? '+' : '-';
                            $icon = ($type == 'Earned') ? 'fa-leaf' : (($type == 'Donated') ? 'fa-heart' : 'fa-cart-shopping');
                        ?>
                        <div class="p-5 flex items-center justify-between hover:bg-gray-50 transition">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center"><i class="fa-solid <?php echo $icon; ?>"></i></div>
                                <div>
                                    <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($item['Description']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date("M d, Y", strtotime($item['Date'])); ?></p>
                                </div>
                            </div>
                            <div class="font-bold <?php echo $color; ?>"><?php echo $sign . number_format($item['Points']); ?> pts</div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($my_team_id && $team_info): ?>
        <div id="view-team" class="dashboard-section hidden animate-fade-in">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-2xl mx-auto flex items-center justify-center text-3xl font-bold mb-4">
                            <?php echo strtoupper(substr($team_info['Team_name'], 0, 1)); ?>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($team_info['Team_name']); ?></h2>
                        <p class="text-gray-500 text-sm mt-1 mb-6">Code: <span class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo $team_info['Team_code']; ?></span></p>
                        
                        <?php if ($team_info['Owner_ID'] == $user_id): ?>
                            <a href="../module1/team_edit.php" class="block w-full py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 transition mb-3">Manage Team</a>
                        <?php endif; ?>
                        
                        <div class="border-t border-gray-100 pt-4 mt-4 grid grid-cols-2 gap-4 text-center">
                            <div>
                                <span class="block text-xs text-gray-400 uppercase font-bold">Members</span>
                                <span class="block text-xl font-bold text-gray-800"><?php echo $team_info['Total_members']; ?></span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-400 uppercase font-bold">Role</span>
                                <span class="block text-xl font-bold text-gray-800">
                                    <?php echo ($team_info['Owner_ID'] == $user_id) ? 'Captain' : 'Member'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-bold text-gray-800">Team Leaderboard</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php foreach($team_members as $index => $mem): 
                            $rank = $index + 1;
                            $medal = '';
                            if($rank == 1) $medal = '🥇';      // Gold Medal
                            elseif($rank == 2) $medal = '🥈';  // Silver Medal
                            elseif($rank == 3) $medal = '🥉';  // Bronze Medal
                            else $medal = '<span class="text-gray-400 font-mono w-6 text-center inline-block">#'.$rank.'</span>';
                            
                            $is_me = ($mem['User_ID'] == $user_id);
                            $bg_row = $is_me ? 'bg-green-50/50' : 'hover:bg-gray-50';
                            
                            $display_avatar = "https://ui-avatars.com/api/?name=" . urlencode($mem['First_Name']) . "&background=random&color=fff";
                            if (!empty($mem['Avatar'])) {
                                $phys_path = $_SERVER['DOCUMENT_ROOT'] . $mem['Avatar'];
                                if (file_exists($phys_path)) $display_avatar = $mem['Avatar'];
                            }
                        ?>
                        <div class="p-4 flex items-center justify-between transition <?php echo $bg_row; ?>">
                            <div class="flex items-center gap-4">
                                <div class="text-lg w-8 text-center"><?php echo $medal; ?></div>
                                <img src="<?php echo $display_avatar; ?>" class="w-10 h-10 rounded-full border border-gray-100 object-cover">
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">
                                        <?php echo htmlspecialchars($mem['First_Name'] . ' ' . $mem['Last_Name']); ?>
                                        <?php if($is_me) echo '<span class="ml-2 text-[10px] bg-green-200 text-green-800 px-2 py-0.5 rounded-full">YOU</span>'; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="font-bold text-gray-700"><?php echo number_format($mem['Point']); ?> pts</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>

<script>
    const dashboardData = {
        chartLabels: <?php echo json_encode($chart_labels ?? []); ?>,
        chartData: <?php echo json_encode($chart_data ?? []); ?>,
        weeklyLabels: <?php echo json_encode($weekly_labels); ?>,
        weeklyCounts: <?php echo json_encode($weekly_counts); ?>,
        // NEW STATS PASSED TO JS
        userStatus: <?php echo json_encode($user_status_data); ?>,
        top5Labels: <?php echo json_encode($top5_labels); ?>,
        top5Data: <?php echo json_encode($top5_data); ?>
    };
</script>

<script src="../js/dashboard_user.js"></script>

</body>
</html>