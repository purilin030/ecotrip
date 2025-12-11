<?php
session_start();
require '../database.php';

// 1. Security Check
if (!isset($_SESSION['Firstname']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// DATA FETCHING LOGIC (All your existing SQL)
// ---------------------------------------------------------

// --- OVERVIEW TAB ---
$stats = [];
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM user");
$stats['users'] = mysqli_fetch_assoc($res)['cnt'];
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM team");
$stats['teams'] = mysqli_fetch_assoc($res)['cnt'];
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM submissions");
$stats['submissions'] = mysqli_fetch_assoc($res)['cnt'];
$res = mysqli_query($con, "SELECT SUM(Point) as total FROM user");
$row = mysqli_fetch_assoc($res);
$stats['points'] = $row['total'] ? $row['total'] : 0;

// Chart: User Growth
$chart_sql = "SELECT DATE_FORMAT(STR_TO_DATE(Register_Date, '%Y-%m-%d %H:%i:%s'), '%Y-W%u') as week_label, COUNT(*) as count FROM user WHERE Register_Date IS NOT NULL AND Register_Date != '' GROUP BY week_label ORDER BY week_label ASC LIMIT 12";
$chart_res = mysqli_query($con, $chart_sql);
$weeks = [];
$user_counts = [];
while ($row = mysqli_fetch_assoc($chart_res)) {
    $weeks[] = $row['week_label'];
    $user_counts[] = $row['count'];
}

// Chart: Top Teams
$team_chart_sql = "SELECT Team_name, Total_members FROM team ORDER BY Total_members DESC LIMIT 5";
$team_chart_res = mysqli_query($con, $team_chart_sql);
$team_names = [];
$team_members = [];
while ($row = mysqli_fetch_assoc($team_chart_res)) {
    $team_names[] = $row['Team_name'];
    $team_members[] = $row['Total_members'];
}

// --- POINTS TAB ---
$sql_earned = "SELECT MONTH(Earned_Date) as m, SUM(Points_Earned) as total FROM pointsledger WHERE YEAR(Earned_Date) = YEAR(CURRENT_DATE) GROUP BY MONTH(Earned_Date)";
$res_earned = mysqli_query($con, $sql_earned);
$earned_data = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($res_earned)) $earned_data[$row['m']] = $row['total'];

$sql_spent = "SELECT MONTH(r.Redeem_Date) as m, SUM(r.Redeem_Quantity * rw.Points_Required) as total FROM redeemrecord r JOIN reward rw ON r.Reward_ID = rw.Reward_ID WHERE YEAR(r.Redeem_Date) = YEAR(CURRENT_DATE) GROUP BY MONTH(r.Redeem_Date)";
$res_spent = mysqli_query($con, $sql_spent);
$spent_data = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($res_spent)) $spent_data[$row['m']] = $row['total'];

$sql_pop_reward = "SELECT Reward_Name, COUNT(*) as cnt FROM redeemrecord GROUP BY Reward_Name ORDER BY cnt DESC LIMIT 5";
$res_pop = mysqli_query($con, $sql_pop_reward);
$reward_labels = [];
$reward_data = [];
while ($row = mysqli_fetch_assoc($res_pop)) {
    $reward_labels[] = $row['Reward_Name'];
    $reward_data[] = $row['cnt'];
}

$sql_low_stock = "SELECT Reward_name, Stock, Reward_Photo FROM reward WHERE Stock < 50 AND Status = 'Active' ORDER BY Stock ASC LIMIT 5";
$res_low_stock = mysqli_query($con, $sql_low_stock);
$low_stock_items = [];
while ($row = mysqli_fetch_assoc($res_low_stock)) $low_stock_items[] = $row;

$sql_wealth = "SELECT SUM(CASE WHEN RedeemPoint BETWEEN 0 AND 2000 THEN 1 ELSE 0 END) as '0-2000', SUM(CASE WHEN RedeemPoint BETWEEN 2001 AND 5000 THEN 1 ELSE 0 END) as '2001-5000', SUM(CASE WHEN RedeemPoint BETWEEN 5001 AND 10000 THEN 1 ELSE 0 END) as '5001-10000', SUM(CASE WHEN RedeemPoint > 10000 THEN 1 ELSE 0 END) as '10000+' FROM user";
$res_wealth = mysqli_query($con, $sql_wealth);
$wealth_data = mysqli_fetch_assoc($res_wealth);
$wealth_counts = [$wealth_data['0-2000'], $wealth_data['2001-5000'], $wealth_data['5001-10000'], $wealth_data['10000+']];

$sql_recent = "SELECT r.Redeem_Date, u.First_Name, u.Last_Name, rw.Reward_name, u.Avatar FROM redeemrecord r JOIN user u ON r.Redeem_By = u.User_ID JOIN reward rw ON r.Reward_ID = rw.Reward_ID ORDER BY r.Redeem_Date DESC LIMIT 5";
$res_recent = mysqli_query($con, $sql_recent);
$recent_tx = [];
while ($row = mysqli_fetch_assoc($res_recent)) $recent_tx[] = $row;

