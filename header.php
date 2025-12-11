<?php
// 1. 确保 Session 开启
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 只有在非公开页面才强制检查登录
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['index.php', 'signup.php', 'home.php'];

if (!in_array($current_page, $public_pages)) {
    if (!isset($_SESSION['Firstname'])) {
        header("Location: /ecotrip/module1/index.php");
        exit();
    }
}

// 3. Global avatar logic
$display_avatar = "https://ui-avatars.com/api/?name=Guest&background=f3f4f6&color=6b7280";
$user_name_display = "Guest";
$user_role_display = "Visitor";
$role_badge_class = "bg-gray-100 text-gray-500";

// 初始化角色
$db_role = 0;

if (isset($_SESSION['Firstname'])) {
    $display_avatar = "https://ui-avatars.com/api/?name=" . $_SESSION['Firstname'] . "+" . $_SESSION['Lastname'] . "&background=0D8ABC&color=fff&size=128";
    $user_name_display = $_SESSION['Firstname'] . " " . $_SESSION['Lastname'];
    $user_role_display = "Member";
    $role_badge_class = "bg-green-500 text-white";

    if (isset($con) && isset($_SESSION['user_id'])) {
        $uid_h = $_SESSION['user_id'];
        $h_sql = "SELECT Avatar, Role FROM user WHERE User_ID = '$uid_h'";
        $h_res = mysqli_query($con, $h_sql);
        if ($h_res) {
            $h_row = mysqli_fetch_assoc($h_res);

            // 检查数据库字段不为空
            if (!empty($h_row['Avatar'])) {
                // 拼接物理路径用于检查：C:/xampp/htdocs + /ecotrip/avatars/xxx.jpg
                $physical_path = $_SERVER['DOCUMENT_ROOT'] . $h_row['Avatar'];

                if (file_exists($physical_path)) {
                    // 如果文件确实存在，就使用数据库里的 Web 路径
                    $display_avatar = $h_row['Avatar'];
                }
            }

            $db_role = $h_row['Role'];

            if ($db_role == 1) {
                $user_role_display = "Admin";
                $role_badge_class = "bg-red-900 text-white";
            } elseif ($db_role == 2) {
                $user_role_display = "Team Owner";
                $role_badge_class = "bg-blue-500 text-white";
            } else {
                $user_role_display = "Member";
                $role_badge_class = "bg-green-500 text-white";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ecoTrip'; ?></title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <?php if (isset($extra_css))
        echo $extra_css; ?>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { brand: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 900: '#14532d' } }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="w-full px-8">
            <div class="flex justify-between h-16">

                <div class="flex items-center">
                    <a href="../module1/home.php" class="flex-shrink-0 flex items-center gap-2 navbar-brand">
                        <i class="fa-solid fa-leaf text-brand-600 text-2xl"></i>
                        <span class="font-bold text-xl tracking-tight text-gray-900">ecoTrip</span>
                    </a>

                    <div class="hidden md:ml-10 md:flex md:space-x-8 h-full">

                        <div class="relative group h-full flex items-center">
                            <a href="/ecotrip/module2/view_challenge.php"
                                class="nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300 h-full focus:outline-none">
                                Challenges
                                <?php if ($db_role == 1): ?>
                                    <i
                                        class="fa-solid fa-chevron-down ml-1.5 text-xs text-gray-400 group-hover:text-gray-600 transition-transform group-hover:rotate-180"></i>
                                <?php endif; ?>
                            </a>

                            <?php if ($db_role == 1 ): ?>
                                <div
                                    class="absolute top-full left-0 mt-0 w-48 bg-white border border-gray-200 rounded-lg shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform origin-top-left z-50">
                                    <div class="py-2">
                                        <a href="/ecotrip/module2/manage_challenge.php"
                                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                                            <i class="fa-solid fa-list-check mr-2 text-gray-400"></i> Manage Challenge
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <a href="../module4/Leaderboard.php"
                            class="nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300 h-full">Leaderboard</a>

                        <div class="relative group h-full flex items-center">
    <a href="../module4/Marketplace.php"
        class="nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300 h-full focus:outline-none">
        Marketplace
        <?php if ($db_role == 1): ?>
            <i class="fa-solid fa-chevron-down ml-1.5 text-xs text-gray-400 group-hover:text-gray-600 transition-transform group-hover:rotate-180"></i>
        <?php endif; ?>
    </a>
    
    


    <?php if ($db_role == 1): ?>
        <div class="absolute top-full left-0 mt-0 w-56 bg-white border border-gray-200 rounded-lg shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform origin-top-left z-50">
            <div class="py-2">
                
                <div class="border-b border-gray-100 pb-1 mb-1">
                    <p class="px-4 py-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        Store Manager
                    </p>
                </div>

                <a href="/ecotrip/module4/Inventory.php"
                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                    <i class="fa-solid fa-boxes-stacked mr-2 text-blue-400"></i> Inventory
                </a>

                <a href="/ecotrip/module4/Redemption_List.php"
                    class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                    <i class="fa-solid fa-clipboard-check mr-2 text-green-400"></i> Redemption Requests
                </a>

            </div>
        </div>
    <?php endif; ?>
</div>
        <div class="relative group h-full flex items-center">
            <a href="../module4/Donations.php"
                            class="nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300 h-full">Donations</a>
        </div>
            
                        <div class="relative group h-full flex items-center">
                            <a href="/ecotrip/module3/submission_list.php"
                                class="nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300 h-full focus:outline-none">
                                Submission
                                <?php if ($db_role == 1): ?>
                                    <i
                                        class="fa-solid fa-chevron-down ml-1.5 text-xs text-gray-400 group-hover:text-gray-600 transition-transform group-hover:rotate-180"></i>
                                <?php endif; ?>
                            </a>

                            <?php if ($db_role == 1): ?>
                                <div
                                    class="absolute top-full left-0 mt-0 w-48 bg-white border border-gray-200 rounded-lg shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform origin-top-left z-50">
                                    <div class="py-2">
                                        <a href="/ecotrip/module3/admin_verification_list.php"
                                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                                            <i class="fa-solid fa-list-check mr-2 text-gray-400"></i> Verification Queue
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>


                        <?php
                        $is_team_page = ($current_page == 'team.php' || $current_page == 'team_information.php' || $current_page == 'create_team_action.php' || $current_page == 'team_edit.php' || $current_page == 'leaveteam.php');

                        if ($is_team_page) {
                            $team_class = "nav-custom-link text-brand-600 border-b-2 border-brand-600 px-1 pt-1 text-sm font-medium inline-flex items-center";
                        } else {
                            $team_class = "nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300";
                        }
                        ?>

                        <div class="relative group h-full flex items-center">
                            <a href="/ecotrip/module1/team.php"
                                class="<?php echo $team_class; ?> h-full focus:outline-none">
                                Team
                                <?php if ($db_role == 1): ?>
                                    <i
                                        class="fa-solid fa-chevron-down ml-1.5 text-xs text-gray-400 group-hover:text-gray-600 transition-transform group-hover:rotate-180"></i>
                                <?php endif; ?>
                            </a>

                            

                            <?php if ($db_role == 1): ?>
                                <div
                                    class="absolute top-full left-0 mt-0 w-48 bg-white border border-gray-200 rounded-lg shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform origin-top-left z-50">
                                    <div class="py-2">
                                        <div class="border-b border-gray-100 pb-1 mb-1">
                                            <p class="px-4 py-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Admin</p>
                                        </div>
                                        <a href="/ecotrip/module1/user_list.php"
                                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                                            <i class="fa-solid fa-users mr-2 text-gray-400"></i> User List
                                        </a>
                                        <a href="/ecotrip/module1/team_list.php"
                                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                                            <i class="fa-solid fa-people-group mr-2 text-gray-400"></i> Team List
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        

                        <?php 
                        // 1. 设置默认链接：所有人默认去 User Dashboard
                        $dashboard_main_url = "/ecotrip/module5/dashboard_user.php"; 
                        
                        // 2. 如果是管理员，点击标题默认去 Admin Dashboard
                        if ($db_role == 1) {
                            $dashboard_main_url = "/ecotrip/module5/dashboard_admin.php";
                        }
                        ?>

                        <div class="relative group h-full flex items-center">
                            
                            <a href="<?php echo $dashboard_main_url; ?>"
                                class="nav-custom-link text-gray-500 hover:text-gray-900 px-1 pt-1 text-sm font-medium inline-flex items-center border-b-2 border-transparent hover:border-gray-300 h-full focus:outline-none">
                                Dashboard
                                <?php if ($db_role == 1): ?>
                                    <i class="fa-solid fa-chevron-down ml-1.5 text-xs text-gray-400 group-hover:text-gray-600 transition-transform group-hover:rotate-180"></i>
                                <?php endif; ?>
                            </a>

                            <?php if ($db_role == 1): ?>
                                <div class="absolute top-full right-0 mt-0 w-48 bg-white border border-gray-200 rounded-lg shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform origin-top-right z-50">
                                    <div class="py-2">
                                        <a href="/ecotrip/module5/dashboard_user.php"
                                            class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition">
                                            <i class="fa-solid fa-user-tag mr-2 text-gray-400"></i> User Dashboard
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-end mr-3">
                        <span class="text-sm font-bold text-gray-900"><?php echo $user_name_display; ?></span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded <?php echo $role_badge_class; ?>">
                            <?php echo $user_role_display; ?>
                        </span>
                    </div>

                    <div class="h-10 w-10 rounded-full bg-gray-200 overflow-hidden border-2 border-white shadow-sm">
                        <a href="/ecotrip/module1/profile.php">
                            <img src="<?php echo $display_avatar; ?>" alt="User Avatar"
                                class="h-full w-full object-cover">
                        </a>
                    </div>

                    <?php if (isset($_SESSION['Firstname'])): ?>
                        <a href="/ecotrip/module1/logout.php"
                            class="text-sm text-red-500 hover:text-red-700 font-bold ml-2">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>