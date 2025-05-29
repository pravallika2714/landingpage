<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/verify_otp_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ob_clean();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=login_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    // Get OTP from otp_codes table
    $stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE email = ? AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otpRecord) {
        throw new Exception('No valid OTP found');
    }

    if ($otpRecord['otp'] !== $otp) {
        error_log("OTP mismatch. Provided: $otp, Stored: " . $otpRecord['otp']);
        throw new Exception('Invalid OTP');
    }

    $now = new DateTime();
    $expiry = new DateTime($otpRecord['expires_at']);

    if ($now > $expiry) {
        throw new Exception('OTP has expired');
    }

    // Mark OTP as used
    $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
    $stmt->execute([$otpRecord['id']]);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in verify_otp.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
exit(); 