<?php
// Session timeout in seconds (1 hour)
define('SESSION_TIMEOUT', 3600);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if session has expired
function checkSessionExpiration() {
    if (isset($_SESSION['last_activity'])) {
        // Check if session has expired
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            // Clear all session data
            $_SESSION = array();
            
            // Destroy the session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            // Destroy the session
            session_destroy();
            
            // Redirect to login page with expired message
            header("Location: /ProjectPtyxiakhcursorNEWEST/ProjectPtyxiakhcursor2/public/home.php?session_expired=1");
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Function to regenerate session ID periodically
function regenerateSession() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } else {
        // Regenerate session ID every 5 minutes
        if (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Check session expiration and regenerate session
checkSessionExpiration();
regenerateSession(); 