<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 确保路径正确：指向根目录下的 vendor
require '../vendor/autoload.php';

function sendOTPEmail($toEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // --- 服务器配置 ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // 🔴 必填：你的 Gmail 和 16位应用密码
        $mail->Username   = 'pangleeing@gmail.com'; 
        $mail->Password   = 'iatt hkzd wwkm eqdn'; // 填入你的应用密码，不要有空格
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- 收件人 ---
        $mail->setFrom('no-reply@ecotrip.com', 'ecoTrip Security');
        $mail->addAddress($toEmail);

        // --- 内容 ---
        $mail->isHTML(true);
        $mail->Subject = 'Your ecoTrip Verification Code';
        $mail->Body    = "
            <div style='padding: 20px; border: 1px solid #eee; font-family: Arial;'>
                <h2 style='color: #22c55e;'>Login Verification</h2>
                <p>Your OTP code is:</p>
                <h1 style='background: #f0fdf4; color: #16a34a; padding: 10px; text-align: center; letter-spacing: 5px;'>$otpCode</h1>
                <p>Valid for 5 minutes. Do not share this code.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>