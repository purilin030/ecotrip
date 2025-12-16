<?php
// 1. Start Session
session_start();

// 2. Include dependencies
require_once 'database.php';
require_once 'config_google.php';
require_once 'mail_config.php'; // [new] include email sending helper

// 3. Check if a code was returned
if (isset($_GET['code'])) {
    
    try {
        // --- A. Get Google user information ---
        
        // Temporarily disable SSL verification (for local development)
        $client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

        // Exchange code for token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception("Google Login Error: " . $token['error']);
        }

        $client->setAccessToken($token['access_token']);

        // Retrieve profile details
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        // Prepare data
        $email     = mysqli_real_escape_string($con, $google_account_info->email);
        $firstname = mysqli_real_escape_string($con, $google_account_info->givenName);
        $lastname  = mysqli_real_escape_string($con, $google_account_info->familyName);
        $avatar    = mysqli_real_escape_string($con, $google_account_info->picture);

        // --- B. Database logic (ensure user exists in DB) ---

        $target_user_id = 0; // Will store the final user ID

        // Query the database
        $check_sql = "SELECT * FROM user WHERE Email = '$email'";
        $result = mysqli_query($con, $check_sql);

        if (mysqli_num_rows($result) > 0) {
            // --- Case 1: existing user ---
            $user = mysqli_fetch_assoc($result);
            $target_user_id = $user['User_ID'];

            // (Optional) Update avatar here to keep it current
            // mysqli_query($con, "UPDATE user SET Avatar='$avatar' WHERE User_ID='$target_user_id'");

        } else {
            // --- Case 2: new user (auto-register) ---
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
        // 🛑 Core change: do not set session directly; use OTP verification instead
        // ============================================================

        // 1. Generate a 6-digit random OTP
        $otp = rand(100000, 999999);

        // 2. Store in temporary session (same logic as login.php)
        $_SESSION['temp_otp'] = $otp;
        $_SESSION['temp_otp_expiry'] = time() + 300; // 5-minute expiry
        $_SESSION['temp_user_id'] = $target_user_id; // Key: tell verify page which user to verify
        $_SESSION['temp_email'] = $email;
        
        // 3. Send email
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