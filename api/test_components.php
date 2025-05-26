<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing OTP System Components\n";
echo "============================\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection\n";
try {
    $pdo = new PDO("mysql:host=localhost;port=4306;dbname=login_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check Tables
echo "\n2. Checking Required Tables\n";
try {
    // Check users table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    echo "Users table exists: " . ($stmt->rowCount() > 0 ? "✅ Yes" : "❌ No") . "\n";
    
    // Check password_resets table
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    echo "Password resets table exists: " . ($stmt->rowCount() > 0 ? "✅ Yes" : "❌ No") . "\n";
} catch (PDOException $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "\n";
}

// Test 3: List Users
echo "\n3. Checking Users in Database\n";
try {
    $stmt = $pdo->query("SELECT id, username, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) > 0) {
        echo "✅ Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "   - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}\n";
        }
    } else {
        echo "❌ No users found in database\n";
    }
} catch (PDOException $e) {
    echo "❌ Error listing users: " . $e->getMessage() . "\n";
}

// Test 4: Test Email Function
echo "\n4. Testing Email Function\n";
function testEmail($to) {
    $subject = "Test Email";
    $message = "This is a test email from the OTP system.";
    $headers = 'From: noreply@example.com' . "\r\n";
    
    if (mail($to, $subject, $message, $headers)) {
        echo "✅ Test email sent successfully to $to\n";
        return true;
    } else {
        echo "❌ Failed to send test email to $to\n";
        $error = error_get_last();
        if ($error) {
            echo "   Error: " . $error['message'] . "\n";
        }
        return false;
    }
}

// Test with the first user's email
if (!empty($users)) {
    testEmail($users[0]['email']);
} else {
    echo "❌ No users available to test email\n";
}

// Test 5: Test OTP Generation
echo "\n5. Testing OTP Generation and Storage\n";
try {
    $testEmail = !empty($users) ? $users[0]['email'] : 'test@example.com';
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete old OTPs
    $pdo->prepare('DELETE FROM password_resets WHERE email = ? AND used = FALSE')
        ->execute([$testEmail]);
    
    // Insert new OTP
    $stmt = $pdo->prepare('INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$testEmail, $otp, $expires_at]);
    
    echo "✅ Successfully generated and stored OTP: $otp\n";
    
    // Verify OTP storage
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE email = ? AND otp = ? AND used = FALSE');
    $stmt->execute([$testEmail, $otp]);
    $stored = $stmt->fetch();
    
    if ($stored) {
        echo "✅ Successfully verified OTP storage\n";
    } else {
        echo "❌ Failed to verify OTP storage\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing OTP: " . $e->getMessage() . "\n";
} 