// --- ACTIVITY TAB ---
$sql_pop_chal = "SELECT c.Title, COUNT(s.Submission_ID) as cnt FROM challenge c LEFT JOIN submissions s ON c.Challenge_ID = s.Challenge_ID GROUP BY c.Challenge_ID ORDER BY cnt DESC LIMIT 5";
$res_pop_chal = mysqli_query($con, $sql_pop_chal);
$chal_labels = [];
$chal_data = [];
while ($row = mysqli_fetch_assoc($res_pop_chal)) {
    $title = (strlen($row['Title']) > 20) ? substr($row['Title'], 0, 18) . '...' : $row['Title'];
    $chal_labels[] = $title;
    $chal_data[] = $row['cnt'];
}

$sql_status = "SELECT Status, COUNT(*) as cnt FROM submissions GROUP BY Status";
$res_status = mysqli_query($con, $sql_status);
$status_counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
while ($row = mysqli_fetch_assoc($res_status)) {
    $key = ucfirst(strtolower($row['Status']));
    if (isset($status_counts[$key])) $status_counts[$key] = $row['cnt'];
}

$sql_subs = "SELECT s.Submission_Date, s.Status, u.First_Name, u.Last_Name, c.Title, u.Avatar FROM submissions s JOIN user u ON s.User_ID = u.User_ID JOIN challenge c ON s.Challenge_ID = c.Challenge_ID ORDER BY s.Submission_Date DESC LIMIT 6";
$res_subs = mysqli_query($con, $sql_subs);
$recent_submissions = [];
while ($row = mysqli_fetch_assoc($res_subs)) $recent_submissions[] = $row;

// ---------------------------------------------------------
// END DATA FETCHING
// ---------------------------------------------------------

$page_title = "ecoTrip - Dashboard";
include '../header.php';
?>

