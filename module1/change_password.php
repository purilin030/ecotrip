<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

$error_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pass = $_POST['password'];
    $conf = $_POST['confirm_password'];

    if ($pass !== $conf) {
        $error_msg = "Passwords do not match.";
    } else {
        $hashed_pass = md5($pass); // Matching your existing table encryption style
        $user_id = $_SESSION['reset_user_id'];
        
        $sql = "UPDATE user SET Password = '$hashed_pass' WHERE User_ID = '$user_id'";
        if (mysqli_query($con, $sql)) {
            session_destroy();
            echo "<script>alert('Password reset successful!'); window.location.href='index.php';</script>";
            exit();
        } else {
            $error_msg = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Password - ecoTrip</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-center">Set New Password</h2>

        <?php if ($error_msg): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700">
                Update Password
            </button>
        </form>
    </div>
</body>
</html>