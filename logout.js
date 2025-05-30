// Simple logout function that will definitely work
function simpleLogout() {
    console.log('Starting logout process...');

    // Clear all storage
    localStorage.clear();
    sessionStorage.clear();
    console.log('Storage cleared');

    // Reset any global variables
    if (typeof currentUser !== 'undefined') {
        currentUser = null;
    }

    // Hide dashboard
    var dashboard = document.getElementById('dashboard');
    if (dashboard) {
        dashboard.style.display = 'none';
    }

    // Show login form
    var formContainer = document.getElementById('formContainer');
    if (formContainer) {
        formContainer.style.display = 'block';
    }

    // Call logout API
    fetch('api/logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    }).finally(function() {
        // Redirect to login page regardless of API response
        window.location.href = window.location.pathname + '#login';
        
        // Show login form
        if (typeof toggleForm === 'function') {
            toggleForm('login');
        }
        
        console.log('Logout completed');
    });
}

// Add event listener when document loads
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listener to logout button
    var logoutButton = document.querySelector('button[onclick="simpleLogout()"]');
    if (logoutButton) {
        logoutButton.addEventListener('click', simpleLogout);
    }
}); 