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

// Handle the form submission if a patient is selected and EMR is to be updated
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update') {
            $emr_id = $_POST['emr_id'];
            $patient_id = $_POST['patient_id'];
            $doctor_id = $_POST['doctor_id'];
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
                // Update the EMR data in the database with encrypted values
                $update_query = "UPDATE emr 
                                SET notes = '$encrypted_notes', 
                                    diagnosis = '$encrypted_diagnosis', 
                                    prescribed_medications = '$encrypted_medications' 
                                WHERE emr_id = '$emr_id' AND patient_id = '$patient_id' AND doctor_id = '$doctor_id'";

                if ($conn->query($update_query)) {
                    $success_message = "EMR updated successfully.";
                } else {
                    $error_message = "Error updating EMR: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $emr_id = $_POST['emr_id'];
            $doctor_id = $_POST['doctor_id'];

            // Verify that the logged-in doctor is the one who created the EMR
            $verify_query = "SELECT doctor_id FROM emr WHERE emr_id = '$emr_id'";
            $verify_result = $conn->query($verify_query);
            
            if ($verify_result && $verify_result->num_rows > 0) {
                $emr_data = $verify_result->fetch_assoc();
                if ($emr_data['doctor_id'] == $doctor_id) {
                    // Delete the EMR data
                    $delete_query = "DELETE FROM emr WHERE emr_id = '$emr_id' AND doctor_id = '$doctor_id'";
                    if ($conn->query($delete_query)) {
                        $success_message = "EMR deleted successfully.";
                    } else {
                        $error_message = "Error deleting EMR: " . $conn->error;
                    }
                } else {
                    $error_message = "You don't have permission to delete this EMR.";
                }
            } else {
                $error_message = "EMR not found.";
            }
        }
    }
}

// Update the patient query to include a flag for doctor's patients
$query = "SELECT u.user_id, u.full_name, u.email, 
          CASE WHEN e.doctor_id = '{$_SESSION['user_id']}' THEN 1 ELSE 0 END as is_my_patient
          FROM users u 
          LEFT JOIN emr e ON u.user_id = e.patient_id 
          WHERE u.role = 'patient'
          GROUP BY u.user_id, u.full_name, u.email";
$result = $conn->query($query);

// Handle selecting a patient and fetching their EMR data
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
$emr_data = null;

