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
    
    if (!isset($data['credential'])) {
        throw new Exception('Google credential not provided');
    }

    // Verify the Google token
    $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
    $payload = $client->verifyIdToken($data['credential']);
    
    if (!$payload) {
        throw new Exception('Invalid Google token');
    }

    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? OR email = ?');
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user's Google ID if not set
        if (!$user['google_id']) {
            $update = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?');
            $update->execute([$google_id, $user['id']]);
        }
    } else {
        // Create new user
        $stmt = $pdo->prepare('INSERT INTO users (username, email, google_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $google_id]);
        
        $user = [
            'id' => $pdo->lastInsertId(),
            'username' => $name,
            'email' => $email
        ];
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);

} catch (Exception $e) {
    error_log('Google auth error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed: ' . $e->getMessage()
    ]);
} 