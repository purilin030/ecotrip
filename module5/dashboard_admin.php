<?php
session_start();
require '../database.php';

// 1. 安全检查
if (!isset($_SESSION['Firstname']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// 数据统计逻辑
// ---------------------------------------------------------
//----------------OVERVIEW TAB DATA SQL---------------------
// 1. 基础统计数据 (Cards)
$stats = [];

// Total Users
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM user");
$stats['users'] = mysqli_fetch_assoc($res)['cnt'];

// Active Teams
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM team");
$stats['teams'] = mysqli_fetch_assoc($res)['cnt'];

// Total Submissions
$res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM submissions");
$stats['submissions'] = mysqli_fetch_assoc($res)['cnt'];

// Total Points Distributed
$res = mysqli_query($con, "SELECT SUM(Point) as total FROM user");
$row = mysqli_fetch_assoc($res);
$stats['points'] = $row['total'] ? $row['total'] : 0;


// 2. 图表数据：每周新用户 (New Users/Week)
// Register_Date 格式假设为 '2025-11-20 09:37:58' (String)
// 我们使用 STR_TO_DATE 将其转为日期，然后按周分组
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

while($row = mysqli_fetch_assoc($chart_res)) {
    // 格式化一下标签，比如 "2025-W48"
    $weeks[] = $row['week_label'];
    $user_counts[] = $row['count'];
}

// 3. 图表数据：队伍人数分布 (Top 5 Teams)
$team_chart_sql = "SELECT Team_name, Total_members FROM team ORDER BY Total_members DESC LIMIT 5";
$team_chart_res = mysqli_query($con, $team_chart_sql);

$team_names = [];
$team_members = [];

while($row = mysqli_fetch_assoc($team_chart_res)) {
    $team_names[] = $row['Team_name'];
    $team_members[] = $row['Total_members'];
}
//-------------------------------------------------------------------------------------------
//-----------------------------END OF OVERVIEW TAB DATA SQL----------------------------------
//-------------------------------------------------------------------------------------------

// ----------------------------Points & Rewards TAB DATA SQL----------------------------------
// ----------------------------Module 4 Data: Points Economy----------------------------------
// 1. 积分赚取 (Earned) - 从 pointsledger
// 逻辑：按月统计 Points_Earned 总和
$sql_earned = "SELECT MONTH(Earned_Date) as m, SUM(Points_Earned) as total 
               FROM pointsledger 
               WHERE YEAR(Earned_Date) = YEAR(CURRENT_DATE) 
               GROUP BY MONTH(Earned_Date)";
$res_earned = mysqli_query($con, $sql_earned);
$earned_data = array_fill(1, 12, 0); // 初始化 1-12月为 0
while($row = mysqli_fetch_assoc($res_earned)) {
    $earned_data[$row['m']] = $row['total'];
}

// 2. 积分消耗 (Spent) - 从 redeemrecord + reward
// 逻辑：按月统计 (Redeem_Quantity * Points_Required)
$sql_spent = "SELECT MONTH(r.Redeem_Date) as m, SUM(r.Redeem_Quantity * rw.Points_Required) as total
              FROM redeemrecord r
              JOIN reward rw ON r.Reward_ID = rw.Reward_ID
              WHERE YEAR(r.Redeem_Date) = YEAR(CURRENT_DATE)
              GROUP BY MONTH(r.Redeem_Date)";
$res_spent = mysqli_query($con, $sql_spent);
$spent_data = array_fill(1, 12, 0);
while($row = mysqli_fetch_assoc($res_spent)) {
    $spent_data[$row['m']] = $row['total'];
}

// 3. 最受欢迎奖品 (Top Rewards)
$sql_pop_reward = "SELECT Reward_Name, COUNT(*) as cnt FROM redeemrecord GROUP BY Reward_Name ORDER BY cnt DESC LIMIT 5";
$res_pop = mysqli_query($con, $sql_pop_reward);
$reward_labels = [];
$reward_data = [];
while($row = mysqli_fetch_assoc($res_pop)) {
    $reward_labels[] = $row['Reward_Name'];
    $reward_data[] = $row['cnt'];
}

// 4. 库存预警 (Low Stock)
$sql_low_stock = "SELECT Reward_name, Stock, Reward_Photo FROM reward WHERE Stock < 10 AND Status = 'Active' ORDER BY Stock ASC LIMIT 5";
$res_low_stock = mysqli_query($con, $sql_low_stock);
$low_stock_items = [];
while($row = mysqli_fetch_assoc($res_low_stock)) {
    $low_stock_items[] = $row;
}

// 5. 用户财富分布 (Wealth Distribution)
// 统计不同积分段的用户数量
$sql_wealth = "
    SELECT 
        SUM(CASE WHEN RedeemPoint BETWEEN 0 AND 2000 THEN 1 ELSE 0 END) as '0-2000',
        SUM(CASE WHEN RedeemPoint BETWEEN 2001 AND 5000 THEN 1 ELSE 0 END) as '2001-5000',
        SUM(CASE WHEN RedeemPoint BETWEEN 5001 AND 10000 THEN 1 ELSE 0 END) as '5001-10000',
        SUM(CASE WHEN RedeemPoint > 10000 THEN 1 ELSE 0 END) as '10000+'
    FROM user";
$res_wealth = mysqli_query($con, $sql_wealth);
$wealth_data = mysqli_fetch_assoc($res_wealth);
// 转换成图表需要的数组格式
$wealth_counts = [
    $wealth_data['0-2000'], 
    $wealth_data['2001-5000'], 
    $wealth_data['5001-10000'], 
    $wealth_data['10000+']
];

// 6. 最近兑换记录 (Recent Transactions)
$sql_recent = "SELECT r.Redeem_Date, u.First_Name, u.Last_Name, rw.Reward_name, u.Avatar 
               FROM redeemrecord r
               JOIN user u ON r.Redeem_By = u.User_ID
               JOIN reward rw ON r.Reward_ID = rw.Reward_ID
               ORDER BY r.Redeem_Date DESC LIMIT 5";
$res_recent = mysqli_query($con, $sql_recent);
$recent_tx = [];
while($row = mysqli_fetch_assoc($res_recent)) {
    $recent_tx[] = $row;
}
//------------------------------------------------------------------------------
// ----------------------END OF Points & Rewards TAB DATA SQL-------------------
//------------------------------------------------------------------------------

//------------------------Activity TAB DATA SQL---------------------------------

//------------------------------------------------------------------------------
// ----------------------END OF Activity TAB DATA SQL--------------------------
//------------------------------------------------------------------------------
$page_title = "ecoTrip - Dashboard";
include '../header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
            <p class="text-gray-500 mt-1">Platform statistics and performance metrics.</p>
        </div>

    <div class="mb-8 border-b border-gray-200"><!-- Tabs Navigation -->
        
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('overview')" id="tab-overview" 
            class="tab-btn border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            <i class="fa-solid fa-chart-pie mr-2"></i> Overview
            </button>

            <button onclick="switchTab('financial')" id="tab-financial" 
            class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            <i class="fa-solid fa-coins mr-2"></i> Points & Rewards
            </button>

            <button onclick="switchTab('activity')" id="tab-activity" 
            class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1   border-b-2 font-medium text-sm transition-colors">
                <i class="fa-solid fa-person-running mr-2"></i> Challenges & Submissions
            </button>
        </nav>

    </div><!-- End of Tabs Navigation -->

    <div id="view-overview" class="dashboard-section animate-fade-in"><!-- Overview Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">            
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['users']); ?></p>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-full">
                        <i class="fa-solid fa-users text-xl"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Active Teams</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['teams']); ?></p>
                    </div>
                    <div class="p-3 bg-green-50 text-green-600 rounded-full">
                        <i class="fa-solid fa-people-group text-xl"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Submissions</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['submissions']); ?></p>
                    </div>
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-full">
                        <i class="fa-solid fa-camera text-xl"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Eco Points</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['points']); ?></p>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-full">
                        <i class="fa-solid fa-leaf text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-800">User Growth</h3>
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">New Users / Week</span>
                    </div>
                    <div class="relative h-72 w-full">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Top Teams</h3>
                        <p class="text-xs text-gray-400">By member count</p>
                    </div>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="teamChart"></canvas>
                    </div>
                </div>

            </div>
        </div>
    </div><!----------END OF OVERVIEW SECTION------>

    <div id="view-financial" class="dashboard-section hidden animate-fade-in space-y-8">
    
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Points Economy</h3>
                    <p class="text-xs text-gray-400">Earned (Green) vs. Spent (Red)</p>
                </div>
                <div class="relative h-72 w-full">
                    <canvas id="pointsFlowChart"></canvas>
                </div>
            </div><!--END OF POINTS ECONOMY-->

            <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Top Redeemed Rewards</h3>
                    <p class="text-xs text-gray-400">Most popular items</p>
                </div>
                <div class="relative h-64 w-full flex justify-center">
                    <canvas id="popularRewardsChart"></canvas>
                </div>
            </div><!--END OF POPULAR REWARDS-->
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <div class="mb-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">User Purchasing Power</h3>
            </div>
            <div class="relative h-48">
                <canvas id="wealthChart"></canvas>
            </div>
            <p class="text-xs text-center text-gray-400 mt-2">Points Balance Distribution</p>
        </div><!--END OF USER PURCHASING POWER-->

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
                                <div class="w-8 h-8 rounded bg-gray-100 overflow-hidden">
                                    <img src="<?php echo !empty($item['Reward_Photo']) ? $item['Reward_Photo'] : 'https://placehold.co/100'; ?>" class="w-full h-full object-cover">
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($item['Reward_name']); ?></span>
                            </div>
                            <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded-full">
                                Only <?php echo $item['Stock']; ?> left
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div><!--END OF LOW STOCK ALERT-->

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-4">Recent Redemptions</h3>
            <div class="space-y-4">
                <?php if (empty($recent_tx)): ?>
                    <p class="text-sm text-gray-400">No recent transactions.</p>
                <?php else: ?>
                    <?php foreach ($recent_tx as $tx): 
                         $avatar = !empty($tx['Avatar']) ? $tx['Avatar'] : "https://ui-avatars.com/api/?name=".$tx['First_Name'];
                    ?>
                        <div class="flex items-start gap-3">
                            <img src="<?php echo $avatar; ?>" class="w-8 h-8 rounded-full border border-gray-100">
                            <div>
                                <p class="text-xs text-gray-500">
                                    <span class="font-bold text-gray-800"><?php echo htmlspecialchars($tx['First_Name']); ?></span> 
                                    redeemed 
                                    <span class="text-brand-600 font-medium"><?php echo htmlspecialchars($tx['Reward_name']); ?></span>
                                </p>
                                <p class="text-[10px] text-gray-400"><?php echo date("M d, H:i", strtotime($tx['Redeem_Date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div><!--END OF RECENT REDEMPTION-->

    </div>
</div><!--End of Financial-->

        <div id="view-activity" class="dashboard-section hidden animate-fade-in">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Challenge Popularity</h3>
                <div class="relative h-72 w-full">
                    <canvas id="challengePopChart"></canvas>
                </div>
            </div>
        </div><!--End of Activity-->
    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p>
    </div>
</footer>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>

<script>
//--------------- Tab Switching Logic----------------------
function switchTab(tabName) {
    // 1. Hide all sections
    document.querySelectorAll('.dashboard-section').forEach(el => {
        el.classList.add('hidden');
    });

    // 2. Show selected section
    document.getElementById('view-' + tabName).classList.remove('hidden');

    // 3. Update Tab Styles
    document.querySelectorAll('.tab-btn').forEach(btn => {
        // Reset to inactive style
        btn.classList.remove('border-green-500', 'text-green-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });

    // Set active style
    const activeBtn = document.getElementById('tab-' + tabName);
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    activeBtn.classList.add('border-green-500', 'text-green-600');
}
//--------------------END OF TAB SWITCHING LOGIC--------------------------
// Charts Configuration
// -------------------Overview Charts -------------------------------------

    // 1. 配置 User Growth Chart (Line Chart)
    const ctxGrowth = document.getElementById('userGrowthChart').getContext('2d');
    
    // PHP 数据转 JS
    const weekLabels = <?php echo json_encode($weeks); ?>;
    const userCounts = <?php echo json_encode($user_counts); ?>;

    new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: weekLabels,
            datasets: [{
                label: 'New Users',
                data: userCounts,
                borderColor: '#16a34a', // ecoTrip Green
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                borderWidth: 2,
                tension: 0.4, // 平滑曲线
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#16a34a',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#f3f4f6' },
                    ticks: { precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. 配置 Team Chart (Doughnut Chart)
    const ctxTeam = document.getElementById('teamChart').getContext('2d');
    
    // PHP 数据转 JS
    const teamNames = <?php echo json_encode($team_names); ?>;
    const teamMembers = <?php echo json_encode($team_members); ?>;

    new Chart(ctxTeam, {
        type: 'doughnut',
        data: {
            labels: teamNames,
            datasets: [{
                data: teamMembers,
                backgroundColor: [
                    '#16a34a', // Green 600
                    '#22c55e', // Green 500
                    '#4ade80', // Green 400
                    '#86efac', // Green 300
                    '#bbf7d0'  // Green 200
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%', // 中间空心大小
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } }
                }
            }
        }
    });
//------------------------END OF Overview Charts-----------------------

// ---------------Chart 3: Points Economy (Bar Chart)------- -----------------
    const ctxPoints = document.getElementById('pointsFlowChart').getContext('2d');
    new Chart(ctxPoints, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Points Earned',
                    data: <?php echo json_encode(array_values($earned_data)); ?>,
                    backgroundColor: '#22c55e', // Green
                    borderRadius: 4
                },
                {
                    label: 'Points Spent',
                    data: <?php echo json_encode(array_values($spent_data)); ?>,
                    backgroundColor: '#ef4444', // Red
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // --- Chart 4: Popular Rewards (Pie Chart) ---
    const ctxRewards = document.getElementById('popularRewardsChart').getContext('2d');
    new Chart(ctxRewards, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($reward_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($reward_data); ?>,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#6366f1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 10 } }
            }
        }
    });

    // --- Chart 5: Wealth Distribution (Horizontal Bar) ---
    const ctxWealth = document.getElementById('wealthChart').getContext('2d');
    new Chart(ctxWealth, {
        type: 'bar', // 或者 'doughnut'
        data: {
            labels: ['0-2000 pts', '2001-5000 pts', '5001-10000 pts', '10000+ pts'],
            datasets: [{
                label: 'Users',
                data: <?php echo json_encode($wealth_counts); ?>,
                backgroundColor: [
                    '#94a3b8', // Gray (Low)
                    '#60a5fa', // Blue
                    '#34d399', // Emerald
                    '#fbbf24'  // Amber (High)
                ],
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            indexAxis: 'y', // 让柱状图横过来，更有“阶层”感
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true, grid: { display: false } },
                y: { grid: { display: false } }
            }
        }
    });
//---------------------------END OF Points & Rewards Charts---------------------------


</script>

</body>
</html>