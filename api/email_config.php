<?php
/*
IMPORTANT: Follow these steps to set up your email:

1. Sign in to nimmii0914@gmail.com
2. Go to Google Account settings (click your profile picture → Manage your Google Account)
3. Click "Security" on the left
4. Enable "2-Step Verification" if not already enabled
5. Search for "App passwords"
6. Generate a new App Password:
   - Select "Mail" for app
   - Select "Windows Computer" for device
   - Click Generate
7. Copy the 16-character password
8. Replace 'PASTE-YOUR-16-CHAR-PASSWORD-HERE' below with that password
*/

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Download PHPMailer if not exists
if (!file_exists('phpmailer')) {
    mkdir('phpmailer');
    file_put_contents('phpmailer/PHPMailer.php', file_get_contents('https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php'));
    file_put_contents('phpmailer/SMTP.php', file_get_contents('https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'));
    file_put_contents('phpmailer/Exception.php', file_get_contents('https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'));
}

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

require_once __DIR__ . '/../vendor/autoload.php';

// Gmail SMTP Configuration
$email_config = array(
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'nimmii0914@gmail.com',
    'smtp_password' => 'fjpdfldraavzglsr', // ← App password (spaces removed)
    'smtp_secure' => 'tls',
    'from_email' => 'nimmii0914@gmail.com',
    'from_name' => 'Personal Book App'
);

// Function to send email using Gmail SMTP
function sendEmailWithGmail($to, $subject, $message) {
    global $email_config;

    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $email_config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $email_config['smtp_username'];
        $mail->Password = $email_config['smtp_password'];
        $mail->SMTPSecure = $email_config['smtp_secure'];
        $mail->Port = $email_config['smtp_port'];

        // Recipients
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));

        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
} 