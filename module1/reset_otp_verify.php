<?php
session_start();
if (!isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

$error_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (time() > $_SESSION['reset_otp_expiry']) {
        $error_msg = "Code expired. Please request a new one.";
    } else if ($_POST['otp'] == $_SESSION['reset_otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: change_password.php");
        exit();
    } else {
        $error_msg = "Invalid verification code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code - ecoTrip</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">
        <h2 class="text-2xl font-bold mb-2">Check your email</h2>
        <p class="text-gray-500 mb-6">Enter the code sent to <b><?php echo $_SESSION['reset_email']; ?></b></p>

        <?php if ($error_msg): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="otp" maxlength="6" required placeholder="000000"
                   class="w-full text-center text-3xl font-bold py-3 border rounded-lg focus:ring-2 focus:ring-green-500 mb-6 tracking-widest">
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700">
                Verify Code
            </button>
        </form>
    </div>
</body>
</html>