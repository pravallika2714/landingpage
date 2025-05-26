<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendPasswordResetEmail($to, $username, $tempPassword) {
    $subject = "Password Reset - Your Personal Book App";
    
    // HTML email body with modern design
    $htmlMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #6b4065; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
            .password { background: #f5f5f5; padding: 15px; margin: 20px 0; font-family: monospace; text-align: center; font-size: 18px; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            .button { display: inline-block; padding: 10px 20px; background: #6b4065; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Password Reset</h2>
            </div>
            <div class='content'>
                <p>Dear $username,</p>
                <p>We received a request to reset your password for your Personal Book App account.</p>
                <p>Your temporary password is:</p>
                <div class='password'>$tempPassword</div>
                <p>Please use this temporary password to log in. For security reasons, we recommend changing your password after logging in.</p>
                <p><strong>Note:</strong> If you didn't request this password reset, please contact us immediately.</p>
                <a href='http://localhost/frontend_login' class='button'>Go to Login</a>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Personal Book App. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Plain text version for email clients that don't support HTML
    $textMessage = "
    Password Reset - Personal Book App
    
    Dear $username,
    
    We received a request to reset your password for your Personal Book App account.
    
    Your temporary password is: $tempPassword
    
    Please use this temporary password to log in. For security reasons, we recommend changing your password after logging in.
    
    Note: If you didn't request this password reset, please contact us immediately.
    
    Visit: http://localhost/frontend_login to log in.
    
    This is an automated message, please do not reply to this email.
    ";
    
    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Personal Book App <noreply@personalbookapp.com>',
        'Reply-To: noreply@personalbookapp.com',
        'X-Mailer: PHP/' . phpversion()
    );
    
    // Send the email
    return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
}

try {
    // Try localhost first
    try {
        $pdo = new PDO("mysql:host=localhost;port=4306;dbname=login_db", "root", "");
    } catch (PDOException $e) {
        // If localhost fails, try 127.0.0.1
        $pdo = new PDO("mysql:host=127.0.0.1;port=4306;dbname=login_db", "root", "");
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username'])) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        exit;
    }

    $username = $data['username'];

    // Check if username exists and get their email
    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['email']) {
        // Generate a random temporary password (12 characters)
        $temp_password = bin2hex(random_bytes(6));
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        // Update the user's password
        $update_stmt = $pdo->prepare('UPDATE users SET password = ? WHERE username = ?');
        $update_stmt->execute([$hashed_password, $username]);

        // Send password reset email
        $emailSent = sendPasswordResetEmail($user['email'], $user['username'], $temp_password);

        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email.',
                'debug_temp_password' => $temp_password // Remove this in production!
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email. Please try again or contact support.',
                'debug_temp_password' => $temp_password // Remove this in production!
            ]);
        }
    } else {
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Username not found']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No email associated with this account']);
        }
    }
} catch (PDOException $e) {
    error_log("Reset password error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} 