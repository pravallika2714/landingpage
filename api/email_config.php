<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email_errors.log');

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

// Check if PHPMailer files exist and are readable
$required_files = [
    'phpmailer/PHPMailer.php',
    'phpmailer/SMTP.php',
    'phpmailer/Exception.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file) || !is_readable($file)) {
        error_log("Missing or unreadable file: " . $file);
        // Download PHPMailer if files are missing
        mkdir('phpmailer', 0777, true);
        file_put_contents('phpmailer/PHPMailer.php', file_get_contents('https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php'));
        file_put_contents('phpmailer/SMTP.php', file_get_contents('https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'));
        file_put_contents('phpmailer/Exception.php', file_get_contents('https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'));
        break;
    }
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
        
        // Enable debug output
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $email_config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $email_config['smtp_username'];
        $mail->Password = $email_config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $email_config['smtp_port'];

        // Set timeout
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));

        // Log before sending
        error_log("Attempting to send email to: " . $to);
        error_log("SMTP Host: " . $email_config['smtp_host']);
        error_log("SMTP Port: " . $email_config['smtp_port']);
        error_log("SMTP Username: " . $email_config['smtp_username']);

        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        error_log("Full Error Details: " . print_r($mail->ErrorInfo, true));
        throw new Exception("Failed to send email: " . $e->getMessage());
    }
}