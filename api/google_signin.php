<?php
require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || !isset($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $email = $data['email'];
    $name = $data['name'];
    $picture = $data['picture'] ?? null;
    $google_id = $data['token'] ?? null;

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, email, name, profile_picture FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user's info
        $stmt = $pdo->prepare('UPDATE users SET name = ?, profile_picture = ?, google_id = ? WHERE email = ?');
        $stmt->execute([$name, $picture, $google_id, $email]);
    } else {
        // Create new user
        $stmt = $pdo->prepare('INSERT INTO users (email, name, profile_picture, google_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $name, $picture, $google_id]);
        
        $user = [
            'id' => $pdo->lastInsertId(),
            'email' => $email,
            'name' => $name,
            'profile_picture' => $picture
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Google sign-in successful',
        'user' => $user
    ]);

} catch (PDOException $e) {
    error_log("Database error in google_signin.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in google_signin.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
} 