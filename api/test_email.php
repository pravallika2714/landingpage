<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'email_config.php';

echo "Testing Email Configuration\n";
echo "=========================\n\n";

// Test email configuration
echo "1. Checking Email Configuration\n";
if (empty($email_config['smtp_username']) || empty($email_config['smtp_password'])) {
    echo "❌ Email configuration is incomplete. Please update email_config.php with your Gmail credentials.\n";
    exit(1);
}

echo "✅ Email configuration found\n";
echo "SMTP Host: {$email_config['smtp_host']}\n";
echo "SMTP Port: {$email_config['smtp_port']}\n";
echo "From Email: {$email_config['from_email']}\n";
echo "From Name: {$email_config['from_name']}\n\n";

// Test email parameters
$to = "nimmii0914@gmail.com"; // Sending to the same email for testing
$subject = "Test Email from Book App";
$message = "
<html>
<body>
    <h2>Test Email</h2>
    <p>If you receive this email, it means your email configuration is working correctly!</p>
    <p>Time sent: " . date('Y-m-d H:i:s') . "</p>
</body>
</html>";

// Try to send the email
if (sendEmailWithGmail($to, $subject, $message)) {
    echo "Test email sent successfully! Check your inbox.";
} else {
    echo "Failed to send test email. Check the error logs for details.";
} 