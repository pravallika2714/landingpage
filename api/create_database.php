<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // First connect without database name
    $pdo = new PDO("mysql:host=localhost;port=3306", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS login_db");
    echo "Database 'login_db' created or already exists!\n";
    
    // Switch to the database
    $pdo->exec("USE login_db");
    
    // Create users table with proper constraints
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255),
        google_id VARCHAR(255) UNIQUE,
        profile_picture VARCHAR(255),
        otp VARCHAR(6),
        otp_expiry TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_email (email),
        KEY idx_google_id (google_id)
    )";
    
    $pdo->exec($sql);
    echo "Users table created or verified!\n";
    
    // Drop existing otp_codes table if exists
    $pdo->exec("DROP TABLE IF EXISTS otp_codes");
    
    // Create OTP table with proper constraints and indexes
    $sql = "CREATE TABLE otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        INDEX idx_email (email),
        INDEX idx_otp (otp),
        INDEX idx_expiry (expires_at),
        CONSTRAINT fk_user_email FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "OTP table created or verified!\n";
    
    // Create stored procedure for OTP cleanup
    $pdo->exec("DROP PROCEDURE IF EXISTS cleanup_expired_otps");
    $pdo->exec("
        CREATE PROCEDURE cleanup_expired_otps()
        BEGIN
            DELETE FROM otp_codes WHERE expires_at < NOW() OR is_used = 1;
        END
    ");
    
    // Create event to automatically cleanup expired OTPs
    $pdo->exec("DROP EVENT IF EXISTS cleanup_otps_event");
    $pdo->exec("
        CREATE EVENT cleanup_otps_event
        ON SCHEDULE EVERY 1 HOUR
        DO CALL cleanup_expired_otps()
    ");
    
    // Enable event scheduler
    $pdo->exec("SET GLOBAL event_scheduler = ON");
    
    echo "Database setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 