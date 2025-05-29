<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

require_once '../database/db_connection.php';
require_once '../includes/encryption.php';  // Add encryption include

define('INCLUDED', true);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch user basic data
$query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user_result = $query->get_result();
$user = $user_result->fetch_assoc();

// Fetch patient or doctor profile data
if ($role === 'patient') {
    $profile_query = $conn->prepare("SELECT * FROM patient_profiles WHERE patient_id = ?");
} else {
    $profile_query = $conn->prepare("SELECT * FROM doctor_profiles WHERE doctor_id = ?");
}
// Fetch EMR data
if ($role === 'patient') {
    $emr_query = $conn->prepare("SELECT emr.*, users.full_name AS doctor_name 
                                FROM emr 
                                JOIN users ON emr.doctor_id = users.user_id 
                                WHERE emr.patient_id = ?");
} else {
    $emr_query = $conn->prepare("SELECT emr.*, users.full_name AS patient_name 
                                FROM emr 
                                JOIN users ON emr.patient_id = users.user_id 
                                WHERE emr.doctor_id = ?");
}
$emr_query->bind_param("i", $user_id);
$emr_query->execute();
$emr_result = $emr_query->get_result();
$profile_query->bind_param("i", $user_id);
$profile_query->execute();
$profile_result = $profile_query->get_result();
$profile = $profile_result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);

    // Update users table
    $update_user = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
    $update_user->bind_param("ssi", $full_name, $email, $user_id);
    $update_user->execute();

    if ($role === 'patient') {
        $dob = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $contact_number = $_POST['contact_number'];
        $address = $conn->real_escape_string($_POST['address']);

        // Update patient_profile
        $update_profile = $conn->prepare("UPDATE patient_profiles SET date_of_birth = ?, gender = ?, contact_number = ?, address = ? WHERE patient_id = ?");
        $update_profile->bind_param("ssssi", $dob, $gender, $contact_number, $address, $user_id);
        $update_profile->execute();
    } else {
        // Doctor profile update
        $specialty = $_POST['specialty'];
        $license_number = $_POST['license_number'];
        $availability = $_POST['availability'];

        // Update doctor profile
        $update_doctor = $conn->prepare("UPDATE doctor_profiles SET specialty = ?, license_number = ?, availability = ? WHERE doctor_id = ?");
        $update_doctor->bind_param("sssi", $specialty, $license_number, $availability, $user_id);
        $update_doctor->execute();
    }

    $_SESSION['name'] = $full_name; // Update session name
    header("Location: view_profile.php?success=1");
    exit();
}

