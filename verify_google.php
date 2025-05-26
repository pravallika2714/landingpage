<?php
require_once 'database.php';
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$email = $data['email'] ?? '';
$name = $data['name'] ?? '';

if (!$token || !$email || !$name) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists, return success
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ]);
    } else {
        // Create new user
        $username = explode('@', $email)[0]; // Use email prefix as username
        $hashedPassword = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT); // Random password

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $pdo->lastInsertId(),
                'username' => $username,
                'email' => $email
            ]
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 