<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. 查询用户信息
$sql = "SELECT u.*, t.Team_name 
        FROM user u 
        LEFT JOIN team t ON u.Team_ID = t.Team_ID 
        WHERE u.User_ID = '$user_id'";

$result = mysqli_query($con, $sql);
$user_info = mysqli_fetch_assoc($result);

// 3. 处理数据显示逻辑
$dob_display = !empty($user_info['User_DOB']) ? $user_info['User_DOB'] : '<span class="text-gray-400 italic">N/A</span>';
$team_display = !empty($user_info['Team_name']) ? $user_info['Team_name'] : '<span class="text-gray-400 italic">No Team joined</span>';

// Detect role and change 
$role_code = $user_info['Role'];
if ($role_code == 1) {
    $role_display = "Admin";
    $role_badge_color = "bg-red-900 text-white"; // 可选：给不同角色不同颜色
} elseif ($role_code == 2) {
    $role_display = "Moderator";
    $role_badge_color = "bg-blue-900 text-white";
} else {
    $role_display = "Member";
    $role_badge_color = "bg-green-500 text-white"; // 默认颜色
}

// 设置页面标题，并引入 Header (Header 会自动处理 HTML 头部、Tailwind、导航栏和头像)
$page_title = "User Profile - ecoTrip";
include '../header.php';
?>

<main class="flex-grow w-full px-8 py-12">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Profile</h1>
        <p class="text-gray-500 mb-8">Manage your account settings and preferences.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

            <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                <div class="flex flex-col items-center mb-8 border-b border-gray-100 pb-6">
                    <div
                        class="h-32 w-32 rounded-full bg-gray-200 overflow-hidden border-4 border-brand-100 shadow-md group relative">
                        <img src="<?php echo $display_avatar; ?>" alt="User Avatar" class="h-full w-full object-cover">
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-gray-900">
                        <?php echo $user_info['First_Name'] . " " . $user_info['Last_Name']; ?>
                    </h3>
                    <p class="text-sm text-gray-500"><?php echo $user_info['Email']; ?></p>
                </div>

                <h4 class="font-bold text-gray-700 mb-4">Change Avatar</h4>
                <?php include 'submission_form.php'; ?>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Personal Information</h2>
                    <a href="edit_profile.php" class="text-sm text-brand-600 font-semibold hover:underline">Edit
                        &#9998;</a>
                </div>

                <div class="space-y-6">

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <?php echo htmlspecialchars($user_info['First_Name'] . " " . $user_info['Last_Name']); ?>
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <?php echo $dob_display; ?>
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2 break-all">
                            <?php echo htmlspecialchars($user_info['Email']); ?>
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <?php echo !empty($user_info['Phone_num']) ? htmlspecialchars($user_info['Phone_num']) : '<span class="text-gray-400">N/A</span>'; ?>
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Current Team</dt>
                        <dd class="text-sm font-bold text-brand-600 col-span-2">
                            <?php echo $team_display; ?>
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Total Points</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <?php echo number_format($user_info['Point']); ?> pts
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Redeem Points</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <?php echo number_format($user_info['RedeemPoint']); ?> pts
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <dt class="text-sm font-medium text-gray-500">Joined</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <?php echo htmlspecialchars($user_info['Register_Date']); ?>
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Account Role</dt>
                        <dd class="text-sm font-semibold text-gray-900 col-span-2">
                            <span
                                class="<?php echo $role_badge_color; ?> py-1 px-3 rounded-md text-xs uppercase tracking-wide">
                                <?php echo $role_display; ?>
                            </span>
                        </dd>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>

<footer class="bg-white border-t border-gray-200">
    <div class="w-full py-8 px-8">
        <p class="text-center text-sm text-gray-400">
            &copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.
        </p>
    </div>
</footer>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>

</html>