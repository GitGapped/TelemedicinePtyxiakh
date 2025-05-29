<?php
// Start session to access the user data
session_start();

// Check if user is logged in and has the correct role (patient)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    // If not, redirect them to the login page
    header("Location: login.php");
    exit();
}

define('INCLUDED', true);
require_once '../database/db_connection.php'; // Make sure the DB connection is available

$user_id = $_SESSION['user_id'];  // Get the current user's ID from the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the submitted form data
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $medical_history = $_POST['medical_history'];
    $allergies = $_POST['allergies'];
    $current_medications = $_POST['current_medications'];

    // Validate input fields
    if (empty($date_of_birth) || empty($gender) || empty($contact_number) || empty($address)) {
        $error_message = "Date of birth, gender, contact number, and address are required fields.";
    } else {
        // Insert data into patient_profiles table with the current session's user_id as patient_id
        $stmt = $conn->prepare("INSERT INTO patient_profiles (patient_id, date_of_birth, gender, contact_number, address, medical_history, allergies, current_medications) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $date_of_birth, $gender, $contact_number, $address, $medical_history, $allergies, $current_medications);

        if ($stmt->execute()) {
            // Redirect the patient to their dashboard after successful profile creation
            header("Location: patient_dashboard.php");
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
    <title>Create Patient Profile</title>
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
                    <i class="bi bi-person-plus-fill me-2"></i>Complete Your Patient Profile
                </h2>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger profile-alert d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="create_patient_profile.php" method="POST" class="needs-validation profile-form" novalidate>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="date_of_birth" class="form-label required">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" required>
                        </div>

                        <div class="col-md-6">
                            <label for="gender" class="form-label required">Gender</label>
                            <select class="form-select" name="gender" id="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_number" class="form-label required">Contact Number</label>
                            <input type="tel" class="form-control" name="contact_number" id="contact_number" 
                                   placeholder="Enter your contact number" required>
                        </div>

                        <div class="col-md-6">
                            <label for="address" class="form-label required">Address</label>
                            <input type="text" class="form-control" name="address" id="address" 
                                   placeholder="Enter your full address" required>
                        </div>

                        <div class="col-12">
                            <label for="medical_history" class="form-label">Medical History</label>
                            <textarea class="form-control" name="medical_history" id="medical_history" rows="3"
                                    placeholder="List any significant medical conditions or past surgeries..."></textarea>
                        </div>

                        <div class="col-12">
                            <label for="allergies" class="form-label">Allergies</label>
                            <textarea class="form-control" name="allergies" id="allergies" rows="2"
                                    placeholder="List any allergies you have (medications, food, etc.)..."></textarea>
                        </div>

                        <div class="col-12">
                            <label for="current_medications" class="form-label">Current Medications</label>
                            <textarea class="form-control" name="current_medications" id="current_medications" rows="2"
                                    placeholder="List any medications you are currently taking..."></textarea>
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
