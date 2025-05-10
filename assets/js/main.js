// Main JavaScript for all pages

// Toggle mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenuToggle.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
    
    // Add fade-in animation to elements
    const fadeElements = document.querySelectorAll('.hero, .polyclinic-card, .feature-card');
    fadeElements.forEach(el => {
        el.classList.add('fade-in');
    });
});

// Helper function to show error message
function showError(element, message) {
    // Create error message element if it doesn't exist
    let errorElement = element.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('form-error')) {
        errorElement = document.createElement('div');
        errorElement.className = 'form-error';
        errorElement.style.color = 'var(--error-color)';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '4px';
        element.parentNode.insertBefore(errorElement, element.nextSibling);
    }
    
    errorElement.textContent = message;
    element.classList.add('error');
    element.setAttribute('aria-invalid', 'true');
}

// Helper function to remove error message
function removeError(element) {
    const errorElement = element.nextElementSibling;
    if (errorElement && errorElement.classList.contains('form-error')) {
        errorElement.textContent = '';
    }
    element.classList.remove('error');
    element.setAttribute('aria-invalid', 'false');
}

// Helper function for API requests
async function fetchAPI(url, method = 'GET', data = null) {
    try {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API request error:', error);
        throw error;
    }
}

// Tab functionality
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    if (tabButtons.length === 0) return;
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
}

// Call setupTabs on document load
document.addEventListener('DOMContentLoaded', setupTabs);