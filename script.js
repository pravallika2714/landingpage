const API_URL = 'http://localhost/frontend_login/api';

const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');
const forgotForm = document.getElementById('forgotForm');
const otpForm = document.getElementById('otpForm');
const newPasswordForm = document.getElementById('newPasswordForm');
const loginError = document.getElementById('loginError');
const signupError = document.getElementById('signupError');
const forgotError = document.getElementById('forgotError');
const otpError = document.getElementById('otpError');
const newPasswordError = document.getElementById('newPasswordError');
const formTitle = document.getElementById('formTitle');
const formContainer = document.getElementById('formContainer');
const dashboard = document.getElementById('dashboard');
const dataTable = document.getElementById('dataTable');

let currentUser = null;

function toggleForm(form) {
    // Hide all forms
    [loginForm, signupForm, forgotForm, otpForm, newPasswordForm].forEach(f => {
        f.style.display = 'none';
        // Clear any error messages
        const errorDiv = f.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
    });

    // Show selected form
    document.getElementById(form + 'Form').style.display = 'block';
    
    // Update form title
    const titles = {
        'login': 'Welcome Back',
        'signup': 'Create Account',
        'forgot': 'Reset Password',
        'otp': 'Verify OTP',
        'newPassword': 'Set New Password'
    };
    formTitle.textContent = titles[form] || 'Personal Book App';
}

// Function to show error messages
function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 3000);
}

// Function to show success messages
function showSuccess(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.style.display = 'block';
    element.style.color = '#28a745';
    setTimeout(() => {
        element.style.display = 'none';
    }, 3000);
}

// Google Sign-In handler
async function handleGoogleSignIn(response) {
    try {
        const credential = response.credential;
        
        const googleResponse = await fetch(`${API_URL}/google_login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ credential })
        });

        const data = await googleResponse.json();
        if (data.success) {
            currentUser = data.user;
            showDashboard();
        } else {
            showError('loginError', data.message || 'Google Sign-In failed');
        }
    } catch (error) {
        console.error('Google Sign-In error:', error);
        showError('loginError', 'An error occurred during Google Sign-In');
    }
}

// Login form submission
loginForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const email = document.getElementById('loginUser').value;
    const password = document.getElementById('loginPass').value;

    try {
        const response = await fetch(`${API_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            currentUser = data.user;
            showDashboard();
        } else {
            showError('loginError', data.message || 'Invalid credentials');
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('loginError', 'An error occurred. Please check your connection and try again.');
    }
});

// Signup form submission
signupForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const name = document.getElementById('newUser').value;
    const email = document.getElementById('newEmail').value;
    const password = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;

    if (password !== confirmPass) {
        showError('signupError', 'Passwords do not match');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name, email, password })
        });

        const data = await response.json();
        if (data.success) {
            showSuccess('signupError', 'Registration successful! Please login.');
            setTimeout(() => toggleForm('login'), 2000);
        } else {
            showError('signupError', data.message || 'Registration failed');
        }
    } catch (error) {
        showError('signupError', 'An error occurred. Please try again.');
    }
});

// Forgot password form submission
forgotForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const email = document.getElementById('forgotEmail').value;
    const errorDiv = document.getElementById('forgotError');

    try {
        errorDiv.textContent = 'Sending OTP to your email...';
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#666';

        const response = await fetch('send_otp_new.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();
        
        if (data.success) {
            // Store email for verification
            localStorage.setItem('resetEmail', email);
            
            errorDiv.style.color = '#28a745';
            errorDiv.textContent = 'OTP has been sent to your email';
            
            // Switch to OTP form after 3 seconds
            setTimeout(() => {
                toggleForm('otp');
                errorDiv.style.display = 'none';
            }, 3000);
        } else {
            errorDiv.style.color = '#dc3545';
            errorDiv.textContent = data.message || 'Failed to send OTP';
        }
    } catch (error) {
        console.error('Forgot password error:', error);
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'An error occurred. Please try again.';
    }
});

// OTP verification form submission
otpForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const otp = document.getElementById('otpInput').value;
    const email = localStorage.getItem('resetEmail');
    const errorDiv = document.getElementById('otpError');

    if (!email) {
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'Session expired. Please try again';
        errorDiv.style.display = 'block';
        return;
    }

    try {
        errorDiv.textContent = 'Verifying OTP...';
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#666';

        const response = await fetch('verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, otp })
        });

        const data = await response.json();
        
        if (data.success) {
            errorDiv.style.color = '#28a745';
            errorDiv.textContent = 'OTP verified successfully';
            
            // Switch to new password form after 2 seconds
            setTimeout(() => {
                toggleForm('newPassword');
                errorDiv.style.display = 'none';
            }, 2000);
        } else {
            errorDiv.style.color = '#dc3545';
            errorDiv.textContent = data.message || 'Invalid OTP';
        }
    } catch (error) {
        console.error('OTP verification error:', error);
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'An error occurred. Please try again.';
    }
});

// New password form submission
newPasswordForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const newPassword = document.getElementById('newPassword').value;
    const confirmNewPassword = document.getElementById('confirmNewPassword').value;
    const email = localStorage.getItem('resetEmail');
    const errorDiv = document.getElementById('newPasswordError');

    if (newPassword !== confirmNewPassword) {
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'Passwords do not match';
        errorDiv.style.display = 'block';
        return;
    }

    if (!email) {
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'Session expired. Please try again';
        errorDiv.style.display = 'block';
        return;
    }

    try {
        errorDiv.textContent = 'Updating password...';
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#666';

        const response = await fetch('reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password: newPassword })
        });

        const data = await response.json();
        
        if (data.success) {
            errorDiv.style.color = '#28a745';
            errorDiv.textContent = 'Password updated successfully!';
            
            // Clear stored email
            localStorage.removeItem('resetEmail');
            
            // Switch to login form after 2 seconds
            setTimeout(() => {
                toggleForm('login');
                errorDiv.style.display = 'none';
            }, 2000);
        } else {
            errorDiv.style.color = '#dc3545';
            errorDiv.textContent = data.message || 'Failed to update password';
        }
    } catch (error) {
        console.error('Password reset error:', error);
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'An error occurred. Please try again.';
    }
});

// Function to fetch and display dashboard data
async function fetchAndDisplayData() {
    try {
        const response = await fetch(`${API_URL}/read.php`);
        const data = await response.json();
        
        if (data.success && Array.isArray(data.data)) {
            let tableHTML = '';
            data.data.forEach(user => {
                tableHTML += `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.username || user.name}</td>
                        <td>${user.email}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                        </td>
                    </tr>
                `;
            });
            dataTable.innerHTML = tableHTML;
        } else {
            dataTable.innerHTML = '<tr><td colspan="4" class="text-center">No data available</td></tr>';
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        dataTable.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>';
    }
}

// Function to show dashboard
function showDashboard() {
    formContainer.style.display = 'none';
    dashboard.style.display = 'block';
    fetchAndDisplayData(); // Fetch and display data when dashboard is shown
}

// Function to delete user
async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) {
        return;
    }

    try {
        const response = await fetch(`${API_URL}/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });

        const data = await response.json();
        if (data.success) {
            fetchAndDisplayData(); // Refresh the table after deletion
        } else {
            alert(data.message || 'Failed to delete user');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('An error occurred while deleting the user');
    }
}

// Function to edit user
function editUser(id) {
    // Implement edit functionality
    alert('Edit functionality coming soon!');
}

// Logout function
function logout() {
    currentUser = null;
    dashboard.style.display = 'none';
    formContainer.style.display = 'block';
    toggleForm('login');
}