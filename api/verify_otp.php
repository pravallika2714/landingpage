<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/verify_otp_errors.log');
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once 'config.php'; // Use the existing database connection

    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    $email = isset($data['email']) ? trim($data['email']) : '';
    $otp = isset($data['otp']) ? trim($data['otp']) : '';

    if (empty($email) || empty($otp)) {
        throw new Exception('Email and OTP are required');
    }

    // First check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }

    // Get latest unused OTP
    $stmt = $pdo->prepare("
        SELECT * FROM otp_codes 
        WHERE email = ? 
        AND is_used = 0 
        AND expires_at > NOW() 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otpRecord) {
        throw new Exception('No valid OTP found. Please request a new OTP.');
    }

    if ($otpRecord['otp'] !== $otp) {
        error_log("OTP mismatch. Provided: $otp, Stored: " . $otpRecord['otp']);
        throw new Exception('Invalid OTP. Please try again.');
    }

    // Calculate time left
    $now = new DateTime();
    $expiry = new DateTime($otpRecord['expires_at']);
    $timeLeft = $expiry->getTimestamp() - $now->getTimestamp();

    if ($timeLeft <= 0) {
        // Mark expired OTP as used
        $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        throw new Exception('OTP has expired. Please request a new one.');
    }

    // Mark OTP as used
    $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
    $stmt->execute([$otpRecord['id']]);

    // Clear any old unused OTPs for this email
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND id != ? AND is_used = 0");
    $stmt->execute([$email, $otpRecord['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in verify_otp.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 