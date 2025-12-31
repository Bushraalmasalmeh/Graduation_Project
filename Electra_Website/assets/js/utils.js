// assets/js/utils.js

const Utils = {
    // 1. حماية النصوص (XSS Protection) - مهم جداً!
    escapeHTML: (str) => {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag]));
    },

    // 2. إظهار حالة التحميل (Loading State) للأزرار
    setLoading: (buttonId, isLoading, originalText = 'Submit') => {
        const btn = document.getElementById(buttonId);
        if (!btn) return;

        if (isLoading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.value || btn.innerText; // نحفظ النص الأصلي
            // تغيير النص أو إضافة أيقونة تحميل
            if (btn.tagName === 'INPUT') {
                btn.value = 'Processing...';
            } else {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        } else {
            btn.disabled = false;
            if (btn.tagName === 'INPUT') {
                btn.value = originalText;
            } else {
                btn.innerText = originalText;
            }
        }
    },

    // 3. عرض رسالة خطأ عامة
    showError: (message) => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message || 'Something went wrong!',
            confirmButtonColor: '#d33'
        });
    },

    // 4. عرض رسالة نجاح
    showSuccess: (title, message) => {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonColor: '#66cd00',
            timer: 2000,
            showConfirmButton: false
        });
    }
};