<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }

    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Update password
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $result = $stmt->execute([$password, $email]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successful'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reset password'
        ]);
    }

} catch (Exception $e) {
    error_log("Error resetting password: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while resetting password'
    ]);
} 