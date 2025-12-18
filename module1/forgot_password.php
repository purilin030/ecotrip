<?php
session_start();
require_once('database.php');
require_once('mail_config.php');

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $sql = "SELECT User_ID, First_Name FROM user WHERE Email = '$email' AND Account_Status = 'Active'";
    $result = mysqli_query($con, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_id'] = $row['User_ID'];
        $_SESSION['reset_otp_expiry'] = time() + 300; // 5 minutes

        if (sendOTPEmail($email, $otp)) {
            header("Location: reset_otp_verify.php");
            exit();
        } else {
            $error_msg = "Failed to send email. Please try again later.";
        }
    } else {
        $error_msg = "No active account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - ecoTrip</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Reset Password</h2>
            <p class="text-gray-500">Enter your email to receive a 6-digit code.</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition">
                Send Reset Code
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="index.php" class="text-sm text-gray-400 hover:text-green-600">Back to Login</a>
        </div>
    </div>
</body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</html>