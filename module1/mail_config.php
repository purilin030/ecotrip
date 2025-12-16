<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure path is correct: points to vendor in project root
require '../vendor/autoload.php';

function sendOTPEmail($toEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        // --- Server configuration ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // 🔴 Required: your Gmail and 16-character app password
        $mail->Username   = 'utarecotrip2025@gmail.com'; 
        $mail->Password   = 'hrtw bipe xmtd jqhj'; // 填入你的应用密码，不要有空格
        
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