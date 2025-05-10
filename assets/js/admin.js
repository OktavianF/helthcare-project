// Admin JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Toggle mobile menu
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenuToggle.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
    
    // Dashboard tabs functionality
    setupDashboardTabs();
    
    // Auto-refresh verification page every 30 seconds
    if (window.location.href.includes('verify-patients.php')) {
        setInterval(() => {
            const currentUrl = window.location.href;
            // Preserve the current scroll position
            const scrollPos = window.scrollY;
            
            // Store this in localStorage
            localStorage.setItem('verifyScrollPos', scrollPos);
            
            // Reload the page
            window.location.href = currentUrl;
        }, 30000);
        
        // Restore scroll position after reload
        const savedScrollPos = localStorage.getItem('verifyScrollPos');
        if (savedScrollPos) {
            window.scrollTo(0, parseInt(savedScrollPos));
            localStorage.removeItem('verifyScrollPos');
        }
    }
});

// Setup dashboard tabs
function setupDashboardTabs() {
    const dashboardTabs = document.querySelectorAll('.dashboard-tab');
    const dashboardContents = document.querySelectorAll('.dashboard-content');
    
    if (dashboardTabs.length === 0) return;
    
    dashboardTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs and contents
            dashboardTabs.forEach(t => t.classList.remove('active'));
            dashboardContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            tab.classList.add('active');
            const tabId = tab.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
}

// Polyclinic form validation
function validatePolyclinicForm(form) {
    const name = form.querySelector('[name="name"]').value.trim();
    const description = form.querySelector('[name="description"]').value.trim();
    const dailyQuota = parseInt(form.querySelector('[name="daily_quota"]').value);
    
    let isValid = true;
    
    if (name === '') {
        showError(form.querySelector('[name="name"]'), 'Polyclinic name is required');
        isValid = false;
    }
    
    if (description === '') {
        showError(form.querySelector('[name="description"]'), 'Description is required');
        isValid = false;
    }
    
    if (isNaN(dailyQuota) || dailyQuota <= 0) {
        showError(form.querySelector('[name="daily_quota"]'), 'Daily quota must be a positive number');
        isValid = false;
    }
    
    return isValid;
}