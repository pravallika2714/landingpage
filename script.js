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

// Route definitions with metadata
const routes = {
    login: {
        isPublic: true,
        title: 'Login',
        requiredRole: null
    },
    signup: {
        isPublic: true,
        title: 'Create Account',
        requiredRole: null
    },
    forgot: {
        isPublic: true,
        title: 'Reset Password',
        requiredRole: null
    },
    otp: {
        isPublic: true,
        title: 'Verify OTP',
        requiredRole: null
    },
    newPassword: {
        isPublic: true,
        title: 'Set New Password',
        requiredRole: null
    },
    dashboard: {
        isPublic: false,
        title: 'Dashboard',
        requiredRole: 'user'
    },
    profile: {
        isPublic: false,
        title: 'Profile',
        requiredRole: 'user'
    },
    settings: {
        isPublic: false,
        title: 'Settings',
        requiredRole: 'user'
    }
};

// Initialize form handling
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded'); // Debug log
    
    // Add click event listeners for all form toggle links
    const formLinks = document.querySelectorAll('a[data-form]');
    console.log('Found form links:', formLinks.length); // Debug log
    
    formLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const formType = this.getAttribute('data-form');
            console.log('Clicked form link for:', formType); // Debug log
            toggleForm(formType);
        });
    });

    // Handle hash changes
    window.addEventListener('hashchange', function(e) {
        console.log('Hash changed to:', window.location.hash); // Debug log
        handleRouteChange();
    });

    // Initial form check
    checkAuth();
    const hash = window.location.hash.replace('#', '') || 'login';
    console.log('Initial hash:', hash); // Debug log
    toggleForm(hash);
});

// Enhanced route protection
function checkAuth() {
    const currentRoute = window.location.hash.replace('#', '') || 'login';
    const route = routes[currentRoute];
    
    if (!route) {
        console.log('Invalid route, redirecting to login');
        window.location.hash = '#login';
        return false;
    }

    const user = localStorage.getItem('currentUser') ? JSON.parse(localStorage.getItem('currentUser')) : null;
    console.log('Checking auth for route:', currentRoute, 'User:', user ? 'logged in' : 'not logged in');

    // Check if route requires authentication
    if (!route.isPublic) {
        if (!user) {
            console.log('Protected route accessed without auth');
            window.location.hash = '#login';
            toggleForm('login');
            showError('loginError', 'Please login to access this page');
            return false;
        }

        // Check token expiration
        if (user.exp && user.exp < Date.now() / 1000) {
            console.log('Session expired');
            localStorage.removeItem('currentUser');
            window.location.hash = '#login';
            toggleForm('login');
            showError('loginError', 'Session expired. Please login again');
            return false;
        }

        // Check role requirements if specified
        if (route.requiredRole && (!user.role || user.role !== route.requiredRole)) {
            console.log('Insufficient permissions');
            window.location.hash = '#dashboard';
            showError('dashboardError', 'You do not have permission to access this page');
            return false;
        }
    } else if (user) {
        // Redirect logged-in users away from public routes
        console.log('Logged in user accessing public route');
        window.location.hash = '#dashboard';
        return false;
    }

    return true;
}

// Enhanced route change handler
function handleRouteChange() {
    if (!checkAuth()) return;
    
    const currentRoute = window.location.hash.replace('#', '') || 'login';
    const route = routes[currentRoute];
    
    console.log('Handling route change to:', currentRoute);
    
    // Update page title
    document.title = `${route.title} | Personal Book App`;
    
    // Hide all containers
    [formContainer, dashboard].forEach(container => {
        if (container) container.style.display = 'none';
    });
    
    // Show appropriate container
    if (!route.isPublic) {
        if (dashboard) {
            dashboard.style.display = 'block';
            // Refresh dashboard data if needed
            if (currentRoute === 'dashboard') {
                fetchAndDisplayData();
            }
        }
    } else {
        if (formContainer) {
            formContainer.style.display = 'block';
            toggleForm(currentRoute);
        }
    }
}

// Form toggle function
function toggleForm(form) {
    console.log('Toggling form:', form); // Debug log
    
    // Validate form parameter
    if (!form || typeof form !== 'string') {
        console.error('Invalid form parameter:', form);
        return;
    }

    // Hide all forms
    const forms = [loginForm, signupForm, forgotForm, otpForm, newPasswordForm];
    forms.forEach(f => {
        if (f) {
            console.log('Hiding form:', f.id); // Debug log
            f.style.display = 'none';
            const errorDiv = f.querySelector('.error-message');
            if (errorDiv) {
                errorDiv.style.display = 'none';
                errorDiv.textContent = '';
            }
        }
    });

    // Show selected form
    const targetForm = document.getElementById(form + 'Form');
    if (targetForm) {
        console.log('Showing form:', targetForm.id); // Debug log
        targetForm.style.display = 'block';
        
        // Focus on first input
        const firstInput = targetForm.querySelector('input');
        if (firstInput) {
            console.log('Focusing on:', firstInput.id); // Debug log
            firstInput.focus();
        }

        // Update URL hash
        if (window.location.hash !== '#' + form) {
            window.location.hash = form;
        }
        
        // Update form title
        const titles = {
            'login': 'Welcome Back',
            'signup': 'Create Account',
            'forgot': 'Reset Password',
            'otp': 'Verify OTP',
            'newPassword': 'Set New Password'
        };
        
        if (formTitle) {
            formTitle.textContent = titles[form] || 'Personal Book App';
        }
    } else {
        console.error('Target form not found:', form + 'Form'); // Debug log
    }
}

