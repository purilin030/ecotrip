<?php
// 1. 开启 Session
session_start();

// 2. 引入依赖
require_once 'database.php';
require_once 'config_google.php';

// 3. 检查是否有 Code 返回
if (isset($_GET['code'])) {
    
    try {
        // --- A. 获取 Google 用户信息 ---
        
        // 临时跳过 SSL 验证
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

        // --- B. 数据库逻辑 ---

        // 查库：用户是否存在？
        $check_sql = "SELECT * FROM user WHERE Email = '$email'";
        $result = mysqli_query($con, $check_sql);

        if (mysqli_num_rows($result) > 0) {
            // ================================================
            // 情况 1: 老用户 -> 直接登录
            // ================================================
            $user = mysqli_fetch_assoc($result);

            // 设置 Session
            $_SESSION['user_id']   = $user['User_ID'];
            $_SESSION['Firstname'] = $user['First_Name'];
            $_SESSION['Lastname']  = $user['Last_Name'];
            $_SESSION['Email']     = $user['Email'];
            
            // 【关键点 1】老用户：从数据库读取头像存入 Session
            // 这样能保留用户自己在网站上更改过的头像
            $_SESSION['Avatar']    = $user['Avatar']; 

        } else {
            // ================================================
            // 情况 2: 新用户 -> 自动注册
            // ================================================
            $now = date("Y-m-d H:i:s");
            $random_password = md5(uniqid(rand(), true)); 

            // 插入数据
            $insert_sql = "INSERT INTO user (First_Name, Last_Name, Email, Password, Register_Date, Role, Point, Account_Status, Avatar) 
                           VALUES ('$firstname', '$lastname', '$email', '$random_password', '$now', 0, 0, 'Active', '$avatar')";
            
            if (mysqli_query($con, $insert_sql)) {
                $new_uid = mysqli_insert_id($con);

                // 设置 Session
                $_SESSION['user_id']   = $new_uid;
                $_SESSION['Firstname'] = $firstname;
                $_SESSION['Lastname']  = $lastname;
                $_SESSION['Email']     = $email;

                // 【关键点 2】新用户：把刚才从 Google 拿到的头像存入 Session
                // 之前就是少了这一行，导致注册后第一眼看到的是默认头像！
                $_SESSION['Avatar']    = $avatar;
                
            } else {
                throw new Exception("Registration Database Error: " . mysqli_error($con));
            }
        }

        // --- C. 跳转 ---
        header("Location: home.php");
        exit();

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