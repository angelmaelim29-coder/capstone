// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });
    
    // Auto-calculate final grade
    document.querySelectorAll('.grade-input').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            if (!row) return;
            
            const prelim = parseFloat(row.querySelector('input[name*="[prelim]"]')?.value) || 0;
            const midterm = parseFloat(row.querySelector('input[name*="[midterm]"]')?.value) || 0;
            const prefinal = parseFloat(row.querySelector('input[name*="[prefinal]"]')?.value) || 0;
            const final = parseFloat(row.querySelector('input[name*="[final]"]')?.value) || 0;
            
            const grades = [prelim, midterm, prefinal, final].filter(g => g > 0);
            if (grades.length > 0) {
                const avg = grades.reduce((a, b) => a + b, 0) / grades.length;
                const finalInput = row.querySelector('input[name*="[grade]"]');
                if (finalInput) {
                    finalInput.value = avg.toFixed(2);
                }
            }
        });
    });
    
    // Confirm delete actions
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '<i class="bi bi-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    this.innerHTML = '<i class="bi bi-eye"></i>';
                }
            }
        });
    });
    
    // Handle notification clicks
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.dataset.notifId;
            if (notifId) {
                fetch(`api/notifications/mark_read.php?id=${notifId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('bg-light', 'unread');
                            updateUnreadCount();
                        }
                    });
            }
        });
    });
    
    function updateUnreadCount() {
        fetch('api/notifications/unread_count.php')
            .then(response => response.json())
            .then(data => {
                document.querySelectorAll('.unread-count').forEach(el => {
                    el.textContent = data.count;
                });
            });
    }
});

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function showToast(message, type = 'success') {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${colors[type] || '#1b3a5c'};
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: fadeInUp 0.5s ease;
        max-width: 400px;
        font-size: 14px;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 5000);
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const required = form.querySelectorAll('[required]');
    let valid = true;
    
    required.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return valid;
}

// AJAX Request Helper
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        if (method === 'POST') {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        
        xhr.onload = function() {
            if (this.status >= 200 && this.status < 300) {
                try {
                    resolve(JSON.parse(this.responseText));
                } catch (e) {
                    resolve(this.responseText);
                }
            } else {
                reject(new Error(this.statusText));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Network error'));
        };
        
        if (data && method === 'POST') {
            xhr.send(new URLSearchParams(data));
        } else {
            xhr.send();
        }
    });
}

// Export functions
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showToast = showToast;
window.validateForm = validateForm;
window.ajaxRequest = ajaxRequest;