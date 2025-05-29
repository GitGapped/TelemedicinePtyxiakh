// Session warning configuration
const SESSION_WARNING_TIME = 5 * 60 * 1000; // 5 minutes before expiration
const SESSION_TIMEOUT = 60 * 60 * 1000; // 1 hour total

// Function to show session expiration warning
function showSessionWarning() {
    // Only show warning if user is logged in
    if (document.querySelector('.welcome-message')) {
        const warningModal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
        warningModal.show();
        
        // Set timer for automatic logout if no action is taken
        window.expirationTimer = setTimeout(() => {
            window.location.href = 'logout.php';
        }, SESSION_WARNING_TIME);
    }
}

// Function to extend session
function extendSession() {
    // Clear the expiration timer
    if (window.expirationTimer) {
        clearTimeout(window.expirationTimer);
    }
    
    fetch('extend_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const warningModal = bootstrap.Modal.getInstance(document.getElementById('sessionWarningModal'));
                warningModal.hide();
                startSessionTimer();
            }
        })
        .catch(error => console.error('Error extending session:', error));
}

// Function to start session timer
function startSessionTimer() {
    // Only start timer if user is logged in
    if (!document.querySelector('.welcome-message')) {
        return;
    }

    // Clear any existing timers
    if (window.sessionTimer) {
        clearTimeout(window.sessionTimer);
    }
    if (window.expirationTimer) {
        clearTimeout(window.expirationTimer);
    }
    
    // Set timer for warning
    window.sessionTimer = setTimeout(() => {
        showSessionWarning();
    }, SESSION_TIMEOUT - SESSION_WARNING_TIME);
}

// Start the session timer when the page loads
document.addEventListener('DOMContentLoaded', () => {
    // Only start session management if user is logged in
    if (document.querySelector('.welcome-message')) {
        startSessionTimer();
        
        // Add event listeners for user activity
        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        activityEvents.forEach(event => {
            document.addEventListener(event, () => {
                startSessionTimer();
            });
        });
    }
}); 