if ($patient_id) {
    // Query to fetch EMR for the selected patient
    $emr_query = "SELECT * FROM emr WHERE patient_id = '$patient_id'";
    $emr_result = $conn->query($emr_query);
    if ($emr_result && $emr_result->num_rows > 0) {
        $emr_data = $emr_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Patient EMR</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
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
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .btn-view-emr {
            padding: 0.25rem 1rem;
            font-weight: 500;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            background-color: #f8f9fa;
            border-radius: 15px 15px 0 0;
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
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
                        <i class="bi bi-file-medical me-2"></i>Patient EMR Reports
                    </h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-file-medical me-2"></i>
                        Patient EMR Management
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h4 mb-0">Select a Patient to View and Edit EMR</h2>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="myPatientsFilter" onchange="togglePatientFilter()">
                                <label class="form-check-label" for="myPatientsFilter">Show Only My Patients</label>
                            </div>
                        </div>

                        <!-- Success or error messages -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $success_message; ?>
                            </div>
                        <?php elseif (!empty($error_message)): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        
                        <!-- Desktop Table View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle" id="patientsTable">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="patient-row <?php echo $row['is_my_patient'] ? 'my-patient' : 'other-patient'; ?>">
                                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <?php if ($row['is_my_patient']): ?>
                                                    <span class="badge bg-success">My Patient</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Other Patient</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-view-emr" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#emrModal<?php echo $row['user_id']; ?>">
                                                    <i class="bi bi-eye me-1"></i>View EMR
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="d-md-none">
                            <?php 
                            // Reset the result pointer
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <div class="card mb-3 patient-card <?php echo $row['is_my_patient'] ? 'my-patient' : 'other-patient'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($row['full_name']); ?></h6>
                                            <span class="badge bg-secondary">ID: <?php echo htmlspecialchars($row['user_id']); ?></span>
                                        </div>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($row['email']); ?>
                                        </p>
                                        <p class="card-text mb-3">
                                            <?php if ($row['is_my_patient']): ?>
                                                <span class="badge bg-success">My Patient</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Other Doctor's Patient</span>
                                            <?php endif; ?>
                                        </p>
                                        <button type="button" class="btn btn-primary btn-sm w-100" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#emrModal<?php echo $row['user_id']; ?>">
                                            <i class="bi bi-eye me-1"></i>View EMR
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- EMR Modals -->
                        <?php 
                        // Reset the result pointer again for modals
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <!-- EMR Modal for each patient -->
                            <div class="modal fade" id="emrModal<?php echo $row['user_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-file-medical me-2"></i>
                                                EMR Data for <?php echo htmlspecialchars($row['full_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            $patient_emr_query = "SELECT * FROM emr WHERE patient_id = '{$row['user_id']}'";
                                            $patient_emr_result = $conn->query($patient_emr_query);
                                            if ($patient_emr_result && $patient_emr_result->num_rows > 0):
                                                $patient_emr = $patient_emr_result->fetch_assoc();
                                            ?>
                                                <?php if ($patient_emr['doctor_id'] != $_SESSION['user_id']): ?>
                                                    <div class="alert alert-warning d-flex align-items-center">
                                                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                                                        You do not have permission to edit this EMR data. This data belongs to another doctor.
                                                    </div>
                                                <?php else: ?>
                                                    <form action="reports.php" method="POST" class="needs-validation" novalidate>
                                                        <input type="hidden" name="emr_id" value="<?php echo $patient_emr['emr_id']; ?>">
                                                        <input type="hidden" name="patient_id" value="<?php echo $patient_emr['patient_id']; ?>">
                                                        <input type="hidden" name="doctor_id" value="<?php echo $_SESSION['user_id']; ?>">
                                                        <input type="hidden" name="action" value="update">

                                                        <div class="mb-3">
                                                            <label for="notes<?php echo $row['user_id']; ?>" class="form-label">Clinical Notes:</label>
                                                            <textarea name="notes" id="notes<?php echo $row['user_id']; ?>" class="form-control" required><?php echo htmlspecialchars($patient_emr['notes']); ?></textarea>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="diagnosis<?php echo $row['user_id']; ?>" class="form-label">Diagnosis:</label>
                                                            <textarea name="diagnosis" id="diagnosis<?php echo $row['user_id']; ?>" class="form-control" required><?php echo htmlspecialchars($patient_emr['diagnosis']); ?></textarea>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="prescribed_medications<?php echo $row['user_id']; ?>" class="form-label">Prescribed Medications:</label>
                                                            <textarea name="prescribed_medications" id="prescribed_medications<?php echo $row['user_id']; ?>" class="form-control" required><?php echo htmlspecialchars($patient_emr['prescribed_medications']); ?></textarea>
                                                        </div>

                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <div>
                                                                <button type="submit" class="btn btn-primary me-2">
                                                                    <i class="bi bi-save me-1"></i>Save Changes
                                                                </button>
                                                                <button type="button" class="btn btn-danger" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteConfirmModal<?php echo $row['user_id']; ?>"
                                                                        data-bs-dismiss="modal">
                                                                    <i class="bi bi-trash me-1"></i>Delete EMR
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="alert alert-info d-flex align-items-center">
                                                    <i class="bi bi-info-circle-fill me-2"></i>
                                                    No EMR data available for this patient.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <!-- Delete Confirmation Modals -->
                        <?php 
                        // Reset the result pointer for delete modals
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()): 
                            $patient_emr_query = "SELECT * FROM emr WHERE patient_id = '{$row['user_id']}'";
                            $patient_emr_result = $conn->query($patient_emr_query);
                            if ($patient_emr_result && $patient_emr_result->num_rows > 0):
                                $patient_emr = $patient_emr_result->fetch_assoc();
                                if ($patient_emr['doctor_id'] == $_SESSION['user_id']):
                        ?>
                            <div class="modal fade" id="deleteConfirmModal<?php echo $row['user_id']; ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title text-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                Confirm Deletion
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete the EMR data for <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>? This action cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form action="reports.php" method="POST" class="d-inline">
                                                <input type="hidden" name="emr_id" value="<?php echo $patient_emr['emr_id']; ?>">
                                                <input type="hidden" name="doctor_id" value="<?php echo $_SESSION['user_id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash me-1"></i>Delete EMR
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                                endif;
                            endif;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </main>
        </div>
        <br/>
        <br/>
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

    <!-- Add JavaScript for filtering -->
    <script>
        function togglePatientFilter() {
            const showOnlyMyPatients = document.getElementById('myPatientsFilter').checked;
            const rows = document.querySelectorAll('.patient-row');
            const cards = document.querySelectorAll('.patient-card');
            
            // Filter table rows
            rows.forEach(row => {
                if (showOnlyMyPatients) {
                    row.style.display = row.classList.contains('my-patient') ? '' : 'none';
                } else {
                    row.style.display = '';
                }
            });
            
            // Filter mobile cards
            cards.forEach(card => {
                if (showOnlyMyPatients) {
                    card.style.display = card.classList.contains('my-patient') ? '' : 'none';
                } else {
                    card.style.display = '';
                }
            });
        }

        // Add some CSS for the filter switch
        const style = document.createElement('style');
        style.textContent = `
            .form-switch .form-check-input {
                width: 3em;
                height: 1.5em;
                margin-top: 0.25em;
            }
            .form-switch .form-check-input:checked {
                background-color: #0d6efd;
                border-color: #0d6efd;
            }
            .patient-row.my-patient td {
                background-color: rgba(13, 110, 253, 0.05);
            }
            .patient-card.my-patient {
                border-left: 4px solid #0d6efd;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
