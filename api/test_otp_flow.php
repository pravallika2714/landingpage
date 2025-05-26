<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'email_config.php';

echo "Testing Complete OTP Flow\n";
echo "=======================\n\n";

// Test email to use (this is a registered email from the database)
$test_email = "lakkabattulapravallika@gmail.com";

echo "1. Requesting OTP for email: $test_email\n";
$otp_request_data = json_encode(['email' => $test_email]);

$ch = curl_init('http://localhost/frontend_login/api/request_otp.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $otp_request_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Create a temporary file handle for CURL to write verbose information
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

// Print verbose curl output
echo "\nCURL Verbose Output:\n";
echo $verboseLog;

echo "\nResponse Headers:\n";
print_r(curl_getinfo($ch));

echo "\nResponse ($http_code): " . $response . "\n\n";

if (curl_errno($ch)) {
    echo "Curl Error: " . curl_error($ch) . "\n";
}

curl_close($ch);

// Extract OTP from response (for testing only)
$response_data = json_decode($response, true);
if (isset($response_data['debug_otp'])) {
    $otp = $response_data['debug_otp'];
    
    echo "2. Testing OTP Verification\n";
    echo "Sending data: " . json_encode([
        'email' => $test_email,
        'otp' => $otp,
        'new_password' => 'NewTestPass123'
    ], JSON_PRETTY_PRINT) . "\n\n";
    
    $verify_data = json_encode([
        'email' => $test_email,
        'otp' => $otp,
        'new_password' => 'NewTestPass123'
    ]);
    
    $ch = curl_init('http://localhost/frontend_login/api/verify_otp.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $verify_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Create a temporary file handle for CURL to write verbose information
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Get verbose information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    
    // Print verbose curl output
    echo "\nCURL Verbose Output:\n";
    echo $verboseLog;
    
    echo "\nResponse Headers:\n";
    print_r(curl_getinfo($ch));
    
    echo "\nResponse ($http_code): " . $response . "\n";
    
    if (curl_errno($ch)) {
        echo "Curl Error: " . curl_error($ch) . "\n";
    }
    
    curl_close($ch);
} 