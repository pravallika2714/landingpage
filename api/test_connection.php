<?php
require_once 'config.php';

try {
    // Test database connection
    $test_connection = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    echo "✅ Database connection successful!\n";

    // Test if users table exists
    $stmt = $test_connection->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists!\n";
        
        // Check table structure
        $stmt = $test_connection->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Table columns found: " . implode(", ", $columns) . "\n";
    } else {
        echo "❌ Users table not found!\n";
    }

} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 