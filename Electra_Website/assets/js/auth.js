/**
 * Authentication Module
 * Handles Login logic, Session persistence, and "Remember Me" functionality.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1. Login Logic
    // ============================================================
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const rememberCheckbox = document.getElementById('rememberMe');
        const submitBtn = loginForm.querySelector('input[type="submit"]') || loginForm.querySelector('button');

        // Restore saved email if "Remember Me" was previously checked
        const savedEmail = localStorage.getItem(CONFIG.REMEMBER_EMAIL_KEY);
        if (savedEmail && emailInput) {
            emailInput.value = savedEmail;
            if (rememberCheckbox) rememberCheckbox.checked = true;
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            // Validation: Simple check for empty fields
            if (!email || !password) {
                Utils.showError('Please enter both your email and password.');
                return;
            }

            try {
                // Use our refined Utility for loading state
                Utils.setLoading(submitBtn.id || 'login-submit', true, 'Signing in...');

                // Clear any old session data before attempting a new login
                localStorage.removeItem(CONFIG.TOKEN_KEY);
                localStorage.removeItem(CONFIG.USER_DATA_KEY);
                
                // API Request (Targeting Admin Login based on Postman collection)
                const response = await API.post('/api/admin/login', { email, password });

                if (response && response.token) {
                    // Success: Persistent Storage
                    localStorage.setItem(CONFIG.TOKEN_KEY, response.token);

                    if (response.user) {
                        localStorage.setItem(CONFIG.USER_DATA_KEY, JSON.stringify(response.user));
                    }

                    // Handle "Remember Me" preference
                    if (rememberCheckbox && rememberCheckbox.checked) {
                        localStorage.setItem(CONFIG.REMEMBER_EMAIL_KEY, email);
                    } else {
                        localStorage.removeItem(CONFIG.REMEMBER_EMAIL_KEY);
                    }

                    // Success Feedback & Redirect
                    Utils.showSuccess('Welcome Back!', 'Redirecting to your dashboard...');
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 1500);
                } else {
                    throw new Error('Authentication failed: No access token received.');
                }

            } catch (error) {
                // Professional error mapping based on server response
                let displayMessage = 'Unable to connect to the server. Please try again later.';
                const errorStr = error.message.toLowerCase();

                if (errorStr.includes('401') || errorStr.includes('credentials')) {
                    displayMessage = 'The email or password you entered is incorrect.';
                } else if (errorStr.includes('fetch')) {
                    displayMessage = 'Network error: Please check your internet connection.';
                } else {
                    displayMessage = error.message;
                }

                Utils.showError(displayMessage);
            } finally {
                // Restore button state automatically
                Utils.setLoading(submitBtn.id || 'login-submit', false);
            }
        });
    }
    // ============================================================
    // 2. Forgot Password — Step 1 (Send Code)
    // ============================================================
    const forgotForm = document.getElementById('forgot-password-form');
    if (forgotForm) {
    forgotForm.onsubmit = async (e) => {
        e.preventDefault();
        const emailInput = document.getElementById('email');
        const sendBtn = document.getElementById('send-code-button');
        const email = emailInput.value;

        try {
            // Toggle button into loading state
            Utils.setLoading('send-code-button', true, 'Sending...');
            
            const response = await API.post('/api/forgotPassword', { email });
            
            localStorage.setItem('reset_email', email);
            await Utils.showSuccess('Sent!', 'Please check your email.');
            window.location.href = 'reset-code.html';
            
        } catch (error) {
            // On error (e.g., 429), restore the button and show the error
            console.error('Forgot Password Error:', error);
            const errorMsg = error.response?.data?.message || 'Failed to send code. Try again later.';
            Utils.showError(errorMsg);
        } finally {
            // Ensure the button returns to normal state in all cases
            Utils.setLoading('send-code-button', false, 'Send Verification Code');
        }
    };
}

    // ============================================================
    // 3. Verify Code
    // ============================================================
    const verifyCodeForm = document.getElementById('reset-code-form');
    if (verifyCodeForm) {
        const savedCode = localStorage.getItem('reset_code');
        const codeInput = document.getElementById('verification-code');
        if (savedCode && codeInput) codeInput.value = savedCode;

        verifyCodeForm.onsubmit = async (e) => {
            e.preventDefault();
            const email = localStorage.getItem('reset_email');
            const code = document.getElementById('verification-code').value.trim();

            if (!email) {
                Utils.showError('Session expired. Please enter email again.');
                setTimeout(() => window.location.href = 'forgot-password.html', 2000);
                return;
            }

            try {
                Utils.setLoading('reset-password-button', true, 'Verifying...');
                
                await API.post('/api/verifyCode', { email, code });
                
                localStorage.setItem('reset_code', code);
                
                await Utils.showSuccess('Verified', 'Code is correct.');
                window.location.href = 'set-new-password.html'; 

            } catch (error) {
                const msg = error.response?.data?.error || 'Invalid code.';
                Utils.showError(msg);
            } finally {
                Utils.setLoading('reset-password-button', false, 'Reset Password');
            }
        };

        const resendLink = document.getElementById('resend-link');
        if (resendLink) {
            resendLink.onclick = async (e) => {
                e.preventDefault();
                const email = localStorage.getItem('reset_email');
                if (!email) {
                    Utils.showError('No email found. Please start over.');
                    return;
                }

                try {
                    resendLink.innerText = 'Sending...';
                    resendLink.style.pointerEvents = 'none';
                    await API.post('/api/forgotPassword', { email });
                    Utils.showSuccess('Sent!', 'A new code has been sent.');
                } catch (error) {
                    Utils.showError('Failed to resend code.');
                } finally {
                    resendLink.innerText = 'Resend Code';
                    resendLink.style.pointerEvents = 'auto';
                }
            };
        }
    }

    // ============================================================
    // 4. Set New Password
    // ============================================================
    const setPasswordForm = document.getElementById('set-password-form');
    if (setPasswordForm) {
        setPasswordForm.onsubmit = async (e) => {
            e.preventDefault();
            
            const email = localStorage.getItem('reset_email');
            const code = localStorage.getItem('reset_code');
            const password = document.getElementById('new-password-input').value;
            const password_confirmation = document.getElementById('confirm-password-input').value;

            if (!email || !code) {
                Utils.showError('Session expired. Please start over.');
                setTimeout(() => window.location.href = 'forgot-password.html', 2000);
                return;
            }

            if (password !== password_confirmation) {
                Utils.showError('Passwords do not match!');
                return;
            }

            try {
                Utils.setLoading('save-password-button', true, 'Saving...');
                
                await API.post('/api/resetPassword', {
                    email,
                    code,
                    password,
                    password_confirmation
                });

                localStorage.removeItem('reset_email');
                localStorage.removeItem('reset_code');

                await Utils.showSuccess('Success', 'Password reset successfully!');
                window.location.href = 'index.html'; // Back to login page

            } catch (error) {
                const msg = error.response?.data?.message || 'Failed to reset password.';
                Utils.showError(msg);
            } finally {
                Utils.setLoading('save-password-button', false, 'Save Password');
            }
        };
    }
});