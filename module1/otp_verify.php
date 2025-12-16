<?php
session_start();
require_once('database.php');

// Security check: accessing without going through login? Kick out!
if (!isset($_SESSION['temp_otp']) || !isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['temp_email'];
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = $_POST['otp'];
    
    // 1. 检查是否过期
    if (time() > $_SESSION['temp_otp_expiry']) {
        $error_msg = "Code expired. Please login again.";
    } 
    // 2. 比对验证码
    else if ($user_otp == $_SESSION['temp_otp']) {
        
        // ✅ Verification successful! Fetch official user data
        $user_id = $_SESSION['temp_user_id'];
        $sql = "SELECT * FROM user WHERE User_ID = '$user_id'";
        $result = mysqli_query($con, $sql);
        $row = mysqli_fetch_assoc($result);
        
        // 设置正式 Session
        $_SESSION['user_id']   = $row['User_ID'];
        $_SESSION['Firstname'] = $row['First_Name'];
        $_SESSION['Lastname']  = $row['Last_Name'];
        $_SESSION['Email']     = $row['Email'];
        $_SESSION['Avatar']    = $row['Avatar'];
        
        // 清理垃圾 Session
        unset($_SESSION['temp_otp']);
        unset($_SESSION['temp_otp_expiry']);
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_email']);
        
        // 跳转首页
        echo "<script>window.location.href = 'home.php';</script>";
        exit();
        
    } else {
        $error_msg = "Invalid code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">
        <h2 class="text-2xl font-bold mb-2">Security Verification</h2>
        <p class="text-gray-500 mb-6">Enter code sent to <strong><?php echo $email; ?></strong></p>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="otp" maxlength="6" required placeholder="000000"
                   class="w-full text-center text-3xl font-bold py-3 border rounded-lg focus:ring-2 focus:ring-green-500 mb-6 tracking-widest">
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700">Verify</button>
        </form>
        <a href="index.php" class="block mt-4 text-sm text-gray-400 hover:text-gray-600">Back to Login</a>
    </div>
</body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</html>