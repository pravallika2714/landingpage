<?php
require_once 'config.php';

try {
    // Database connection is already established in config.php, use $pdo from there
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Missing credentials']);
        exit;
    }

    $email = $data['email'];
    $password = $data['password'];

    // Debug: Log the login attempt
    error_log("Login attempt for email: " . $email);

    $stmt = $pdo->prepare('SELECT id, email, password, name, profile_picture FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) {
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
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

} catch (PDOException $e) {
    error_log("Database error in login.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in login.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred'
    ]);
}