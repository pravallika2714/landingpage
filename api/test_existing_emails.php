<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'email_config.php';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;port=4306;dbname=login_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all registered emails
    $stmt = $pdo->query("SELECT email FROM users");
    $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($emails) . " registered emails.\n\n";
    
    // Try to send test email to each address
    foreach ($emails as $email) {
        echo "Sending test email to: " . $email . "\n";
        
        $subject = "Test OTP Email";
        $message = "
        <html>
        <body>
            <h2>Test OTP Email</h2>
            <p>This is a test email to verify the OTP system is working.</p>
            <p>Test OTP: 123456</p>
            <p>Time: " . date('Y-m-d H:i:s') . "</p>
        </body>
        </html>";
        
        if (sendEmailWithGmail($email, $subject, $message)) {
            echo "✅ Email sent successfully!\n\n";
        } else {
            echo "❌ Failed to send email.\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 