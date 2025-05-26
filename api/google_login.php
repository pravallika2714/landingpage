<?php
require_once 'config.php';
require_once '../vendor/autoload.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['credential'])) {
        echo json_encode(['success' => false, 'message' => 'No credential provided']);
        exit;
    }

    // Initialize the Google Client
    $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
    
    // Verify the token
    try {
        $payload = $client->verifyIdToken($data['credential']);
        if (!$payload) {
            throw new Exception('Invalid token');
        }
    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    // Extract user information from payload
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];
    $picture = $payload['picture'] ?? null;

    // Check if user exists
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR google_id = ?');
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user's Google ID if not set
        if (!$user['google_id']) {
            $updateStmt = $pdo->prepare('UPDATE users SET google_id = ?, profile_picture = ? WHERE id = ?');
            $updateStmt->execute([$google_id, $picture, $user['id']]);
        }
    } else {
        // Create new user
        $stmt = $pdo->prepare('INSERT INTO users (name, email, google_id, profile_picture) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $google_id, $picture]);
        
        // Get the newly created user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    // Return user data
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'profile_picture' => $user['profile_picture']
        ]
    ]);

} catch (Exception $e) {
    error_log("Google login error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during Google Sign-In'
    ]);
} 