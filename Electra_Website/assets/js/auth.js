// auth.js - Updated with SweetAlert2

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. التعامل مع فورم تسجيل الدخول ---
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (email.trim() === '' || password.trim() === '') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please fill in both email and password.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
                return;
            }
            console.log('Simulating successful login...');
            window.location.href = 'dashboard.html';
        });
    }

    // --- 2. التعامل مع فورم "نسيت كلمة المرور" ---
    const forgotPasswordForm = document.getElementById('forgot-password-form');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const email = document.getElementById('email').value;

            if (email.trim() === '') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please enter your email address.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
                return;
            }
            console.log('Simulating sending code...');
            window.location.href = 'reset-code.html';
        });
    }

    // --- 3. التعامل مع فورم "إدخال الكود" ---
    const resetCodeForm = document.getElementById('reset-code-form');
    if (resetCodeForm) {
        resetCodeForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const code = document.getElementById('verification-code').value;

            if (code.trim() === '') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please enter the verification code.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
                return;
            }
            console.log('Simulating code verification...');
            window.location.href = 'set-new-password.html';
        });
    }

    // --- 4. التعامل مع فورم "تعيين كلمة مرور جديدة" ---
    const setPasswordForm = document.getElementById('set-password-form');
    if (setPasswordForm) {
        setPasswordForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const newPassword = document.getElementById('new-password-input').value;
            const confirmPassword = document.getElementById('confirm-password-input').value;

            if (newPassword.trim() === '' || confirmPassword.trim() === '') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please fill in both password fields.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
                return;
            }
            if (newPassword !== confirmPassword) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Passwords do not match. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
                return;
            }

            // تنبيه النجاح!
            Swal.fire({
                title: 'Success!',
                text: 'Your password has been changed successfully!',
                icon: 'success', // أيقونة نجاح
                confirmButtonText: 'Login',
                background: '#1a1a1a',
                color: '#ffffff'
            }).then((result) => {
                // بعد الضغط على OK، يتم التحويل
                if (result.isConfirmed) {
                    window.location.href = ' SignIn_page.html';
                }
            });
        });
    }
});