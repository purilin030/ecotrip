<?php
session_start();
require '../database.php';

// 1. Security Check
if (!isset($_SESSION['Firstname']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['Firstname'];

// --- FIX: Include Header HERE (At the top) ---
$page_title = "ecoTrip - Dashboard";
include '../header.php';
// ---------------------------------------------

// ---------------------------------------------------------
// DATA FETCHING LOGIC
// ---------------------------------------------------------

// --- OVERVIEW TAB DATA ---
$stats = [];

// Total Users
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM user");
$stats['users'] = ($res) ? mysqli_fetch_assoc($res)['cnt'] : 0;

// Active Teams
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM team");
$stats['teams'] = ($res) ? mysqli_fetch_assoc($res)['cnt'] : 0;

// Total Submissions
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM submissions");
$stats['submissions'] = ($res) ? mysqli_fetch_assoc($res)['cnt'] : 0;

// Total Points Distributed
$res = mysqli_query($con, "SELECT SUM(Point) as total FROM user");
$row = ($res) ? mysqli_fetch_assoc($res) : ['total' => 0];
$stats['points'] = $row['total'] ? $row['total'] : 0;


// Chart: User Growth
$chart_sql = "
    SELECT 
        DATE_FORMAT(STR_TO_DATE(Register_Date, '%Y-%m-%d %H:%i:%s'), '%Y-W%u') as week_label,
        COUNT(*) as count
    FROM user
    WHERE Register_Date IS NOT NULL AND Register_Date != ''
    GROUP BY week_label
    ORDER BY week_label ASC
    LIMIT 12
";
$chart_res = mysqli_query($con, $chart_sql);
$weeks = [];
$user_counts = [];

if ($chart_res) {
    while($row = mysqli_fetch_assoc($chart_res)) {
        $weeks[] = $row['week_label'];
        $user_counts[] = $row['count'];
    }
}

// Chart: Team Distribution
$team_chart_sql = "SELECT Team_name, Total_members FROM team ORDER BY Total_members DESC LIMIT 5";
$team_chart_res = mysqli_query($con, $team_chart_sql);
$team_names = [];
$team_members = [];

if ($team_chart_res) {
    while($row = mysqli_fetch_assoc($team_chart_res)) {
        $team_names[] = $row['Team_name'];
        $team_members[] = $row['Total_members'];
    }
}

// ----------------------------Points & Rewards TAB DATA----------------------------------
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


// --- HISTORY QUERY (With Safety Check) ---
// We check if donation_record exists first to prevent crashes
$check_table = mysqli_query($con, "SHOW TABLES LIKE 'donation_record'");
$has_donations = ($check_table && mysqli_num_rows($check_table) > 0);

$sql_history = "
    (SELECT 
        'Earned' as Type, 
        pl.Points_Earned as Points, 
        pl.Earned_Date as Date, 
        c.Title as Description
     FROM pointsledger pl
     JOIN submissions s ON pl.Submission_ID = s.Submission_ID
     JOIN challenge c ON s.Challenge_ID = c.Challenge_ID
     WHERE pl.User_ID = '$user_id')
    
    UNION ALL
    
    (SELECT 
        'Spent' as Type, 
        (r.Redeem_Quantity * rw.Points_Required) as Points, 
        r.Redeem_Date as Date, 
        CONCAT('Redeemed: ', rw.Reward_name) as Description
     FROM redeemrecord r
     JOIN reward rw ON r.Reward_ID = rw.Reward_ID
     WHERE r.Redeem_By = '$user_id')
";

if ($has_donations) {
    $sql_history .= "
    UNION ALL
    (SELECT 
        'Donated' as Type, 
        d.Amount as Points, 
        d.Donation_Date as Date, 
        CONCAT('Donated to: ', dc.Title) as Description
     FROM donation_record d
     JOIN donation_campaign dc ON d.Campaign_ID = dc.Campaign_ID
     WHERE d.User_ID = '$user_id')
    ";
}

$sql_history .= " ORDER BY Date DESC LIMIT 20";

$res_history = mysqli_query($con, $sql_history);
$history_items = [];

if ($res_history) {
    while($row = mysqli_fetch_assoc($res_history)) {
        $history_items[] = $row;
    }
} else {
    // Optional: Uncomment for debugging
    // echo "SQL Error: " . mysqli_error($con);
}

// --- Chart Data: Last 30 Days ---
$sql_chart = "
    SELECT DATE_FORMAT(Earned_Date, '%m-%d') as day_label, SUM(Points_Earned) as daily_total
    FROM pointsledger
    WHERE User_ID = '$user_id' AND Earned_Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY day_label
    ORDER BY Earned_Date ASC
";
$res_chart = mysqli_query($con, $sql_chart);
$chart_labels = [];
$chart_data = [];
if ($res_chart) {
    while($row = mysqli_fetch_assoc($res_chart)) {
        $chart_labels[] = $row['day_label'];
        $chart_data[] = $row['daily_total'];
    }
}

// --- Team Info ---
$team_members = [];
$team_info = null;
if (!empty($my_team_id)) {
    $res_t = mysqli_query($con, "SELECT * FROM team WHERE Team_ID = '$my_team_id'");
    if ($res_t) $team_info = mysqli_fetch_assoc($res_t);
    
    $sql_members = "SELECT First_Name, Last_Name, Point, Role, Avatar, User_ID FROM user WHERE Team_ID = '$my_team_id' ORDER BY Point DESC";
    $res_m = mysqli_query($con, $sql_members);
    if ($res_m) {
        while($row = mysqli_fetch_assoc($res_m)) {
            $team_members[] = $row;
        }
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-10">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
            <p class="text-gray-500 mt-1">Here's your personal eco-impact summary.</p>
        </div>

        <div class="mb-8 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchTab('overview')" id="tab-overview" 
                    class="tab-btn border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fa-solid fa-house-user mr-2"></i> Overview
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
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-2xl shadow-md text-white">
                    <p class="text-sm font-medium text-green-100 uppercase tracking-wider">Wallet Balance</p>
                    <p class="text-3xl font-bold mt-1"><?php echo number_format($my_wallet); ?> <span class="text-lg font-normal">pts</span></p>
                    <a href="../module4/Marketplace.php" class="inline-block mt-4 text-xs bg-white/20 hover:bg-white/30 py-1.5 px-3 rounded transition">
                        Redeem Rewards &rarr;
                    </a>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Global Rank</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">#<?php echo $my_rank; ?></p>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-full"><i class="fa-solid fa-trophy text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Lifetime Points</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($my_total_points); ?></p>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-full"><i class="fa-solid fa-star text-xl"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Challenges Done</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $my_submissions; ?></p>
                    </div>
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-full"><i class="fa-solid fa-check-double text-xl"></i></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">My Eco Journey</h3>
                        <span class="text-xs text-gray-400">Last 30 Days Activity</span>
                    </div>
                    <div class="relative h-72 w-full"><canvas id="myGrowthChart"></canvas></div>
                </div>
                <div class="lg:col-span-1 flex flex-col gap-6">
                    <div class="bg-indigo-900 text-white p-6 rounded-2xl shadow-md h-full flex flex-col justify-center items-center text-center">
                        <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mb-4 text-3xl">🚀</div>
                        <h3 class="text-xl font-bold mb-2">Ready for more?</h3>
                        <p class="text-indigo-200 text-sm mb-6">Complete daily challenges to earn more points and climb the leaderboard!</p>
                        <a href="../module2/view_challenge.php" class="bg-white text-indigo-900 font-bold py-3 px-8 rounded-full hover:bg-indigo-50 transition w-full">Find Challenges</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-history" class="dashboard-section hidden animate-fade-in">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800">Transaction History</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($history_items)): ?>
                        <div class="p-8 text-center text-gray-400">No history found yet. Start completing challenges!</div>
                    <?php else: ?>
                        <?php foreach($history_items as $item): 
                            $type = $item['Type'];
                            $color_class = 'text-red-500';
                            $sign = '-';
                            $icon = '<i class="fa-solid fa-cart-shopping"></i>';
                            $bg_icon = 'bg-red-100 text-red-600';

                            if ($type == 'Earned') {
                                $color_class = 'text-green-600';
                                $sign = '+';
                                $icon = '<i class="fa-solid fa-leaf"></i>';
                                $bg_icon = 'bg-green-100 text-green-600';
                            } elseif ($type == 'Donated') {
                                $color_class = 'text-pink-500';
                                $sign = '-';
                                $icon = '<i class="fa-solid fa-heart"></i>';
                                $bg_icon = 'bg-pink-100 text-pink-500';
                            }
                        ?>
                        <div class="p-5 flex items-center justify-between hover:bg-gray-50 transition">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full <?php echo $bg_icon; ?> flex items-center justify-center">
                                    <?php echo $icon; ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($item['Description']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date("M d, Y", strtotime($item['Date'])); ?></p>
                                </div>
                            </div>
                            <div class="font-bold <?php echo $color_class; ?>">
                                <?php echo $sign . number_format($item['Points']); ?> pts
                            </div>
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
                        <div class="w-20 h-20 bg-brand-100 text-brand-600 rounded-2xl mx-auto flex items-center justify-center text-3xl font-bold mb-4">
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
                                <span class="block text-xs text-gray-400 uppercase font-bold">My Role</span>
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
                            if($rank == 1) $medal = '🥇';
                            elseif($rank == 2) $medal = '🥈';
                            elseif($rank == 3) $medal = '🥉';
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

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8"><p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p></div>
</footer>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>

<script>
    // Tab Switching Logic
    function switchTab(tabName) {
        document.querySelectorAll('.dashboard-section').forEach(el => el.classList.add('hidden'));
        document.getElementById('view-' + tabName).classList.remove('hidden');

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-green-500', 'text-green-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });

        const activeBtn = document.getElementById('tab-' + tabName);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
            activeBtn.classList.add('border-green-500', 'text-green-600');
        }
    }

    // Chart: User Growth
    const ctxGrowth = document.getElementById('userGrowthChart');
    if (ctxGrowth) {
        new Chart(ctxGrowth.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weeks); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode($user_counts); ?>,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    // Chart: My Growth
    const ctx = document.getElementById('myGrowthChart');
    if (ctx) {
        const labels = <?php echo json_encode($chart_labels); ?>;
        const dataPoints = <?php echo json_encode($chart_data); ?>;
        if(labels.length > 0) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Points Earned',
                        data: dataPoints,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
                }
            });
        } else {
            const ctx2d = ctx.getContext('2d');
            ctx2d.font = "14px Inter";
            ctx2d.fillStyle = "#9ca3af";
            ctx2d.textAlign = "center";
            ctx2d.fillText("No recent activity data to display", ctx.width/2, ctx.height/2);
        }
    }
</script>

</body>
</html>