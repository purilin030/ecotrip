<?php
session_start();
require 'database.php'; // 注意：这里原代码是 require 'database.php'，如果 admin 文件在子目录，可能需要 require '../database.php'。我保持你原文件的写法。

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
    header("Location: index.php");
    exit();
}

// 2. 获取目标 ID
if (!isset($_GET['id'])) {
    header("Location: team_list.php");
    exit();
}
$target_team_id = intval($_GET['id']);

// 3. 处理保存逻辑 (更新基本信息)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_team'])) {
    $team_name = mysqli_real_escape_string($con, $_POST['team_name']);
    $team_bio = mysqli_real_escape_string($con, $_POST['team_bio']);
    $team_points = intval($_POST['team_points']);
    $owner_id = intval($_POST['owner_id']);

    // 简单的 Owner 检查
    $check_owner = mysqli_query($con, "SELECT User_ID FROM user WHERE User_ID = '$owner_id'");
    if (mysqli_num_rows($check_owner) == 0 && $owner_id != 0) {
        $error_msg = "Error: The Owner ID ($owner_id) does not exist.";
    } else {
        $update_sql = "UPDATE team SET 
                       Team_name='$team_name', 
                       Team_Bio='$team_bio', 
                       Owner_ID='$owner_id' 
                       WHERE Team_ID='$target_team_id'";

        if (mysqli_query($con, $update_sql)) {
            // 如果换了队长，同步更新 user 表
            if ($owner_id != 0) {
                mysqli_query($con, "UPDATE user SET Team_ID='$target_team_id' WHERE User_ID='$owner_id'");
            }
            echo "<script>alert('Team updated successfully!'); window.location.href='team_list.php';</script>";
            exit();
        } else {
            $error_msg = "Error updating: " . mysqli_error($con);
        }
    }
}

// 4. 读取当前队伍数据
$sql = "SELECT * FROM team WHERE Team_ID = '$target_team_id'";

// 5. 实时计算队伍总分 (从 user 表累加)
$sum_sql = "SELECT SUM(Point) as total_points FROM user WHERE Team_ID = '$target_team_id'";
$sum_res = mysqli_query($con, $sum_sql);
$sum_row = mysqli_fetch_assoc($sum_res);
$real_team_points = $sum_row['total_points'] ? $sum_row['total_points'] : 0;
$result = mysqli_query($con, $sql);
$team = mysqli_fetch_assoc($result);

if (!$team) {
    echo "Team not found.";
    exit();
}

// 6. 读取该队伍的所有成员
$members_sql = "SELECT User_ID, First_Name, Last_Name, Email, Avatar FROM user WHERE Team_ID = '$target_team_id'";
$members_res = mysqli_query($con, $members_sql);

$page_title = "Edit Team #" . $team['Team_ID'];
include '../header.php';
?>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-4xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Edit Team #<?php echo $team['Team_ID']; ?></h1>
            <a href="team_list.php" class="text-sm text-gray-500 hover:text-gray-900">Back to List</a>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 mb-8">
            <form action="" method="POST">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Team Name</label>
                        <input type="text" name="team_name" value="<?php echo htmlspecialchars($team['Team_name']); ?>"
                            required class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Team Bio</label>
                        <textarea name="team_bio" rows="3"
                            class="w-full mt-1 border-gray-300 rounded-md shadow-sm border p-2"><?php echo htmlspecialchars($team['Team_Bio']); ?></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase">Team Points
                                    (Calculated)</label>
                                <input type="number" value="<?php echo $real_team_points; ?>" disabled
                                    class="w-full mt-1 border-gray-200 bg-gray-100 text-gray-500 rounded-md shadow-sm border p-2 cursor-not-allowed">
                                <p class="text-[10px] text-gray-400 mt-1">Sum of all members' points.</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase">Owner User ID</label>
                            <input type="number" name="owner_id" value="<?php echo $team['Owner_ID']; ?>" required
                                class="w-full mt-1 border-gray-300 rounded-md border p-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-400">Team Code (Read-only)</label>
                        <input type="text" value="<?php echo htmlspecialchars($team['Team_code']); ?>" disabled
                            class="w-full mt-1 border-gray-200 bg-gray-100 text-gray-500 rounded-md shadow-sm border p-2 cursor-not-allowed">
                    </div>
                    <div class="pt-4 flex items-center justify-end gap-3">
                        <button type="submit" name="update_team"
                            class="px-8 py-2.5 bg-blue-600 text-white rounded-md font-bold hover:bg-blue-700 shadow-md transition">
                            Save Info
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <h2 class="text-xl font-bold text-gray-900 mb-4">Manage Team Members</h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="divide-y divide-gray-100">
                <?php if (mysqli_num_rows($members_res) > 0): ?>
                    <?php while ($member = mysqli_fetch_assoc($members_res)): ?>
                        <?php
                        $is_owner = ($member['User_ID'] == $team['Owner_ID']);

                        // === 🔥 头像逻辑修正 ===
                        $fullName = $member['First_Name'] . " " . $member['Last_Name'];
                        $mem_avatar = "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=random&color=fff";
                        
                        if (!empty($member['Avatar'])) {
                            // 强制统一使用 /ecotrip/avatars/ 路径，并使用 basename 避免路径重复
                            $mem_avatar = "/ecotrip/avatars/" . basename($member['Avatar']);
                        }
                        ?>

                        <div
                            class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50 transition">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-gray-200 overflow-hidden border border-gray-100">
                                    <img src="<?php echo $mem_avatar; ?>" class="h-full w-full object-cover">
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                        <?php echo htmlspecialchars($fullName); ?>
                                        <span class="text-xs text-gray-400 font-normal">(ID:
                                            <?php echo $member['User_ID']; ?>)</span>
                                        <?php if ($is_owner): ?>
                                            <span
                                                class="bg-yellow-100 text-yellow-700 text-[10px] px-2 py-0.5 rounded-full font-bold">OWNER</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($member['Email']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <?php if (!$is_owner): ?>
                                    <form action="admin_team_manage_action.php" method="POST"
                                        onsubmit="return confirm('Promote this user to Team Owner?');">
                                        <input type="hidden" name="target_user_id" value="<?php echo $member['User_ID']; ?>">
                                        <input type="hidden" name="team_id" value="<?php echo $target_team_id; ?>">
                                        <input type="hidden" name="action_type" value="transfer">
                                        <button type="submit"
                                            class="text-xs font-bold text-gray-600 border border-gray-300 bg-white hover:bg-gray-50 px-3 py-1.5 rounded transition">
                                            Make Owner
                                        </button>
                                    </form>

                                    <form action="admin_team_manage_action.php" method="POST"
                                        onsubmit="return confirm('Remove this user from the team?');">
                                        <input type="hidden" name="target_user_id" value="<?php echo $member['User_ID']; ?>">
                                        <input type="hidden" name="team_id" value="<?php echo $target_team_id; ?>">
                                        <input type="hidden" name="action_type" value="kick">
                                        <button type="submit"
                                            class="text-xs font-bold text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition">
                                            Kick
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic pr-2">Cannot kick owner</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-500">No members in this team.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc.</p>
    </div>
</footer>
</body>

</html>