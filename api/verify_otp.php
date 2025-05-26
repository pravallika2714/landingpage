<?php
require_once 'database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kolkata'); // Set to Indian timezone

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['otp'])) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
    exit;
}

$email = $data['email'];
$otp = $data['otp'];

try {
    // Get user with matching OTP
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND otp = ?');
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
        exit;
    }

    // Check if OTP has expired
    $now = new DateTime();
    $expiry = new DateTime($user['otp_expiry']);
    
    if ($now > $expiry) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired']);
        exit;
    }

    // Clear OTP after successful verification
    $stmt = $pdo->prepare('UPDATE users SET otp = NULL, otp_expiry = NULL WHERE email = ?');
    $stmt->execute([$email]);

    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully'
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} 