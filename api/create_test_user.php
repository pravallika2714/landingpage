<?php
require_once 'config.php';

try {
    // Test user data
    $email = 'test@example.com';
    $name = 'Test User';
    $password = password_hash('password123', PASSWORD_DEFAULT);

    // Check if user already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        // Insert new user
        $stmt = $pdo->prepare('INSERT INTO users (email, name, password) VALUES (?, ?, ?)');
        $stmt->execute([$email, $name, $password]);
        echo json_encode([
            'success' => true,
            'message' => 'Test user created successfully',
            'email' => $email,
            'password' => 'password123'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Test user already exists',
            'email' => $email,
            'password' => 'password123'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error creating test user',
        'error' => $e->getMessage()
    ]);
} 