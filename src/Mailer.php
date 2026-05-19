<?php

declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public static function sendOtp(string $toEmail, string $otp): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string) env('MAIL_HOST', 'mailhog');
            $username = (string) env('MAIL_USERNAME', '');
            $mail->Username = $username;
            $mail->Password = (string) env('MAIL_PASSWORD', '');
            $mail->SMTPAuth = filter_var(env('MAIL_AUTH', $username !== ''), FILTER_VALIDATE_BOOLEAN);
            $enc = strtolower(trim((string) env('MAIL_ENCRYPTION', '')));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
            $mail->Port = (int) env('MAIL_PORT', 1025);

            $from = (string) env('MAIL_FROM_ADDRESS', $mail->Username);
            $fromName = (string) env('MAIL_FROM_NAME', 'Bhatti Export Documents');
            $mail->setFrom($from, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Your login verification code';
            $minutes = (int) env('OTP_EXPIRY_MINUTES', 15);
            $mail->Body = "<p>Your verification code is:</p><p style='font-size:28px;font-weight:bold;letter-spacing:4px'>{$otp}</p><p>This code expires in {$minutes} minutes.</p><p>If you did not request this, ignore this email.</p>";
            $mail->AltBody = "Your verification code is: {$otp}. Expires in {$minutes} minutes.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
                error_log('Mail error: ' . $e->getMessage());
            }
            return false;
        }
    }
}
