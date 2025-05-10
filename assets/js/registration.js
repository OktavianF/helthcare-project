// Registration JavaScript for register.php

document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registration-form');
    
    if (registrationForm) {
        registrationForm.addEventListener('submit', handleRegistration);
        
        // Add input validation
        const nikInput = document.getElementById('nik');
        const nameInput = document.getElementById('name');
        const addressInput = document.getElementById('address');
        const phoneInput = document.getElementById('phone');
        
        nikInput.addEventListener('input', validateNIK);
        phoneInput.addEventListener('input', validatePhone);
        
        // Basic validation functions
        nameInput.addEventListener('blur', function() {
            if (nameInput.value.trim() === '') {
                showError(nameInput, 'Name is required');
            } else {
                removeError(nameInput);
            }
        });
        
        addressInput.addEventListener('blur', function() {
            if (addressInput.value.trim() === '') {
                showError(addressInput, 'Address is required');
            } else {
                removeError(addressInput);
            }
        });
    }
    
    // Check for available quota every 30 seconds
    const polyclinicId = document.getElementById('polyclinic_id')?.value;
    if (polyclinicId) {
        setInterval(() => {
            checkAvailableQuota(polyclinicId);
        }, 30000);
    }
});

// Validate NIK (16 digits)
function validateNIK() {
    const nikInput = document.getElementById('nik');
    const nik = nikInput.value.trim();
    
    if (nik === '') {
        showError(nikInput, 'NIK is required');
        return false;
    } else if (!/^\d+$/.test(nik)) {
        showError(nikInput, 'NIK must contain numbers only');
        return false;
    } else if (nik.length !== 16) {
        showError(nikInput, 'NIK must be 16 digits');
        return false;
    } else {
        removeError(nikInput);
        return true;
    }
}

// Validate phone (numbers only)
function validatePhone() {
    const phoneInput = document.getElementById('phone');
    const phone = phoneInput.value.trim();
    
    if (phone === '') {
        showError(phoneInput, 'Phone number is required');
        return false;
    } else if (!/^\d+$/.test(phone)) {
        showError(phoneInput, 'Phone number must contain numbers only');
        return false;
    } else {
        removeError(phoneInput);
        return true;
    }
}

// Handle registration form submission
async function handleRegistration(event) {
    event.preventDefault();
    
    // Get form values
    const polyclinicId = document.getElementById('polyclinic_id').value;
    const nik = document.getElementById('nik').value.trim();
    const name = document.getElementById('name').value.trim();
    const address = document.getElementById('address').value.trim();
    const phone = document.getElementById('phone').value.trim();
    
    // Validate form
    if (!validateNIK() || name === '' || address === '' || !validatePhone()) {
        return;
    }
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    submitButton.textContent = 'Registering...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch('api/register-patient.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                polyclinic_id: polyclinicId,
                nik,
                name,
                address,
                phone
            })
        });
        
        const data = await response.json();
        
        // Hide form and show result
        const registrationForm = document.getElementById('registration-form');
        const registrationResult = document.getElementById('registration-result');
        
        if (data.success) {
            registrationForm.style.display = 'none';
            registrationResult.classList.remove('hidden');
            registrationResult.classList.add('fade-in');
            
            registrationResult.innerHTML = `
                <h3>Registration Successful!</h3>
                <p>Thank you for registering, ${name}.</p>
                <p>Your registration code is:</p>
                <div class="registration-code">${data.registration_code}</div>
                <p>Please keep this code safe. You will need it to check your queue status.</p>
                <p>You have been registered for: <strong>${data.polyclinic_name}</strong></p>
                <div class="form-actions" style="margin-top: 24px;">
                    <a href="queue-status.php?code=${data.registration_code}" class="btn btn-primary">Check Queue Status</a>
                    <a href="index.php" class="btn btn-secondary">Return to Home</a>
                </div>
            `;
            
            // Update available quota display
            document.getElementById('available-slots').textContent = data.available_quota;
        } else {
            // Show error message
            const errorElement = document.createElement('div');
            errorElement.className = 'alert alert-error';
            errorElement.textContent = data.message || 'Registration failed. Please try again.';
            
            registrationForm.prepend(errorElement);
            
            // Reset button
            submitButton.textContent = originalButtonText;
            submitButton.disabled = false;
        }
    } catch (error) {
        console.error('Registration error:', error);
        
        // Show error message
        const errorElement = document.createElement('div');
        errorElement.className = 'alert alert-error';
        errorElement.textContent = 'Registration failed. Please check your connection and try again.';
        
        document.getElementById('registration-form').prepend(errorElement);
        
        // Reset button
        submitButton.textContent = originalButtonText;
        submitButton.disabled = false;
    }
}

// Check if quota is still available
async function checkAvailableQuota(polyclinicId) {
    try {
        const response = await fetch(`api/get-polyclinics.php?id=${polyclinicId}`);
        const data = await response.json();
        
        if (data.success && data.polyclinics && data.polyclinics.length > 0) {
            const polyclinic = data.polyclinics[0];
            document.getElementById('available-slots').textContent = polyclinic.available_quota;
            
            // Redirect or show message if quota is full
            if (polyclinic.available_quota <= 0 && !document.querySelector('.alert-error')) {
                const registrationForm = document.getElementById('registration-form');
                if (registrationForm) {
                    registrationForm.innerHTML = `
                        <div class="alert alert-error">
                            <p>We're sorry, but this polyclinic has reached its daily quota while you were on this page.</p>
                            <a href="index.php" class="btn btn-secondary">View Other Polyclinics</a>
                        </div>
                    `;
                }
            }
        }
    } catch (error) {
        console.error('Error checking quota:', error);
    }
}