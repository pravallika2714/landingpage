<?php
require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

try {
    // Fetch all users from the database
    $stmt = $pdo->prepare('SELECT id, name, email FROM users ORDER BY id DESC');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch users',
        'error' => $e->getMessage()
    ]);
} 