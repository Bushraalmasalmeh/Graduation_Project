/**
 * auth.js
 * إدارة تسجيل الدخول بناءً على Public.json
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // ============================================================
    // 1. تسجيل الدخول
    // ============================================================
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        const savedEmail = localStorage.getItem(CONFIG.REMEMBER_EMAIL_KEY);
        const emailInput = document.getElementById('email');
        const rememberCheckbox = document.getElementById('rememberMe');

        if (savedEmail && emailInput) {
            emailInput.value = savedEmail;
            if (rememberCheckbox) rememberCheckbox.checked = true;
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            const password = document.getElementById('password').value.trim();
            const submitBtn = loginForm.querySelector('input[type="submit"]');

            if (!email || !password) {
                Utils.showError('Please fill in all fields.');
                return;
            }

            try {
                Utils.setLoading(submitBtn.id || submitBtn, true);

                // 🟢 حسب Public.json الرابط هو /api/login
                const data = await API.post('/api/login', { email, password });

                // 🟢 حسب Public.json الرد يحتوي على "token" مباشرة
                if (data.token) {
                    localStorage.setItem(CONFIG.TOKEN_KEY, data.token);
                    
                    if (data.user) {
                        localStorage.setItem(CONFIG.USER_DATA_KEY, JSON.stringify(data.user));
                    }

                    if (rememberCheckbox && rememberCheckbox.checked) {
                        localStorage.setItem(CONFIG.REMEMBER_EMAIL_KEY, email);
                    } else {
                        localStorage.removeItem(CONFIG.REMEMBER_EMAIL_KEY);
                    }

                    window.location.href = 'dashboard.html';
                } else {
                    throw new Error('No token received from server.');
                }

            } catch (error) {
                Utils.showError(error.message || 'Invalid credentials');
            } finally {
                Utils.setLoading(submitBtn.id || submitBtn, false);
            }
        });
    }

    // ملاحظة: روابط نسيان كلمة المرور غير موجودة في ملفات Postman المرفقة
    // لذلك تركت الكود القديم كما هو، لكنه لن يعمل إلا إذا أضاف المطور الروابط.
});