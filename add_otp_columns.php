<?php
require_once 'database.php';

try {
    $sql = "ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS otp VARCHAR(6) NULL,
            ADD COLUMN IF NOT EXISTS otp_expiry DATETIME NULL";
    
    $pdo->exec($sql);
    echo json_encode(['success' => true, 'message' => 'OTP columns added successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 