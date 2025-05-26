<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // First connect without database name
    $pdo = new PDO("mysql:host=localhost;port=4306", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS login_db");
    echo "Database 'login_db' created or already exists!\n";
    
    // Switch to the database
    $pdo->exec("USE login_db");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Users table created or verified!\n";
    
    // Check if test user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Add test user
        $username = "testuser";
        $email = "test@example.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $insert->execute([$username, $email, $password]);
        echo "Test user created with:\nEmail: test@example.com\nPassword: password123\n";
    } else {
        echo "Test user already exists!\n";
    }
    
    // List all users
    $stmt = $pdo->query("SELECT id, username, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent users in database:\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 