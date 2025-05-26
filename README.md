# Password Reset System with Email OTP

A secure password reset system with email OTP verification built using PHP, MySQL, and JavaScript.

## Features

- User Login and Registration
- Forgot Password with Email OTP
- OTP Verification
- Password Reset
- Email Notifications
- Secure Password Hashing
- 15-minute OTP Expiry
- Google Sign-In Integration

## Setup Instructions for Teachers/Evaluators

1. Install XAMPP
2. Clone this repository to `C:\xampp\htdocs\frontend_login`
3. Import the database schema from `database.sql`

### Email Configuration Setup
1. Copy `send_otp_new.php.template` to `send_otp_new.php`
2. Edit `send_otp_new.php` and update these lines with your Gmail credentials:
   ```php
   $mail->Username = 'your.email@gmail.com';    // Your Gmail address
   $mail->Password = 'your-app-password';       // Your Gmail App Password
   ```
   Note: You can get the Gmail App Password by:
   - Go to Google Account Settings
   - Enable 2-Step Verification
   - Go to Security → App Passwords
   - Generate new App Password for Mail

### Google Sign-In Setup
1. Copy `api/config.php.template` to `api/config.php`
2. Edit `config.php` and update this line with your Google Client ID:
   ```php
   define('GOOGLE_CLIENT_ID', 'your-client-id.apps.googleusercontent.com');
   ```

### For Quick Testing (Optional)
You can use these test credentials:
- Gmail: nimmii0914@gmail.com
- App Password: ddkwuluadicnbmds
- Google Client ID: 981917202489-i1r28amea7o390v900hjho99ju6h0hl3.apps.googleusercontent.com

## Database Configuration

- Database Name: login_db
- Username: root
- Password: (blank)

## Security Features

- Password Hashing
- OTP Expiry System
- Secure Email Delivery
- Input Validation
- SQL Injection Prevention
- OAuth 2.0 Authentication 