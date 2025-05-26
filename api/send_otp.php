<?php
require_once 'config.php';

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

    // Plain text version
    $textMessage = "Your OTP for password reset is: {$otp}\n\nThis OTP will expire in 15 minutes.\nIf you didn't request this, please ignore this email.";

    // Email Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
    $headers .= "From: Personal Book App <noreply@personalbook.com>\r\n";
    $headers .= "Reply-To: noreply@personalbook.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Create the message body
    $message = "--boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $textMessage . "\r\n\r\n";
    $message .= "--boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $htmlMessage . "\r\n\r\n";
    $message .= "--boundary--";

    // Debug information
    error_log("Sending email to: " . $email);
    error_log("OTP generated: " . $otp);
    error_log("Current PHP mail configuration:");
    error_log("SMTP = " . ini_get('SMTP'));
    error_log("smtp_port = " . ini_get('smtp_port'));
    error_log("sendmail_path = " . ini_get('sendmail_path'));

    // Send email
    $mailSent = mail($email, $subject, $message, $headers);
    
    if ($mailSent) {
        error_log("Email sent successfully to " . $email);
        // Store success in session for verification
        session_start();
        $_SESSION['otp_sent'] = true;
        $_SESSION['otp_email'] = $email;
        
        echo json_encode([
            'success' => true, 
            'message' => 'OTP has been sent to your email. Please check your inbox and spam folder.',
            'debug' => [
                'email' => $email,
                'time' => date('Y-m-d H:i:s'),
                'smtp_server' => ini_get('SMTP'),
                'smtp_port' => ini_get('smtp_port')
            ]
        ]);
    } else {
        $error = error_get_last();
        error_log("Failed to send email. Error: " . ($error ? json_encode($error) : 'Unknown error'));
        throw new Exception('Failed to send email. Please check server configuration.');
    }

} catch (Exception $e) {
    error_log("Error in send_otp.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send OTP. Please try again.',
        'debug' => [
            'error' => $e->getMessage(),
            'smtp_server' => ini_get('SMTP'),
            'smtp_port' => ini_get('smtp_port'),
            'php_version' => phpversion()
        ]
    ]);
} 