// Handle account deletion
if (isset($_POST['delete'])) {
    $delete = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $delete->bind_param("i", $user_id);
    $delete->execute();

    session_destroy();
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <?php include_once '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if ($role === 'doctor') {
                include_once '../includes/doctor_sidebar.php';
            } else {
                include_once '../includes/patient_sidebar.php';
            } ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 page-title">My Profile</h1>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Profile updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                            <div class="profile-info">
                                <h3 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h3>
                                <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?> <span class="badge bg-primary ms-2 rounded-pill"><?= ucfirst($role) ?></span></p>
                                <?php if ($role === 'patient' && $profile): ?>
                                    <p class="mb-0 text-muted">
                                        <?= $profile['gender'] ? ucfirst($profile['gender']) : '' ?>
                                        <?= $profile['date_of_birth'] ? ' • Born ' . date('F j, Y', strtotime($profile['date_of_birth'])) : '' ?>
                                    </p>
                                <?php elseif ($role === 'doctor' && $profile): ?>
                                    <p class="mb-0 text-muted"><span class="badge bg-info rounded-pill me-2">Specialty</span><?= htmlspecialchars($profile['specialty']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="<?php echo ($role === 'patient' || $role === 'doctor') && $profile ? 'col-md-6' : 'col-md-12'; ?>">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <i class="bi bi-person-badge me-2"></i>Account Information
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="full_name" class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Role</label>
                                                <div class="form-control-plaintext border-bottom">
                                                    <span class="badge bg-primary"><?= ucfirst($role) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($role === 'patient' && $profile): ?>
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <i class="bi bi-clipboard-pulse me-2"></i>Patient Profile Information
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($profile['date_of_birth']) ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="gender" class="form-label">Gender</label>
                                                    <select class="form-select" id="gender" name="gender">
                                                        <option value="male" <?= $profile['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                                                        <option value="female" <?= $profile['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                                                        <option value="other" <?= $profile['gender'] == 'other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="contact_number" class="form-label">Contact Number</label>
                                                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= htmlspecialchars($profile['contact_number']) ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="address" class="form-label">Address</label>
                                                <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($profile['address']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif ($role === 'doctor' && $profile): ?>
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <i class="bi bi-clipboard-plus me-2"></i>Doctor Profile Information
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="specialty" class="form-label">Specialty</label>
                                                    <input type="text" class="form-control" id="specialty" name="specialty" value="<?= htmlspecialchars($profile['specialty']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="license_number" class="form-label">License Number</label>
                                                    <input type="text" class="form-control" id="license_number" name="license_number" value="<?= htmlspecialchars($profile['license_number']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="availability" class="form-label">Availability</label>
                                                    <input type="text" class="form-control" id="availability" name="availability" value="<?= htmlspecialchars($profile['availability']) ?>" placeholder="e.g. Mon-Fri 9am-5pm" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="btn-action-group">
                                <button type="submit" name="update" class="btn-update">
                                    <i class="bi bi-check-circle"></i> Update Profile
                                </button>
                                <button type="reset" class="btn-reset">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Changes
                                </button>
                                <button type="button" class="btn-delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                                    <i class="bi bi-trash"></i> Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($emr_result->num_rows > 0): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="bi bi-file-medical me-2"></i>Electronic Medical Records (EMR)
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>EMR ID</th>
                                            <?php if ($role === 'patient'): ?>
                                                <th>Doctor</th>
                                            <?php else: ?>
                                                <th>Patient</th>
                                            <?php endif; ?>
                                            <th>Notes</th>
                                            <th>Diagnosis</th>
                                            <th>Prescribed Medications</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($emr = $emr_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($emr['emr_id']) ?></td>
                                                <?php if ($role === 'patient'): ?>
                                                    <td><?= htmlspecialchars($emr['doctor_name']) ?></td>
                                                <?php else: ?>
                                                    <td><?= htmlspecialchars($emr['patient_name']) ?></td>
                                                <?php endif; ?>
                                                <?php
                                                // Decrypt the EMR data
                                                $decrypted_notes = decryptMessage($emr['notes']);
                                                $decrypted_diagnosis = decryptMessage($emr['diagnosis']);
                                                $decrypted_medications = decryptMessage($emr['prescribed_medications']);
                                                
                                                // Handle decryption errors
                                                if ($decrypted_notes === false) $decrypted_notes = "Error: Unable to decrypt notes";
                                                if ($decrypted_diagnosis === false) $decrypted_diagnosis = "Error: Unable to decrypt diagnosis";
                                                if ($decrypted_medications === false) $decrypted_medications = "Error: Unable to decrypt medications";
                                                ?>
                                                <td><?= nl2br(htmlspecialchars($decrypted_notes)) ?></td>
                                                <td><?= nl2br(htmlspecialchars($decrypted_diagnosis)) ?></td>
                                                <td><?= nl2br(htmlspecialchars($decrypted_medications)) ?></td>
                                                <td><?= htmlspecialchars($emr['created_at']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        No EMR records found.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <div>
        <br>
        <br>
      
    </div>
    <?php include_once '../includes/footer.php'; ?>

    <script>
        // Optional JavaScript for enhanced user experience
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight fields on focus
            const formControls = document.querySelectorAll('.form-control, .form-select');
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.classList.add('border-primary');
                });
                control.addEventListener('blur', function() {
                    this.classList.remove('border-primary');
                });
            });
            
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transition = 'background-color 0.15s ease';
                    this.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
            
            // Animate buttons on hover
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
        });
    </script>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade modal-delete" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Delete Account Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill warning-icon"></i>
                        <span><strong>Warning:</strong> This action cannot be undone.</span>
                    </div>
                    <p>Are you sure you want to delete your account? This will permanently remove all your data from our system, including:</p>
                    <ul>
                        <li>Personal profile information</li>
                        <li>Medical records</li>
                        <li>Appointment history</li>
                        <li>Messages and communication history</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display:inline;">
                        <button type="submit" name="delete" class="btn-modal-delete">Delete Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>