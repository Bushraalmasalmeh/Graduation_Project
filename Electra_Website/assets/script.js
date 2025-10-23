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

    // Real-time email validation
    emailInput.addEventListener('blur', function() {
        if (this.value && !validateEmail(this.value)) {
            this.style.borderColor = 'red';
            showError(this, 'Invalid email format');
        } else {
            this.style.borderColor = '#444';
            removeError(this);
        }
    });

    // Real-time password validation
    passwordInput.addEventListener('blur', function() {
        if (this.value && this.value.length < 6) {
            this.style.borderColor = 'red';
            showError(this, 'Password must be at least 6 characters');
        } else {
            this.style.borderColor = '#444';
            removeError(this);
        }
    });

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate email format
        if (!validateEmail(emailInput.value)) {
            alert('Please enter a valid email address');
            emailInput.focus();
            return;
        }

        // Validate password length
        if (passwordInput.value.length < 6) {
            alert('Password must be at least 6 characters');
            passwordInput.focus();
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
    const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return re.test(String(email).toLowerCase());
}

// Show error message
function showError(input, message) {
    removeError(input);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '0.7rem';
    errorDiv.style.marginLeft = '30px';
    errorDiv.style.marginTop = '5px';
    input.parentElement.insertBefore(errorDiv, input.nextSibling);
}

// Remove error message
function removeError(input) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Temporary login simulation
function simulateLogin(email, password) {
    // Mock login process - replace with actual API call
    console.log('Attempting login with:', email);
    
    // When API is ready, it will validate credentials on server-side
    // Example: await loginAPI(email, password);
    
    window.location.href = 'dashboard.html';
}
