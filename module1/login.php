<?php
// 确保 Session 开启（虽然 index.php 开过了，加个保险）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('database.php');
require_once('mail_config.php'); // 引入发邮件功能 (确保你创建了这个文件)

$error_msg = "";

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {

    $email = mysqli_real_escape_string($con, stripslashes($_POST['email']));
    $password = mysqli_real_escape_string($con, stripslashes($_POST['password']));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        // 验证账号密码
        $query = "SELECT * FROM `user` WHERE `Email`='$email' AND `Password`='" . md5($password) . "'";
        $result = mysqli_query($con, $query) or die(mysqli_error($con));

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // =================================================
            // 🛑 核心修改：密码正确，不直接登录，改为发送 OTP
            // =================================================

            // 1. 生成 6 位随机验证码
            $otp = rand(100000, 999999);

            // 2. 存入临时 Session (5分钟有效)
            $_SESSION['temp_otp'] = $otp;
            $_SESSION['temp_otp_expiry'] = time() + 300;
            $_SESSION['temp_user_id'] = $row['User_ID']; // 记住是谁
            $_SESSION['temp_email'] = $row['Email'];     // 用于显示

            // 3. 发送邮件
            if (sendOTPEmail($email, $otp)) {
                // 发送成功，跳转到输入验证码页面
                echo "<script>window.location.href = 'otp_verify.php';</script>";
                exit();
            } else {
                $error_msg = "Failed to send verification email. Please try again.";
            }

        } else {
            $error_msg = "Incorrect email or password.";
        }
    }
}
?>

<div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 relative z-10">

    <div class="h-2 bg-gradient-to-r from-brand-500 to-green-600 w-full"></div>

    <div class="px-8 py-10">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand-50 mb-4">
                <i class="fa-solid fa-leaf text-brand-600 text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
            <p class="text-sm text-gray-500 mt-1">Sign in to continue</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md animate-pulse">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-medium"><?php echo $error_msg; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="space-y-6">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-envelope text-gray-400"></i>
                    </div>
                    <input type="email" name="email" id="email" required
                        class="pl-10 block w-full rounded-lg border-gray-300 border bg-gray-50 py-2.5 text-gray-900 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition duration-200 outline-none"
                        placeholder="you@example.com">
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                </div>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" name="password" id="password" required
                        class="pl-10 block w-full rounded-lg border-gray-300 border bg-gray-50 py-2.5 text-gray-900 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition duration-200 outline-none"
                        placeholder="••••••••">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <button type="submit" name="submit"
                    class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all duration-200">
                    Sign In
                </button>

                <a href="google_login.php"
                    class="w-full flex justify-center items-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all duration-200">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4" />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853" />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.84z"
                            fill="#FBBC05" />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335" />
                    </svg>
                    Google
                </a>
            </div>

        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                New to ecoTrip?
                <a href="signup.php"
                    class="font-semibold text-brand-600 hover:text-brand-500 hover:underline transition">
                    Create an account
                </a>
            </p>
        </div>

        <div class="space-y-4">


            <div>
                <div class="mt-3 flex justify-end">
                    <a href="forgot_password.php"
                        class="text-xs font-semibold text-green-600 hover:text-green-700 transition-colors">
                        Forgot Password?
                    </a>
                </div>

            </div>


        </div>
    </div>
</div>