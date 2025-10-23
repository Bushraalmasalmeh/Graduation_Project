document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const rememberMe = document.getElementById('rememberMe');

    // Check for saved credentials
    if (localStorage.getItem('rememberedEmail')) {
        emailInput.value = localStorage.getItem('rememberedEmail');
        rememberMe.checked = true;
    }

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate email format
        if (!validateEmail(emailInput.value)) {
            alert('Please enter a valid email address');
            return;
        }

        // Handle remember me functionality
        if (rememberMe.checked) {
            localStorage.setItem('rememberedEmail', emailInput.value);
        } else {
            localStorage.removeItem('rememberedEmail');
        }

        // TODO: Add API connection here
        simulateLogin(emailInput.value, passwordInput.value);
    });
});

// Email validation using regex
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Temporary login simulation
function simulateLogin(email, password) {
    // Mock login process - replace with actual API call
    console.log('Attempting login with:', email);
    window.location.href = 'dashboard.html';
}
