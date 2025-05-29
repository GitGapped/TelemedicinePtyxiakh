<?php
// Start session to access the user data
session_start();

// Check if the user is logged in and has the correct role (doctor)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    // If not, redirect them to the home page
    header("Location: home.php");
    exit(); // Terminate further execution of the script
}

// Include database connection
require_once '../database/db_connection.php';
require_once '../includes/encryption.php';  // Add encryption include

define('INCLUDED', true);
// Variables to control flow
$patient_exists = false;
$existing_patient_id = null;
$emr_saved_message = '';
$error_message = '';
// Handle the form submission to insert new EMR data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_SESSION['user_id']; // The logged-in doctor
    $notes = $conn->real_escape_string($_POST['notes']);
    $diagnosis = $conn->real_escape_string($_POST['diagnosis']);
    $prescribed_medications = $conn->real_escape_string($_POST['prescribed_medications']);

    // Encrypt the EMR data
    $encrypted_notes = encryptMessage($notes);
    $encrypted_diagnosis = encryptMessage($diagnosis);
    $encrypted_medications = encryptMessage($prescribed_medications);

    if ($encrypted_notes === false || $encrypted_diagnosis === false || $encrypted_medications === false) {
        $error_message = "Error encrypting EMR data. Please try again.";
    } else {
        // Check if EMR data already exists for the selected patient
        $check_query = "SELECT emr_id FROM emr WHERE patient_id = '$patient_id' ";
        $check_result = $conn->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            // EMR already exists
            $patient_exists = true;
            $existing_patient_id = $patient_id;
        } else {
            // Insert new EMR data into the database with encrypted values
            $insert_query = "INSERT INTO emr (patient_id, doctor_id, notes, diagnosis, prescribed_medications, created_at)
                            VALUES ('$patient_id', '$doctor_id', '$encrypted_notes', '$encrypted_diagnosis', '$encrypted_medications', NOW())";

            if ($conn->query($insert_query)) {
                $emr_saved_message = "EMR data saved successfully.";
            } else {
                $error_message = "Error saving EMR data: " . $conn->error;
            }
        }
    }
}

// Fetch all patients for the doctor to select from
$query = "SELECT user_id, full_name FROM users WHERE role = 'patient'";
$patients_result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter EMR Data</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            font-weight: 500;
            border-radius: 15px 15px 0 0;
            padding: 1rem;
        }
        .btn-primary {
            padding: 0.5rem 2rem;
            font-weight: 500;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once '../includes/doctor_sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-file-medical me-2"></i>Enter EMR Data
                    </h1>
                </div>

                <div class="row justify-content-center">
                    <div class="col-12 col-xl-10">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-file-medical me-2"></i>
                                New EMR Entry
                            </div>
                            <div class="card-body p-4">
                                <!-- Success or error messages -->
                                <?php if (!empty($emr_saved_message)): ?>
                                    <div class="alert alert-success d-flex align-items-center" role="alert">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <?php echo $emr_saved_message; ?>
                                    </div>
                                <?php elseif (!empty($error_message)): ?>
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Alert if EMR already exists -->
                                <?php if ($patient_exists): ?>
                                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                                        <div>
                                            <p class="mb-2">EMR data for this patient already exists. Do you want to edit the existing data?</p>
                                            <a href="reports.php?patient_id=<?php echo $existing_patient_id; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil-square me-1"></i>Edit Existing EMR
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- EMR Data Entry Form -->
                                <form action="enterEMRData.php" method="POST" class="needs-validation" novalidate>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <select name="patient_id" id="patient_id" class="form-select" required>
                                                    <option value="">Select a Patient</option>
                                                    <?php while ($row = $patients_result->fetch_assoc()): ?>
                                                        <option value="<?php echo $row['user_id']; ?>"><?php echo htmlspecialchars($row['full_name']); ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <label for="patient_id">Select Patient</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating mb-3">
                                                <input type="text" class="form-control" id="doctor_id" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" disabled>
                                                <label for="doctor_id">Doctor</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <textarea name="notes" id="notes" class="form-control" required></textarea>
                                                <label for="notes">Clinical Notes</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <textarea name="diagnosis" id="diagnosis" class="form-control" required></textarea>
                                                <label for="diagnosis">Diagnosis</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <textarea name="prescribed_medications" id="prescribed_medications" class="form-control" required></textarea>
                                                <label for="prescribed_medications">Prescribed Medications</label>
                                            </div>
                                        </div>

                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-save me-2"></i>Save EMR Data
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include_once '../includes/footer.php'; ?>

    
    <!-- Form validation script -->
    <script>
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
