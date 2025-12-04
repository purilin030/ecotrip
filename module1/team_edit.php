<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. 获取 Team
$user_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
$user_res = mysqli_query($con, $user_sql);
$user_data = mysqli_fetch_assoc($user_res);
$my_team_id = $user_data['Team_ID'];

if ($my_team_id == NULL || $my_team_id == 0) {
    header("Location: team.php");
    exit();
}

// 3. 获取 Team Info
$team_sql = "SELECT * FROM team WHERE Team_ID = '$my_team_id'";
$team_res = mysqli_query($con, $team_sql);
$my_team = mysqli_fetch_assoc($team_res);

// 4. 权限检查
if ($my_team['Owner_ID'] != $user_id) {
    $_SESSION['flash_error'] = "Only the Team Owner can edit the team.";
    header("Location: team_information.php");
    exit();
}

// 5. 表单提交 (更新 Name 和 Bio)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_team'])) {
    $new_name = mysqli_real_escape_string($con, trim($_POST['team_name']));
    $new_bio = mysqli_real_escape_string($con, trim($_POST['team_bio'])); // 新增 Bio

    if (!empty($new_name)) {
        // 检查重名 (排除自己)
        $check_sql = "SELECT Team_ID FROM team WHERE Team_name = '$new_name' AND Team_ID != '$my_team_id'";
        $check_res = mysqli_query($con, $check_sql);
        if (mysqli_num_rows($check_res) > 0) {
            $error_msg = "Team name '$new_name' is already taken.";
        } else {
            // 更新 Name 和 Bio
            $update_sql = "UPDATE team SET Team_name = '$new_name', Team_Bio = '$new_bio' WHERE Team_ID = '$my_team_id'";
            if (mysqli_query($con, $update_sql)) {
                $_SESSION['flash_success'] = "Team updated successfully!";
                header("Location: team_information.php");
                exit();
            }
        }
    } else {
        $error_msg = "Team name cannot be empty.";
    }
}

// 6. 获取队友列表
$members_sql = "SELECT User_ID, First_Name, Last_Name, Avatar, Email FROM user WHERE Team_ID = '$my_team_id'";
$members_res = mysqli_query($con, $members_sql);

$page_title = "Manage Team - ecoTrip";
include '../header.php';
?>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Manage Team</h1>
            <a href="team_information.php" class="text-sm text-gray-500 hover:text-gray-900">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <?php echo $_SESSION['flash_error'];
                unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="mb-10">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="h-32 bg-gradient-to-r from-brand-500 to-green-400 relative">
                    <div class="absolute -bottom-10 left-8">
                        <div class="h-24 w-24 rounded-xl bg-white p-2 shadow-lg">
                            <div
                                class="h-full w-full bg-brand-50 rounded-lg flex items-center justify-center text-4xl font-bold text-brand-600">
                                <?php echo strtoupper(substr($my_team['Team_name'], 0, 1)); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-12 pb-8 px-8">
                    <div class="flex flex-col gap-6">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                Team Name <i class="fa-regular fa-pen-to-square text-gray-400"></i>
                            </label>
                            <input type="text" name="team_name" required
                                value="<?php echo htmlspecialchars($my_team['Team_name']); ?>"
                                class="text-2xl font-bold text-gray-900 border-b-2 border-gray-300 focus:border-brand-600 focus:outline-none w-full py-2 bg-transparent placeholder-gray-400 transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                Team Bios <i class="fa-regular fa-pen-to-square text-gray-400"></i>
                            </label>
                            <textarea name="team_bio" rows="2" maxlength="255"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Description to your team"><?php echo htmlspecialchars($my_team['Team_Bio'] ?? ''); ?></textarea>
                            <p class="text-xs text-gray-400 mt-1 text-right">Max 255 characters</p>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                                <div>
                                    <h4 class="text-blue-900 font-bold text-sm">Invite New Members</h4>
                                    <p class="text-blue-700 text-xs mt-1">Share this code with your friends.</p>
                                </div>
                                <div class="flex items-center gap-2 bg-white px-3 py-2 rounded border border-blue-200">
                                    <span
                                        class="font-mono font-bold text-lg text-blue-800 tracking-widest"><?php echo htmlspecialchars($my_team['Team_code']); ?></span>
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('<?php echo $my_team['Team_code']; ?>'); alert('Code copied!');"
                                        class="text-gray-400 hover:text-brand-600 ml-2">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-4">
                        <button type="submit" name="save_team"
                            class="bg-brand-600 text-white font-bold py-2 px-6 rounded hover:bg-brand-700 transition duration-300 shadow-sm">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900">Manage Members</h2>
            <span class="text-sm text-gray-500"><?php echo $my_team['Total_members']; ?> members</span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="divide-y divide-gray-100">
                <?php while ($member = mysqli_fetch_assoc($members_res)): ?>
                    <?php
                    $is_me = ($member['User_ID'] == $user_id);
                    $mem_avatar = "https://ui-avatars.com/api/?name=" . $member['First_Name'] . "+" . $member['Last_Name'] . "&background=random&color=fff";
                    // 拼接物理路径进行检查
                    $phys_path = $_SERVER['DOCUMENT_ROOT'] . $member['Avatar'];

                    if (!empty($member['Avatar']) && file_exists($phys_path)) {
                        // 检查通过，使用数据库里的 Web 路径显示
                        $mem_avatar = $member['Avatar'];
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
                                    <?php echo htmlspecialchars($member['First_Name'] . ' ' . $member['Last_Name']); ?>
                                    <?php if ($is_me): ?>
                                        <span class="bg-brand-100 text-brand-700 text-[10px] px-2 py-0.5 rounded-full">You
                                            (Owner)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($member['Email']); ?></p>
                            </div>
                        </div>
                        <?php if (!$is_me): ?>
                            <div class="flex items-center gap-2">
                                <form action="team_manage_action.php" method="POST"
                                    onsubmit="return confirm('Promote this member to OWNER?');">
                                    <input type="hidden" name="target_user_id" value="<?php echo $member['User_ID']; ?>">
                                    <input type="hidden" name="action_type" value="transfer">
                                    <button type="submit"
                                        class="text-xs font-bold text-gray-600 border border-gray-300 bg-white hover:bg-gray-50 px-3 py-1.5 rounded transition">Make
                                        Owner</button>
                                </form>
                                <form action="team_manage_action.php" method="POST"
                                    onsubmit="return confirm('REMOVE this member?');">
                                    <input type="hidden" name="target_user_id" value="<?php echo $member['User_ID']; ?>">
                                    <input type="hidden" name="action_type" value="kick">
                                    <button type="submit"
                                        class="text-xs font-bold text-red-600 border border-red-200 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition">Remove</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
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