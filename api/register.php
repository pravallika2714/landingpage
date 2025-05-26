<?php
require_once 'config.php';

try {
    // Database connection is already established in config.php, use $pdo from there

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $email = $data['email'];
    $name = $data['name'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (email, name, password) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$email, $name, $password])) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }

} catch (PDOException $e) {
    error_log("Database error in register.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in register.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred'
    ]);
} 