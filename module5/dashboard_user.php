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
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p>
    </div>
</footer>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>

<script>
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
</script>

</body>
</html>