// Update login success handler
async function handleLoginSuccess(userData) {
    currentUser = userData;
    localStorage.setItem('currentUser', JSON.stringify({
        ...userData,
        exp: Date.now() / 1000 + 3600 // 1 hour expiration
    }));
    window.location.hash = '#dashboard';
    showDashboard();
}

// Update logout handler
function handleLogout() {
    localStorage.removeItem('currentUser');
    currentUser = null;
    window.location.hash = '#login';
    toggleForm('login');
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
            // Store user data in localStorage
            localStorage.setItem('currentUser', JSON.stringify(data.user));
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
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: name,
                email: email,
                password: password
            })
        });

        const data = await response.json();
        if (data.success) {
            showSuccess('signupError', 'Registration successful! Please login.');
            setTimeout(() => toggleForm('login'), 2000);
        } else {
            showError('signupError', data.message || 'Registration failed');
        }
    } catch (error) {
        console.error('Signup error:', error);
        showError('signupError', 'An error occurred. Please try again.');
    }
});

// Forgot password form submission
forgotForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const email = document.getElementById('forgotEmail').value;
    const errorDiv = document.getElementById('forgotError');

    try {
        // Clear any existing error messages
        errorDiv.textContent = 'Sending OTP to your email...';
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#666';

        const response = await fetch(`${API_URL}/request_otp.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Server response:', data); // For debugging
        
        if (data.success) {
            // Store email for verification
            localStorage.setItem('resetEmail', email);
            localStorage.setItem('otpRequestTime', new Date().toISOString());
            
            errorDiv.style.color = '#28a745';
            errorDiv.textContent = data.message || 'OTP has been sent to your email';
            
            // Switch to OTP form after showing success message
            setTimeout(() => {
                toggleForm('otp');
                const otpInput = document.getElementById('otpInput');
                if (otpInput) {
                    otpInput.focus();
                }
                errorDiv.style.display = 'none';
            }, 2000);
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

    // Check if OTP request is still valid (15 minutes)
    const otpRequestTime = localStorage.getItem('otpRequestTime');
    if (otpRequestTime) {
        const timeDiff = (new Date() - new Date(otpRequestTime)) / 1000 / 60; // in minutes
        if (timeDiff > 15) {
            errorDiv.style.color = '#dc3545';
            errorDiv.textContent = 'OTP has expired. Please request a new one.';
            errorDiv.style.display = 'block';
            setTimeout(() => toggleForm('forgot'), 2000);
            return;
        }
    }

    if (!email) {
        errorDiv.style.color = '#dc3545';
        errorDiv.textContent = 'Session expired. Please try again';
        errorDiv.style.display = 'block';
        setTimeout(() => toggleForm('forgot'), 2000);
        return;
    }

    try {
        errorDiv.textContent = 'Verifying OTP...';
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#666';

        const response = await fetch(`${API_URL}/verify_otp.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, otp })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            errorDiv.style.color = '#28a745';
            errorDiv.textContent = 'OTP verified successfully';
            
            // Switch to new password form after showing success message
            setTimeout(() => {
                toggleForm('newPassword');
                const newPasswordInput = document.getElementById('newPassword');
                if (newPasswordInput) {
                    newPasswordInput.focus();
                }
                errorDiv.style.display = 'none';
            }, 1500);
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
    addTestButton();
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

// Function to test route protection
function testRouteProtection() {
    console.log('=== Testing Route Protection ===');
    
    // Get current auth state
    const user = localStorage.getItem('currentUser');
    console.log('Current auth state:', user ? 'Logged in' : 'Not logged in');
    
    // Test all routes
    Object.entries(routes).forEach(([route, config]) => {
        console.log(`\nTesting route: #${route}`);
        console.log(`Type: ${config.isPublic ? 'Public' : 'Protected'}`);
        console.log(`Required Role: ${config.requiredRole || 'None'}`);
        
        // Store current hash
        const currentHash = window.location.hash;
        
        // Try accessing the route
        window.location.hash = `#${route}`;
        
        // Log result
        const newHash = window.location.hash;
        console.log('Attempted navigation result:', 
            newHash === `#${route}` ? 'Access Allowed' : `Redirected to ${newHash}`
        );
        
        // Restore original hash
        window.location.hash = currentHash;
    });
    
    console.log('\n=== Route Protection Test Complete ===');
}

// Add test button to dashboard for admins
function addTestButton() {
    const user = JSON.parse(localStorage.getItem('currentUser') || '{}');
    if (user && user.role === 'admin') {
        const dashboard = document.getElementById('dashboard');
        if (dashboard) {
            const testButton = document.createElement('button');
            testButton.className = 'btn btn-warning mt-3';
            testButton.textContent = 'Test Route Protection';
            testButton.onclick = testRouteProtection;
            dashboard.insertBefore(testButton, dashboard.firstChild);
        }
    }
}