<link rel="stylesheet" href="dashboard_admin.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-7xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
            <p class="text-gray-500 mt-1">Platform statistics and performance metrics.</p>
        </div>

        <div class="mb-8 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchTab('overview')" id="tab-overview" class="tab-btn border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-chart-pie mr-2"></i> Overview
                </button>
                <button onclick="switchTab('financial')" id="tab-financial" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-coins mr-2"></i> Points & Rewards
                </button>
                <button onclick="switchTab('activity')" id="tab-activity" class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-person-running mr-2"></i> Challenges & Submissions
                </button>
            </nav>
        </div>

        <div id="view-overview" class="dashboard-section animate-fade-in">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['users']); ?></p>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-full"><i class="fa-solid fa-users text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Active Teams</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['teams']); ?></p>
                    </div>
                    <div class="p-3 bg-green-50 text-green-600 rounded-full"><i class="fa-solid fa-people-group text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Submissions</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['submissions']); ?></p>
                    </div>
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-full"><i class="fa-solid fa-camera text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Eco Points</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['points']); ?></p>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-full"><i class="fa-solid fa-leaf text-xl"></i></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-800">User Growth</h3>
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">New Users / Week</span>
                    </div>
                    <div class="relative h-72 w-full"><canvas id="userGrowthChart"></canvas></div>
                </div>
                <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Top Teams</h3>
                        <p class="text-xs text-gray-400">By member count</p>
                    </div>
                    <div class="relative h-64 w-full flex justify-center"><canvas id="teamChart"></canvas></div>
                </div>
            </div>
        </div>

        <div id="view-financial" class="dashboard-section hidden animate-fade-in space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Points Economy</h3>
                        <p class="text-xs text-gray-400">Earned (Green) vs. Spent (Red)</p>
                    </div>
                    <div class="relative h-72 w-full"><canvas id="pointsFlowChart"></canvas></div>
                </div>
                <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Top Redeemed Rewards</h3>
                        <p class="text-xs text-gray-400">Most popular items</p>
                    </div>
                    <div class="relative h-64 w-full flex justify-center"><canvas id="popularRewardsChart"></canvas></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-4 flex justify-between items-center"><h3 class="font-bold text-gray-800">User Purchasing Power</h3></div>
                    <div class="relative h-48"><canvas id="wealthChart"></canvas></div>
                    <p class="text-xs text-center text-gray-400 mt-2">Points Balance Distribution</p>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-4 flex justify-between items-center">
                        <h3 class="font-bold text-gray-800 text-red-600"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Low Stock Alert</h3>
                        <a href="../module4/Inventory.php" class="text-xs text-blue-500 hover:underline">Manage</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($low_stock_items)): ?>
                            <p class="text-sm text-gray-400 text-center py-4">All stock levels are healthy! 🎉</p>
                        <?php else: ?>
                            <?php foreach ($low_stock_items as $item): ?>
                                <div class="flex items-center justify-between p-2 hover:bg-red-50 rounded-lg transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-gray-100 overflow-hidden"><img src="<?php echo !empty($item['Reward_Photo']) ? $item['Reward_Photo'] : 'https://placehold.co/100'; ?>" class="w-full h-full object-cover"></div>
                                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($item['Reward_name']); ?></span>
                                    </div>
                                    <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded-full">Only <?php echo $item['Stock']; ?> left</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-800 mb-4">Recent Redemptions</h3>
                    <div class="space-y-4">
                        <?php if (empty($recent_tx)): ?>
                            <p class="text-sm text-gray-400">No recent transactions.</p>
                        <?php else: ?>
                            <?php foreach ($recent_tx as $tx): 
                                $avatar = !empty($tx['Avatar']) ? $tx['Avatar'] : "https://ui-avatars.com/api/?name=" . $tx['First_Name'];
                            ?>
                                <div class="flex items-start gap-3">
                                    <img src="<?php echo $avatar; ?>" class="w-8 h-8 rounded-full border border-gray-100">
                                    <div>
                                        <p class="text-xs text-gray-500"><span class="font-bold text-gray-800"><?php echo htmlspecialchars($tx['First_Name']); ?></span> redeemed <span class="text-brand-600 font-medium"><?php echo htmlspecialchars($tx['Reward_name']); ?></span></p>
                                        <p class="text-[10px] text-gray-400"><?php echo date("M d, H:i", strtotime($tx['Redeem_Date'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-activity" class="dashboard-section hidden animate-fade-in space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Most Popular Challenges</h3>
                    <p class="text-xs text-gray-400 mb-4">Based on total submission count</p>
                    <div class="relative h-64 w-full"><canvas id="challengePopChart"></canvas></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Submission Status</h3>
                    <p class="text-xs text-gray-400 mb-4">Approval Rate Overview</p>
                    <div class="relative h-64 w-full flex justify-center"><canvas id="statusChart"></canvas></div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Recent Submissions</h3>
                    <a href="../submission/submission.php" class="text-sm text-green-600 hover:text-green-700 font-medium">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-gray-100">
                                <th class="font-medium py-3">User</th>
                                <th class="font-medium py-3">Challenge</th>
                                <th class="font-medium py-3">Date</th>
                                <th class="font-medium py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (empty($recent_submissions)): ?>
                                <tr><td colspan="4" class="py-4 text-center text-gray-400">No submissions found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_submissions as $sub): 
                                    $avatar = !empty($sub['Avatar']) ? $sub['Avatar'] : "https://ui-avatars.com/api/?name=" . $sub['First_Name'];
                                    $statusColor = 'bg-gray-100 text-gray-600';
                                    if ($sub['Status'] == 'Approved') $statusColor = 'bg-green-100 text-green-700';
                                    if ($sub['Status'] == 'Rejected') $statusColor = 'bg-red-100 text-red-700';
                                    if ($sub['Status'] == 'Pending') $statusColor = 'bg-yellow-100 text-yellow-700';
                                ?>
                                    <tr class="group hover:bg-gray-50 transition-colors">
                                        <td class="py-3 pr-4">
                                            <div class="flex items-center gap-3">
                                                <img src="<?php echo $avatar; ?>" class="w-8 h-8 rounded-full border border-gray-200">
                                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($sub['First_Name'] . ' ' . $sub['Last_Name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600"><?php echo htmlspecialchars($sub['Title']); ?></td>
                                        <td class="py-3 pr-4 text-gray-400 text-xs"><?php echo date('M d, Y h:i A', strtotime($sub['Submission_Date'])); ?></td>
                                        <td class="py-3"><span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $statusColor; ?>"><?php echo ucfirst($sub['Status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8"><p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p></div>
</footer>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>

<script>
    const dashboardData = {
        weeks: <?php echo json_encode($weeks); ?>,
        userCounts: <?php echo json_encode($user_counts); ?>,
        teamNames: <?php echo json_encode($team_names); ?>,
        teamMembers: <?php echo json_encode($team_members); ?>,
        earnedData: <?php echo json_encode(array_values($earned_data)); ?>,
        spentData: <?php echo json_encode(array_values($spent_data)); ?>,
        rewardLabels: <?php echo json_encode($reward_labels); ?>,
        rewardData: <?php echo json_encode($reward_data); ?>,
        wealthCounts: <?php echo json_encode($wealth_counts); ?>,
        chalLabels: <?php echo json_encode($chal_labels); ?>,
        chalData: <?php echo json_encode($chal_data); ?>,
        statusCounts: <?php echo json_encode($status_counts); ?>
    };
</script>

<script src="dashboard_admin.js"></script>

</body>
</html>