// ============================================
// SHARED MODAL + TOAST SYSTEM
// ============================================

// ============================================
// CONFIRMATION MODAL
// ============================================
let _currentConfirmCallback = null;

/**
 * Show a custom confirmation dialog
 * @param {Object} options - Configuration
 * @param {string} options.type - 'success' | 'danger' | 'warning' | 'info'
 * @param {string} options.title - Modal title
 * @param {string} options.message - HTML message (can include <strong>)
 * @param {string} options.confirmText - Text for confirm button
 * @param {string} options.confirmClass - Extra class for confirm button
 * @param {Function} options.onConfirm - Callback when confirmed
 */
function showConfirm(options) {
    const overlay = document.getElementById('confirmOverlay');
    if (!overlay) {
        console.error('Confirm modal HTML not found. Make sure to include the modal HTML.');
        // Fallback to browser confirm
        if (confirm(options.title + '\n\n' + (options.message || '').replace(/<[^>]+>/g, ''))) {
            if (options.onConfirm) options.onConfirm();
        }
        return;
    }
    
    const icon = document.getElementById('confirmIcon');
    const iconInner = document.getElementById('confirmIconInner');
    const title = document.getElementById('confirmTitle');
    const message = document.getElementById('confirmMessage');
    const yesBtn = document.getElementById('confirmYesBtn');
    
    // Set icon
    const iconMap = {
        success: 'bi-check-circle-fill',
        danger: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    
    icon.className = 'confirm-icon ' + (options.type || 'success');
    iconInner.className = 'bi ' + (iconMap[options.type] || 'bi-check-circle-fill');
    
    // Set content
    title.textContent = options.title || 'Confirm';
    message.innerHTML = options.message || 'Are you sure?';
    
    // Set confirm button
    const btnClass = 'btn-confirm-yes' + (options.confirmClass ? ' ' + options.confirmClass : (options.type === 'danger' ? ' danger' : (options.type === 'warning' ? ' warning' : '')));
    yesBtn.className = btnClass;
    yesBtn.innerHTML = '<i class="bi bi-check-lg"></i> ' + (options.confirmText || 'Confirm');
    
    // Store callback
    _currentConfirmCallback = options.onConfirm || null;
    
    // Show
    overlay.classList.add('show');
}

function closeConfirm() {
    const overlay = document.getElementById('confirmOverlay');
    if (overlay) overlay.classList.remove('show');
    _currentConfirmCallback = null;
}

// Auto-wire the yes/no buttons when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const yesBtn = document.getElementById('confirmYesBtn');
    const overlay = document.getElementById('confirmOverlay');
    
    if (yesBtn) {
        yesBtn.addEventListener('click', function() {
            const cb = _currentConfirmCallback;
            closeConfirm();
            if (cb) cb();
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeConfirm();
        });
    }
});

// Escape key closes modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeConfirm();
});

// ============================================
// TOAST NOTIFICATIONS
// ============================================
function showToast(title, message, type = 'success') {
    const toast = document.getElementById('appToast');
    if (!toast) {
        console.log('[Toast] ' + title + ': ' + message);
        return;
    }
    
    const iconMap = {
        success: 'bi-check-circle-fill',
        danger: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    
    // Reset classes
    toast.className = 'app-toast ' + (type !== 'success' ? type : '');
    
    document.getElementById('appToastTitle').textContent = title;
    document.getElementById('appToastMsg').textContent = message;
    document.getElementById('appToastIcon').className = 'bi ' + (iconMap[type] || 'bi-check-circle-fill');
    
    // Show
    setTimeout(() => toast.classList.add('show'), 50);
    
    // Auto hide after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// ============================================
// AUTO-DETECT SUCCESS MESSAGES FROM URL
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check for ?show_toast=message&toast_type=success
    if (urlParams.has('show_toast')) {
        const msg = urlParams.get('show_toast');
        const type = urlParams.get('toast_type') || 'success';
        const title = urlParams.get('toast_title') || 'Success!';
        
        showToast(title, decodeURIComponent(msg), type);
        
        // Clean URL
        urlParams.delete('show_toast');
        urlParams.delete('toast_type');
        urlParams.delete('toast_title');
        
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    }
});