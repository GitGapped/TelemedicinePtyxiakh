<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['role'] === 'doctor') {
        header("Location: dashboard.php");
    } else if ($_SESSION['role'] === 'patient') {
        header("Location: patient_dashboard.php");
    } 
    exit();
}
else{
    header("Location: login.php");
}

// Include session manager
require_once '../includes/session_manager.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... existing login code ...
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... existing head content ... -->
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (isset($_GET['session_expired'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Your session has expired. Please log in again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- ... rest of the login form ... -->
            </div>
        </div>
    </div>
</body>
</html> 