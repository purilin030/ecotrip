<?php
session_start();
require 'database.php';

// 1. 检查登录状态
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. 获取当前用户的 Team_ID
$user_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
$user_res = mysqli_query($con, $user_sql);
$user_data = mysqli_fetch_assoc($user_res);
$my_team_id = $user_data['Team_ID'];

// 3. 如果用户没有团队，跳转回 team.php
if ($my_team_id == NULL || $my_team_id == 0) {
    header("Location: team.php");
    exit();
}

// 4. 获取队伍基本信息
$team_sql = "SELECT * FROM team WHERE Team_ID = '$my_team_id'";
$team_res = mysqli_query($con, $team_sql);
$my_team = mysqli_fetch_assoc($team_res);

// ==========================================
// 5. 【关键修改】计算团队总分
// 逻辑：直接将该团队下所有用户的 Point 加起来
// ==========================================
$points_sql = "SELECT SUM(Point) as total_points FROM user WHERE Team_ID = '$my_team_id'";
$points_res = mysqli_query($con, $points_sql);
$points_data = mysqli_fetch_assoc($points_res);

// 如果结果为NULL（比如没人有分），就默认为0
$team_total_points = $points_data['total_points'] ? $points_data['total_points'] : 0;


// 6. 获取队友列表
$members_sql = "SELECT User_ID, First_Name, Last_Name, Avatar, Role FROM user WHERE Team_ID = '$my_team_id'";
$members_res = mysqli_query($con, $members_sql);

// 设置标题并引入 Header
$page_title = htmlspecialchars($my_team['Team_name']) . " - My Team";
include '../header.php';
?>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-5xl mx-auto">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="h-32 bg-gradient-to-r from-brand-500 to-green-400 relative">
                <div class="absolute -bottom-10 left-8">
                    <div class="h-24 w-24 rounded-xl bg-white p-2 shadow-lg">
                        <div class="h-full w-full bg-brand-50 rounded-lg flex items-center justify-center text-4xl font-bold text-brand-600">
                            <?php echo strtoupper(substr($my_team['Team_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>

                <?php if ($my_team['Owner_ID'] == $_SESSION['user_id']): ?>
                    <div class="absolute top-4 right-4">
                        <a href="team_edit.php"
                            class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-3 py-1.5 rounded-lg text-sm font-semibold border border-white/40 transition flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Team
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pt-12 pb-8 px-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($my_team['Team_name']); ?>
                        </h1>
                        <p class="text-gray-500 mt-1">
                            <i class="fa-solid fa-users mr-1"></i> <?php echo $my_team['Total_members']; ?> Members
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                        <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Team Code</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xl font-mono font-bold text-gray-800 tracking-widest">
                                <?php echo htmlspecialchars($my_team['Team_code']); ?>
                            </span>
                            <button
                                onclick="navigator.clipboard.writeText('<?php echo $my_team['Team_code']; ?>'); alert('Code copied!');"
                                class="text-gray-400 hover:text-brand-600 transition">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">

                    <div class="md:col-span-1 bg-gray-50 rounded-xl p-6 border border-gray-200 flex flex-col justify-center">
                        <div class="text-xs text-gray-400 uppercase font-bold mb-2">Team Bio</div>
                        <?php if (!empty($my_team['Team_Bio'])): ?>
                            <p class="text-sm text-gray-700 italic">"<?php echo htmlspecialchars($my_team['Team_Bio']); ?>"</p>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 italic">No bio yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-brand-50 rounded-xl p-6 border border-brand-100">
                        <div class="text-brand-600 mb-2"><i class="fa-solid fa-trophy text-2xl"></i></div>
                        <div class="text-sm text-brand-800 font-medium">Total Points</div>
                        <div class="text-3xl font-bold text-brand-900">
                            <?php echo number_format($team_total_points); ?>
                        </div>
                    </div>

                    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                        <div class="text-blue-600 mb-2"><i class="fa-solid fa-medal text-2xl"></i></div>
                        <div class="text-sm text-blue-800 font-medium">Current Rank</div>
                        <div class="text-3xl font-bold text-blue-900">#5</div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-900 mb-4">Team Squad</h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="divide-y divide-gray-100">
                <?php while ($member = mysqli_fetch_assoc($members_res)): ?>
                    <?php
                    $is_owner = ($member['User_ID'] == $my_team['Owner_ID']);
                    $member_avatar = "https://ui-avatars.com/api/?name=" . $member['First_Name'] . "+" . $member['Last_Name'] . "&background=random&color=fff";
                    
                    // 修正后的头像路径检查逻辑
                    // 假设数据库存的是 '/ecotrip/avatars/xxx.png' 这种Web路径
                    if (!empty($member['Avatar'])) {
                        // 构建物理路径来检查文件是否存在
                        // 注意：你需要根据你的实际文件结构调整这里的逻辑
                        // 这里尝试去掉开头的 '/ecotrip/' 来拼接物理路径
                        $rel_path = str_replace('/ecotrip/', '', $member['Avatar']);
                        $phys_path = __DIR__ . '/../' . $rel_path; 

                        if (file_exists($phys_path)) {
                            $member_avatar = $member['Avatar'];
                        }
                    }
                    ?>

                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-gray-200 overflow-hidden border border-gray-100">
                                <img src="<?php echo $member_avatar; ?>" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <p class="font-bold text-gray-900 text-sm">
                                    <?php echo htmlspecialchars($member['First_Name'] . ' ' . $member['Last_Name']); ?>
                                    <?php if ($is_owner): ?>
                                        <i class="fa-solid fa-crown text-yellow-500 ml-1" title="Team Owner"></i>
                                    <?php endif; ?>
                                </p>
                                <?php if ($is_owner): ?>
                                    <p class="text-xs text-brand-600 font-bold">Team Owner</p>
                                <?php else: ?>
                                    <p class="text-xs text-gray-500">Member</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($member['User_ID'] == $_SESSION['user_id']): ?>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded font-medium">You</span>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="leaveteam.php" onclick="return confirm('Are you sure you want to leave this team?');"
                class="text-red-500 text-sm hover:text-red-700 font-medium hover:underline">
                Leave Team
            </a>
        </div>

    </div>
</main>

<footer class="bg-white border-t border-gray-200">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p>
    </div>
</footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>