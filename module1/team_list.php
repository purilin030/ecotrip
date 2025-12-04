<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$auth_sql = "SELECT Role FROM user WHERE User_ID = '$current_user_id'";
$auth_res = mysqli_query($con, $auth_sql);
$auth_row = mysqli_fetch_assoc($auth_res);

if ($auth_row['Role'] == 0) {
    $_SESSION['flash_error'] = "Access Denied.";
    header("Location: index.php");
    exit();
}

// 2. 获取所有队伍
$sql = "SELECT t.*, u.First_Name, u.Last_Name 
        FROM team t 
        LEFT JOIN user u ON t.Owner_ID = u.User_ID 
        ORDER BY t.Team_ID ASC";
$result = mysqli_query($con, $sql);

$page_title = "Team List - ecoTrip Admin";
include '../header.php';
?>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-7xl mx-auto">

        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Team Management</h1>
                <p class="text-gray-500 text-sm mt-1">
                    Total Teams: <span class="font-bold text-gray-900"><?php echo mysqli_num_rows($result); ?></span>
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Team Name</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Members</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Points</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bio</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                
                                <?php 
                                    // ======================================================
                                    // 【核心修改】实时计算队伍总分
                                    // ======================================================
                                    $tid = $row['Team_ID'];
                                    
                                    // 1. 去 user 表找，条件是 Team_ID 等于当前队伍 ID
                                    // 2. 使用 SUM(Point) 把分数列加起来
                                    $sum_sql = "SELECT SUM(Point) as total_points FROM user WHERE Team_ID = '$tid'";
                                    $sum_res = mysqli_query($con, $sum_sql);
                                    $sum_row = mysqli_fetch_assoc($sum_res);
                                    
                                    // 如果没有成员或积分为空，默认为 0
                                    $calculated_points = $sum_row['total_points'] ? $sum_row['total_points'] : 0;
                                ?>

                                <tr class="hover:bg-gray-50 transition">

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        #<?php echo $row['Team_ID']; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded bg-brand-100 text-brand-700 flex-shrink-0 flex items-center justify-center font-bold text-xs mr-3">
                                                <?php echo strtoupper(substr($row['Team_name'], 0, 1)); ?>
                                            </div>
                                            <div class="text-sm font-bold text-gray-900">
                                                <?php echo htmlspecialchars($row['Team_name']); ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">
                                            <?php echo htmlspecialchars($row['Team_code']); ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php if (!empty($row['First_Name'])): ?>
                                            <div class="flex items-center gap-1">
                                                <i class="fa-solid fa-crown text-yellow-500 text-xs"></i>
                                                <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-red-400 italic text-xs">No Owner</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <i class="fa-solid fa-users mr-1 text-gray-400"></i>
                                        <?php echo $row['Total_members']; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-brand-600">
                                        <?php echo number_format($calculated_points); ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">
                                        <?php echo !empty($row['Team_Bio']) ? htmlspecialchars($row['Team_Bio']) : '-'; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="admin_edit_team.php?id=<?php echo $row['Team_ID']; ?>"
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>

                                        <a href="admin_delete_team.php?id=<?php echo $row['Team_ID']; ?>"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('WARNING: Deleting a team will remove all members from it. Are you sure?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No teams found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. And you are in Admin page</p>
    </div>
</footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>