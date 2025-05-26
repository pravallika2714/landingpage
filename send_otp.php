<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=login_db", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['email'])) {
    throw new Exception('Email is required');
}

$email = trim($data['email']);

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }
    
    // Generate OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Update user with OTP
    $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
    if (!$stmt->execute([$otp, $expiry, $email])) {
        throw new Exception('Failed to update OTP');
    }

    // Clear any previous output
    ob_clean();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'OTP generated successfully',
        'debug_otp' => $otp
    ]);

} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffer and send response
ob_end_flush();
exit();
?> 