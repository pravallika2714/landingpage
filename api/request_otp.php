<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable displaying errors to prevent HTML output
date_default_timezone_set('Asia/Kolkata'); // Set to Indian timezone

require_once 'config.php';
require_once 'email_config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email'])) {
        throw new Exception('Email is required');
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Email not found');
    }

    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set expiration time (15 minutes from now)
    $current_time = new DateTime();
    $expires_at = $current_time->modify('+15 minutes')->format('Y-m-d H:i:s');

    // Delete any existing unused OTPs for this email
    $stmt = $pdo->prepare('DELETE FROM otp_codes WHERE email = ?');
    $stmt->execute([$email]);

    // Insert new OTP
    $stmt = $pdo->prepare('INSERT INTO otp_codes (email, otp, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$email, $otp, $expires_at]);

    // Log OTP creation
    error_log("New OTP created for $email: $otp, Expires at: $expires_at");

    // Prepare email content
    $emailBody = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #333;'>Password Reset OTP</h2>
            <p>You have requested to reset your password. Your OTP is:</p>
            <div style='background: #f5f5f5; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px;'>
                <strong>$otp</strong>
            </div>
            <p>This OTP will expire in 15 minutes (at " . date('h:i A', strtotime($expires_at)) . ").</p>
            <p><small>If you didn't request this password reset, please ignore this email.</small></p>
        </div>
    </body>
    </html>";

    // Send OTP via email
    if (sendEmailWithGmail($email, "Password Reset OTP", $emailBody)) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP has been sent to your email',
            'debug_info' => [
                'expires_at' => $expires_at,
                'current_time' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to send OTP email');
    }

} catch (Exception $e) {
    error_log("OTP Request Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 