<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable displaying errors to prevent HTML output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/otp_errors.log');
date_default_timezone_set('Asia/Kolkata'); // Set to Indian timezone

require_once 'config.php';
require_once 'email_config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    $email = isset($data['email']) ? trim($data['email']) : '';

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        throw new Exception('No account found with this email');
    }

    // Generate OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set expiry time (15 minutes from now)
    $expiry = new DateTime();
    $expiry->modify('+15 minutes');

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Mark all previous unused OTPs as used
        $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE email = ? AND is_used = 0");
        $stmt->execute([$email]);

        // Insert new OTP
        $stmt = $pdo->prepare("
            INSERT INTO otp_codes (email, otp, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$email, $otp, $expiry->format('Y-m-d H:i:s')]);

        // Send email
        $to = $email;
        $subject = "Password Reset OTP";
        $message = "Your OTP for password reset is: $otp\nThis OTP will expire in 15 minutes.";
        $headers = "From: noreply@yourdomain.com";

        if (!mail($to, $subject, $message, $headers)) {
            throw new Exception('Failed to send OTP email');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'OTP has been sent to your email'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in request_otp.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 