<?php
require_once 'config.php';
require_once 'email_config.php';

// Set proper CORS headers
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email'])) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }

    $email = $data['email'];

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this email']);
        exit;
    }

    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set OTP expiration time (15 minutes from now)
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Store OTP in database
    $stmt = $pdo->prepare('UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?');
    $stmt->execute([$otp, $expiry, $email]);

    // Prepare email content with HTML
    $subject = "Password Reset OTP";
    $htmlMessage = "
    <html>
    <head>
        <title>Password Reset OTP</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #333;'>Password Reset OTP</h2>
            <p>Your OTP for password reset is: <strong style='font-size: 24px; color: #4a90e2;'>{$otp}</strong></p>
            <p>This OTP will expire in 15 minutes.</p>
            <p style='color: #666;'>If you didn't request this, please ignore this email.</p>
        </div>
    </body>
    </html>";

    // Send email using Gmail SMTP
    try {
        sendEmailWithGmail($email, $subject, $htmlMessage);
        // Store success in session for verification
        session_start();
        $_SESSION['otp_sent'] = true;
        $_SESSION['otp_email'] = $email;
        
        echo json_encode([
            'success' => true, 
            'message' => 'OTP has been sent to your email. Please check your inbox and spam folder.'
        ]);
    } catch (Exception $e) {
        error_log("Error in send_otp.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send OTP. Please try again.',
            'error' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    error_log("Error in send_otp.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send OTP. Please try again.',
        'error' => $e->getMessage()
    ]);
}