<?php
require_once '../database/db_connection.php';
session_start();

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];  // Get the current user's ID from the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the submitted form data
    $specialty = $_POST['specialty'];
    $license_number = $_POST['license_number'];
    $availability = $_POST['availability'];

    // Validate input fields
    if (empty($specialty) || empty($license_number) || empty($availability)) {
        $error_message = "All fields are required.";
    } else {
        // Insert data into doctor_profiles table with the current session's user_id as doctor_id
        $stmt = $conn->prepare("INSERT INTO doctor_profiles (doctor_id, specialty, license_number, availability) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $specialty, $license_number, $availability);

        if ($stmt->execute()) {
            // Redirect the doctor to their dashboard after successful profile creation
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Error creating profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Doctor Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/styles.css" rel="stylesheet">
</head>
<body>
    <div class="profile-container">
        <div class="card profile-card">
            <div class="card-header">
                <h2 class="h3 mb-0 text-center">
                    <i class="bi bi-person-plus-fill me-2"></i>Complete Your Doctor Profile
                </h2>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger profile-alert d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="create_doctor_profile.php" method="POST" class="needs-validation profile-form" novalidate>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="specialty" class="form-label required">Specialty</label>
                            <input type="text" class="form-control" name="specialty" id="specialty" 
                                   placeholder="Enter your medical specialty" required>
                        </div>

                        <div class="col-md-6">
                            <label for="license_number" class="form-label required">License Number</label>
                            <input type="text" class="form-control" name="license_number" id="license_number" 
                                   placeholder="Enter your medical license number" required>
                        </div>

                        <div class="col-12">
                            <label for="availability" class="form-label required">Availability</label>
                            <textarea class="form-control" name="availability" id="availability" rows="3"
                                    placeholder="Describe your working hours and availability..." required></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label terms-text" for="terms">
                                    By creating a profile, I agree to the Terms of Service and Privacy Policy of the Telehealth System
                                </label>
                            </div>
                        </div>

                        <div class="col-12 d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Create Profile
                            </button>
                            <a href="logout.php" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Log Out
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
