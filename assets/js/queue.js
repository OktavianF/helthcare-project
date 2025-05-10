// Queue status JavaScript for queue-status.php

document.addEventListener('DOMContentLoaded', function() {
    // Set up tabs
    setupTabs();
    
    // Check if there's a code in URL
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    
    if (code) {
        // Pre-fill the registration code
        document.getElementById('registration_code').value = code;
        // Automatically submit the form
        document.getElementById('code-lookup-form').dispatchEvent(new Event('submit'));
    }
    
    // Set up code lookup form
    const lookupForm = document.getElementById('code-lookup-form');
    if (lookupForm) {
        lookupForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const code = document.getElementById('registration_code').value.trim();
            
            if (!code) {
                showError(document.getElementById('registration_code'), 'Please enter a registration code');
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.textContent = 'Checking...';
            submitButton.disabled = true;
            
            try {
                await checkPatientStatus(code);
                
                // Reset button
                submitButton.textContent = originalButtonText;
                submitButton.disabled = false;
            } catch (error) {
                console.error('Error checking status:', error);
                
                // Show error message
                document.getElementById('patient-status').innerHTML = `
                    <div class="alert alert-error">
                        An error occurred while checking your status. Please try again.
                    </div>
                `;
                document.getElementById('patient-status').classList.remove('hidden');
                
                // Reset button
                submitButton.textContent = originalButtonText;
                submitButton.disabled = false;
            }
        });
    }
    
    // Load all current queues
    loadCurrentQueues();
    
    // Set interval to refresh statuses
    setInterval(function() {
        const patientStatus = document.getElementById('patient-status');
        const regCode = document.getElementById('registration_code').value;
        
        if (!patientStatus.classList.contains('hidden') && regCode) {
            checkPatientStatus(regCode, true);
        }
        
        loadCurrentQueues();
    }, 10000);
});

// Check patient status by registration code
async function checkPatientStatus(code, silent = false) {
    try {
        const response = await fetch(`api/get-queue-status.php?code=${code}`);
        const data = await response.json();
        
        const patientStatus = document.getElementById('patient-status');
        
        if (data.success) {
            // Build status display
            let statusHtml = '';
            let statusClass = '';
            let estimatedTime = '';
            
            switch (data.status) {
                case 'pending':
                    statusClass = 'status-pending';
                    statusHtml = `
                        <div class="status-icon pending"></div>
                        <h3>Registration Pending Verification</h3>
                        <p>Your registration is currently pending verification by our staff.</p>
                    `;
                    break;
                case 'verified':
                    statusClass = 'status-verified';
                    estimatedTime = data.position > 1 ? `(Estimated wait: ~${data.position * 10} minutes)` : '';
                    statusHtml = `
                        <div class="status-icon verified"></div>
                        <h3>In Queue: ${data.queue_number}</h3>
                        <p>Your registration has been verified.</p>
                        <p>Current position: ${data.position} ${estimatedTime}</p>
                        <p>Current number being served: ${data.current_number || 'None yet'}</p>
                    `;
                    break;
                case 'serving':
                    statusClass = 'status-serving';
                    statusHtml = `
                        <div class="status-icon serving"></div>
                        <h3>Now Serving: ${data.queue_number}</h3>
                        <p>It's your turn! Please proceed to the ${data.polyclinic_name} area.</p>
                    `;
                    break;
                case 'completed':
                    statusClass = 'status-completed';
                    statusHtml = `
                        <div class="status-icon completed"></div>
                        <h3>Service Completed</h3>
                        <p>Your visit has been completed. Thank you for using our services!</p>
                    `;
                    break;
                case 'cancelled':
                    statusClass = 'status-cancelled';
                    statusHtml = `
                        <div class="status-icon cancelled"></div>
                        <h3>Registration Cancelled</h3>
                        <p>Your registration has been cancelled. Please register again if needed.</p>
                    `;
                    break;
                default:
                    statusClass = 'status-unknown';
                    statusHtml = `
                        <div class="status-icon unknown"></div>
                        <h3>Unknown Status</h3>
                        <p>We could not determine your current status.</p>
                    `;
            }
            
            patientStatus.innerHTML = `
                <div class="patient-status-content ${statusClass}">
                    ${statusHtml}
                    <div class="patient-details">
                        <p><strong>Name:</strong> ${data.name}</p>
                        <p><strong>Polyclinic:</strong> ${data.polyclinic_name}</p>
                        <p><strong>Registration Time:</strong> ${data.registration_time}</p>
                    </div>
                </div>
            `;
            
            // Add auto-refresh note
            patientStatus.innerHTML += `
                <p class="refresh-note">This status updates automatically every 10 seconds.</p>
            `;
            
            if (!silent) {
                patientStatus.classList.remove('hidden');
                patientStatus.scrollIntoView({ behavior: 'smooth' });
            }
        } else {
            // Show error message only if not silent mode
            if (!silent) {
                patientStatus.innerHTML = `
                    <div class="alert alert-error">
                        ${data.message || 'Registration code not found. Please check and try again.'}
                    </div>
                `;
                patientStatus.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('Error checking patient status:', error);
        throw error;
    }
}

// Load all current queues
async function loadCurrentQueues() {
    try {
        const response = await fetch('api/get-current-queues.php');
        const data = await response.json();
        
        const queuesContainer = document.getElementById('current-queues');
        
        if (data.success) {
            if (data.queues.length === 0) {
                queuesContainer.innerHTML = `
                    <div class="info-message">
                        No active queues at the moment.
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            data.queues.forEach(queue => {
                html += `
                    <div class="queue-card">
                        <div class="queue-info">
                            <h3>${queue.polyclinic_name}</h3>
                            <p>Currently serving: <span class="queue-number">${queue.current_number || 'None yet'}</span></p>
                        </div>
                        <div class="queue-stats">
                            <p>Waiting: ${queue.waiting_count}</p>
                            <p>Completed: ${queue.completed_count}</p>
                        </div>
                    </div>
                `;
            });
            
            queuesContainer.innerHTML = html;
        } else {
            queuesContainer.innerHTML = `
                <div class="alert alert-error">
                    ${data.message || 'Error loading queue data. Please try again later.'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading current queues:', error);
        document.getElementById('current-queues').innerHTML = `
            <div class="alert alert-error">
                Could not connect to the server. Please check your internet connection and try again.
            </div>
        `;
    }
}