<?php
require_once 'config.php';

try {
    // Create OTP table
    $sql = "CREATE TABLE IF NOT EXISTS otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        is_used TINYINT(1) DEFAULT 0,
        INDEX idx_email (email),
        INDEX idx_otp (otp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo json_encode(['success' => true, 'message' => 'OTP table created successfully']);

} catch (PDOException $e) {
    error_log("Database error in create_otp_table.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 