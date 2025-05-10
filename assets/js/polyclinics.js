// Polyclinics JavaScript for index.php

document.addEventListener('DOMContentLoaded', function() {
    // Load polyclinics on page load
    loadPolyclinics();
    
    // Refresh polyclinics data every 30 seconds
    setInterval(loadPolyclinics, 30000);
});

// Load polyclinics data from API
async function loadPolyclinics() {
    try {
        const response = await fetch('api/get-polyclinics.php');
        const data = await response.json();
        
        if (data.success) {
            displayPolyclinics(data.polyclinics);
        } else {
            document.getElementById('polyclinic-list').innerHTML = `
                <div class="alert alert-error">
                    ${data.message || 'Error loading polyclinics. Please try again later.'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading polyclinics:', error);
        document.getElementById('polyclinic-list').innerHTML = `
            <div class="alert alert-error">
                Could not connect to the server. Please check your internet connection and try again.
            </div>
        `;
    }
}

// Display polyclinics in the page
function displayPolyclinics(polyclinics) {
    const container = document.getElementById('polyclinic-list');
    
    if (!polyclinics || polyclinics.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                No polyclinics are currently available.
            </div>
        `;
        return;
    }
    
    let html = '';
    
    polyclinics.forEach(poly => {
        // Determine quota status
        let quotaStatus = 'available';
        let quotaText = `${poly.available_quota} of ${poly.daily_quota} slots available`;
        
        if (poly.available_quota <= 0) {
            quotaStatus = 'full';
            quotaText = 'Fully booked today';
        } else if (poly.available_quota <= 5) {
            quotaStatus = 'limited';
            quotaText = `Only ${poly.available_quota} slots remaining!`;
        }
        
        html += `
            <div class="polyclinic-card">
                <h3>${poly.name}</h3>
                <p>${poly.description}</p>
                <span class="quota-badge ${quotaStatus}">${quotaText}</span>
                <p>Hours: ${formatTime(poly.start_time)} - ${formatTime(poly.end_time)}</p>
                <a href="register.php?id=${poly.id}" class="btn btn-primary" ${poly.available_quota <= 0 ? 'disabled' : ''}>
                    ${poly.available_quota <= 0 ? 'No Slots Available' : 'Register Now'}
                </a>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Add animation class to cards
    const cards = container.querySelectorAll('.polyclinic-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
}

// Format time from HH:MM:SS to HH:MM AM/PM
function formatTime(timeString) {
    if (!timeString) return '';
    
    const timeParts = timeString.split(':');
    let hours = parseInt(timeParts[0]);
    const minutes = timeParts[1];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // Convert 0 to 12
    
    return `${hours}:${minutes} ${ampm}`;
}