<?php
// 1. 开启 Session
session_start();

// 2. 引入依赖
require_once 'database.php';
require_once 'config_google.php';
require_once 'mail_config.php'; // 【新增】引入发邮件功能

// 3. 检查是否有 Code 返回
if (isset($_GET['code'])) {
    
    try {
        // --- A. 获取 Google 用户信息 ---
        
        // 临时跳过 SSL 验证 (本地开发用)
        $client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

        // 用 Code 换 Token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception("Google Login Error: " . $token['error']);
        }

        $client->setAccessToken($token['access_token']);

        // 获取详细资料
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        // 准备数据
        $email     = mysqli_real_escape_string($con, $google_account_info->email);
        $firstname = mysqli_real_escape_string($con, $google_account_info->givenName);
        $lastname  = mysqli_real_escape_string($con, $google_account_info->familyName);
        $avatar    = mysqli_real_escape_string($con, $google_account_info->picture);

        // --- B. 数据库逻辑 (确保用户存在于数据库) ---

        $target_user_id = 0; // 用于记录最终的用户ID

        // 查库
        $check_sql = "SELECT * FROM user WHERE Email = '$email'";
        $result = mysqli_query($con, $check_sql);

        if (mysqli_num_rows($result) > 0) {
            // --- 情况 1: 老用户 ---
            $user = mysqli_fetch_assoc($result);
            $target_user_id = $user['User_ID'];

            // (可选) 可以在这里更新一下头像，保证最新
            // mysqli_query($con, "UPDATE user SET Avatar='$avatar' WHERE User_ID='$target_user_id'");

        } else {
            // --- 情况 2: 新用户 (自动注册) ---
            $now = date("Y-m-d H:i:s");
            $random_password = md5(uniqid(rand(), true)); 

            $insert_sql = "INSERT INTO user (First_Name, Last_Name, Email, Password, Register_Date, Role, Point, Account_Status, Avatar) 
                           VALUES ('$firstname', '$lastname', '$email', '$random_password', '$now', 0, 0, 'Active', '$avatar')";
            
            if (mysqli_query($con, $insert_sql)) {
                $target_user_id = mysqli_insert_id($con);
            } else {
                throw new Exception("Registration Database Error: " . mysqli_error($con));
            }
        }

        // ============================================================
        // 🛑 核心修改：不再直接设置 Session 登录，而是转入 OTP 流程
        // ============================================================

        // 1. 生成 6 位随机验证码
        $otp = rand(100000, 999999);

        // 2. 存入临时 Session (这部分逻辑和 login.php 一模一样)
        $_SESSION['temp_otp'] = $otp;
        $_SESSION['temp_otp_expiry'] = time() + 300; // 5分钟有效期
        $_SESSION['temp_user_id'] = $target_user_id; // 关键：告诉 verify 页面要验谁
        $_SESSION['temp_email'] = $email;
        
        // 3. 发送邮件
        if (sendOTPEmail($email, $otp)) {
            // 4. 跳转到统一的验证页面
            header("Location: otp_verify.php");
            exit();
        } else {
            throw new Exception("Failed to send verification email.");
        }

    } catch (Exception $e) {
        die("<div style='color:red; padding:20px;'>
                <h2>登录发生错误</h2>
                <p>" . $e->getMessage() . "</p>
                <p><a href='login.php'>返回登录页</a></p>
             </div>");
    }

} else {
    header("Location: login.php");
    exit();
}
?>