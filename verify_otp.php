<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

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
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    $email = isset($data['email']) ? trim($data['email']) : '';
    $otp = isset($data['otp']) ? trim($data['otp']) : '';

    if (empty($email) || empty($otp)) {
        throw new Exception('Email and OTP are required');
    }

    // Get user's OTP and expiry
    $stmt = $pdo->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    if ($user['otp'] !== $otp) {
        throw new Exception('Invalid OTP');
    }

    $now = new DateTime();
    $expiry = new DateTime($user['otp_expiry']);

    if ($now > $expiry) {
        throw new Exception('OTP has expired');
    }

    // Clear the OTP after successful verification
    $stmt = $pdo->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE email = ?");
    $stmt->execute([$email]);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully'
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
exit(); 