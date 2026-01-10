/**
 * Global Utilities for ELECTRA Project
 * Focused on Security, UI State, and Amman Timezone consistency.
 */
const Utils = {
    // Loading message helper
    showLoading: (message = 'Loading...') => {
        Swal.fire({
            // Use custom HTML for the title styling
            title: `<div style="color: #66cd00; font-weight: 600; font-size: 1.2rem;">${message}</div>`,
            allowOutsideClick: false,
            showConfirmButton: false,
            padding: '30px',
            customClass: {
                popup: 'border-gradient-green' // Custom border class (defined in CSS)
            },
            didOpen: () => {
                Swal.showLoading();
                // Color the loader ring green
                const loader = Swal.getPopup().querySelector('.swal2-loader');
                if (loader) loader.style.borderColor = '#66cd00 transparent #66cd00 transparent';
            }
        });
    },

    // Success message helper
    showSuccess: async (title, text) => {
        return Swal.fire({
            icon: 'success',
            title: `<span style="color: #66cd00; font-weight: bold;">${title}</span>`,
            html: `<div style="color: #e0e0e0; font-size: 1.1rem; margin-top: 10px;">${text}</div>`,
            background: '#1a1a1a',
            confirmButtonColor: '#66cd00',
            confirmButtonText: 'success✨',
            buttonsStyling: false, // Disable default Bootstrap styling
            customClass: {
                popup: 'border-gradient-green box-shadow-green',
                confirmButton: 'btn btn-success-custom px-5 py-2' // Use our custom button class
            }
        });
    },

    // Error message helper
    showError: async (text) => {
        return Swal.fire({
            icon: 'error',
            title: `<span style="color: #ff4d4d; font-weight: bold;">Sorrt...</span>`,
            html: `<div style="color: #e0e0e0; font-size: 1.1rem; margin-top: 10px;">${text}</div>`,
            background: '#1a1a1a',
            confirmButtonColor: '#ff4d4d',
            confirmButtonText: 'Try Again',
            buttonsStyling: false,
            customClass: {
                popup: 'border-gradient-red box-shadow-red',
                confirmButton: 'btn btn-danger-custom px-5 py-2'
            }
        });
    },
    
    // 1. XSS Protection: Clean strings before rendering to DOM
    escapeHTML: (str) => {
        if (!str) return '';
        const chars = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        };
        return String(str).replace(/[&<>'"]/g, tag => chars[tag]);
        
    },    
    // Optional: hide loading dialog
    hideLoading: () => {
        Swal.close();
    },


    // 2. Intelligent loading state: handles both buttons and inputs
    setLoading: (buttonId, isLoading, loadingText = 'Processing...') => {
        const btn = document.getElementById(buttonId);
        if (!btn) return;

        if (isLoading) {
            // Save original state to restore it later automatically
            btn.dataset.originalContent = btn.innerHTML;
            btn.dataset.originalValue = btn.value;
            btn.disabled = true;

            if (btn.tagName === 'INPUT') {
                btn.value = loadingText;
            } else {
                btn.innerHTML = `<i class="fas fa-circle-notch fa-spin me-2"></i> ${loadingText}`;
            }
        } else {
            btn.disabled = false;
            // Restore from saved dataset
            if (btn.tagName === 'INPUT') {
                btn.value = btn.dataset.originalValue || 'Submit';
            } else {
                btn.innerHTML = btn.dataset.originalContent || 'Submit';
            }
        }
    },

formatAmmanTime: function(dateString) {
    if (!dateString) return '--/--/---- --:-- --';

    // Trim T, Z, and any offset (+00:00) sent by the server
    const cleanDate = dateString.replace('T', ' ')
                                .replace('Z', '')
                                .replace(/\+.*/, ''); // Removes any offset such as +00:00
    
    const date = new Date(cleanDate);

    if (isNaN(date.getTime())) return dateString;

    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        // After cleaning, keep local interpretation as-is (no explicit tz)
    };

    return date.toLocaleString('en-GB', options);
},

    // 4. Standardized alerts (SweetAlert2)
    showError: (message) => {
        Swal.fire({
            icon: 'error',
            title: 'Action Failed',
            text: message || 'Something went wrong!',
            confirmButtonColor: '#581c1c' // Matching your btn-danger-custom
        });
    },

    showSuccess: (title, message) => {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonColor: 'var(--primary-color)',
            timer: 2000,
            showConfirmButton: false
        });
    },

    // 5. Confirmation Dialog: Useful for Delete/Cancel actions
    confirmAction: async (title, text) => {
        const result = await Swal.fire({
            title: title || 'Are you sure?',
            text: text || "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary-color)',
            cancelButtonColor: '#333',
            confirmButtonText: 'Yes, proceed!'
        });
        return result.isConfirmed;
    }
};
