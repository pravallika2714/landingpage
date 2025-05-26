<?php
// Allow requests from your frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database connection
require_once 'database.php';

// Get the action from POST data
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

// Handle different actions
switch($action) {
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            $response = ['success' => true, 'message' => 'Login successful'];
        } else {
            $response = ['success' => false, 'message' => 'Invalid credentials'];
        }
        break;
        
    case 'signup':
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            
            $response = ['success' => true, 'message' => 'Signup successful'];
        } catch(PDOException $e) {
            $response = ['success' => false, 'message' => 'Username or email already exists'];
        }
        break;
        
    case 'forgot':
        $username = $_POST['username'] ?? '';
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT email FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // In a real app, you would send an email here
            $response = ['success' => true, 'message' => 'Password reset instructions sent'];
        } else {
            $response = ['success' => false, 'message' => 'User not found'];
        }
        break;
}

// Send response back to frontend
echo json_encode($response);
?>