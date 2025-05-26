<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO("mysql:host=localhost;port=4306;dbname=login_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT username, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Registered Users:\n";
    echo "================\n";
    foreach ($users as $user) {
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "----------------\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 