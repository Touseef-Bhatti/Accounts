<?php

declare(strict_types=1);

namespace App;

class Mailer
{
    public static function sendOtp(string $toEmail, string $otp): bool
    {
        $minutes = (int) env('OTP_EXPIRY_MINUTES', 15);
        $subject = 'Your login verification code';
        $htmlBody = "<p>Your verification code is:</p><p style='font-size:28px;font-weight:bold;letter-spacing:4px'>{$otp}</p><p>This code expires in {$minutes} minutes.</p><p>If you did not request this, ignore this email.</p>";
        $textBody = "Your verification code is: {$otp}. Expires in {$minutes} minutes.";
        
        $from = (string) env('MAIL_FROM_ADDRESS', '');
        if (empty($from)) {
            $from = 'noreply@' . (parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        }
        $fromName = (string) env('MAIL_FROM_NAME', 'Bhatti Export Documents');
        
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $isDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
                $mail->SMTPDebug = $isDebug ? 2 : 0;
                $mail->Debugoutput = 'error_log';
                
                $mail->isSMTP();
                $mail->Host = (string) env('MAIL_HOST', 'localhost');
                $username = (string) env('MAIL_USERNAME', '');
                $mail->Username = $username;
                $mail->Password = (string) env('MAIL_PASSWORD', '');
                $mail->SMTPAuth = filter_var(env('MAIL_AUTH', $username !== ''), FILTER_VALIDATE_BOOLEAN);
                $enc = strtolower(trim((string) env('MAIL_ENCRYPTION', '')));
                if ($enc === 'ssl') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($enc === 'tls') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPSecure = '';
                    $mail->SMTPAutoTLS = false;
                }
                $mail->Port = (int) env('MAIL_PORT', 25);
                $mail->CharSet = 'UTF-8';
                
                $mail->setFrom($from, $fromName);
                $mail->addAddress($toEmail);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = $textBody;
                $mail->send();
                return true;
            } catch (\Exception $e) {
                error_log('PHPMailer error: ' . $e->getMessage());
                error_log('PHPMailer trace: ' . $e->getTraceAsString());
            }
        }
        
        $headers = "From: {$fromName} <{$from}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"boundary=\"\r\n";
        $message = "--boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= $textBody . "\r\n";
        $message .= "--boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= "--boundary--";
        
        return mail($toEmail, $subject, $message, $headers);
    }
}
