<?php
// Start session
session_start();

// Only allow doctors to access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: home.php");
    exit();
}

// Include database connection
require_once '../database/db_connection.php';

define('INCLUDED', true);

// Handle the search query
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    // Sanitize input to prevent SQL injection
    $search = $conn->real_escape_string($_GET['search']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patients</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .input-editable {
            width: 100%;
            padding: 0.375rem 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }
        .editing {
            background-color: #f8f9fa;
        }
        .action-btns .btn {
            margin: 0 2px;
        }
        .save-btn, .cancel-btn {
            display: none;
        }
        .editing .save-btn, .editing .cancel-btn {
            display: inline-block;
        }
        .editing .edit-btn {
            display: none;
        }
        /* Enhanced table styling */
        .table-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        .enhanced-table {
            margin-bottom: 0;
        }
        .enhanced-table thead {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        }
        .enhanced-table thead th {
            color: white;
            font-weight: 500;
            border-bottom: none;
            padding: 12px 15px;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .enhanced-table tbody tr {
            transition: all 0.2s;
        }
        .enhanced-table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .enhanced-table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
            color: #495057;
            font-size: 0.9rem;
        }
        .enhanced-table .patient-name-cell {
            font-weight: 500;
            color: #0d6efd;
        }
        .enhanced-table .gender-badge {
            padding: 5px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }
        .enhanced-table .gender-badge.male {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        .enhanced-table .gender-badge.female {
            background-color: rgba(214, 51, 132, 0.1);
            color: #d63384;
        }
        .enhanced-table .patient-id-cell {
            font-family: monospace;
            font-size: 0.85rem;
            color: #6c757d;
        }
        .enhanced-table .date-cell {
            font-size: 0.85rem;
            white-space: nowrap;
        }
        /* Action buttons */
        .action-btns .btn {
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .action-btns .btn:hover {
            transform: translateY(-2px);
        }
        .action-btns .btn i {
            margin-right: 3px;
        }
        /* Mobile card view for patients data */
        .patient-card {
            display: none;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            background-color: #fff;
        }
        .patient-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .patient-card .patient-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0d6efd;
        }
        .patient-card .patient-id {
            font-size: 0.8rem;
            color: #6c757d;
            background-color: #f1f1f1;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .patient-card .patient-info {
            margin-bottom: 8px;
            display: flex;
        }
        .patient-card .info-label {
            font-weight: 500;
            min-width: 110px;
            color: #495057;
        }
        .patient-card .info-value {
            flex: 1;
        }
        .patient-card .card-actions {
            margin-top: 12px;
            border-top: 1px solid #e9ecef;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
        }
        .patient-card .card-actions .btn {
            flex: 1;
            margin: 0 3px;
        }
        /* Show cards on mobile, table on desktop */
        @media (max-width: 991px) {
            .table-container {
                display: none;
            }
            .patient-card {
                display: block;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once '../includes/doctor_sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Registered Patients</h1>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Search form -->
                        <form method="GET" action="view_patient.php" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search by Name" value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <?php
                        // Base query to join users and patient_profiles
                        $query = "SELECT 
                                u.user_id AS patient_id, 
                                u.full_name, 
                                u.email, 
                                p.date_of_birth, 
                                p.gender, 
                                p.contact_number, 
                                p.address,
                                u.created_at
                              FROM users u
                              INNER JOIN patient_profiles p ON u.user_id = p.patient_id
                              WHERE u.role = 'patient'";

                        // Modify query if there's a search input
                        if ($search) {
                            $query .= " AND u.full_name LIKE '%$search%'";
                        }

                        // Execute query
                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0): ?>
                            <!-- Table view (desktop) -->
                            <div class="table-responsive table-container">
                                <table class="table table-hover enhanced-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Birth Date</th>
                                            <th>Gender</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr data-id="<?php echo $row['patient_id']; ?>">
                                                <td class="patient-id-cell"><?php echo htmlspecialchars($row['patient_id']); ?></td>
                                                <td class="editable patient-name-cell" data-field="full_name"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td class="editable" data-field="email"><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td class="editable date-cell" data-field="date_of_birth"><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                                                <td class="editable" data-field="gender">
                                                    <span class="gender-badge <?php echo strtolower($row['gender']); ?>">
                                                        <?php echo htmlspecialchars($row['gender']); ?>
                                                    </span>
                                                </td>
                                                <td class="editable" data-field="contact_number"><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                                <td class="editable" data-field="address"><?php echo htmlspecialchars($row['address']); ?></td>
                                                <td class="date-cell"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td class="action-btns">
                                                    <button class="btn btn-sm btn-primary edit-btn">
                                                        <i class="fas fa-edit"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-success save-btn">
                                                        <i class="fas fa-save"></i>Save
                                                    </button>
                                                    <button class="btn btn-sm btn-secondary cancel-btn">
                                                        <i class="fas fa-times"></i>Cancel
                                                    </button>
                                                    <a href="doctor_chat.php?patient_id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-comments"></i>Chat
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Card view (mobile) -->
                            <?php 
                            // Reset the result pointer
                            $result->data_seek(0);
                            
                            while ($row = $result->fetch_assoc()): ?>
                                <div class="patient-card" data-id="<?php echo $row['patient_id']; ?>">
                                    <div class="card-header">
                                        <div class="patient-name"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                        <div class="patient-id">ID: <?php echo htmlspecialchars($row['patient_id']); ?></div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <div class="info-label">Email:</div>
                                        <div class="info-value editable" data-field="email"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <div class="info-label">Date of Birth:</div>
                                        <div class="info-value editable" data-field="date_of_birth"><?php echo htmlspecialchars($row['date_of_birth']); ?></div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <div class="info-label">Gender:</div>
                                        <div class="info-value editable" data-field="gender"><?php echo htmlspecialchars($row['gender']); ?></div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <div class="info-label">Contact:</div>
                                        <div class="info-value editable" data-field="contact_number"><?php echo htmlspecialchars($row['contact_number']); ?></div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <div class="info-label">Address:</div>
                                        <div class="info-value editable" data-field="address"><?php echo htmlspecialchars($row['address']); ?></div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <div class="info-label">Registered:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($row['created_at']); ?></div>
                                    </div>
                                    
                                    <div class="card-actions action-btns">
                                        <button class="btn btn-sm btn-primary edit-btn">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-success save-btn">
                                            <i class="fas fa-save me-1"></i>Save
                                        </button>
                                        <button class="btn btn-sm btn-secondary cancel-btn">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                        <a href="doctor_chat.php?patient_id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-comments me-1"></i>Chat
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            
                        <?php else: ?>
                            <div class="alert alert-info">No registered patients found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
        <br/>
        <br/>
    </div>

    <?php include_once '../includes/footer.php'; ?>
    
    <script>
        // Patient data editing functionality
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const container = btn.closest('tr, .patient-card');
                    container.classList.add('editing');

                    container.querySelectorAll('.editable').forEach(cell => {
                        const value = cell.textContent.trim();
                        const field = cell.dataset.field;
                        
                        if (field === "gender") {
                            cell.innerHTML = `
                                <select class="form-select form-select-sm input-editable" name="${field}">
                                    <option value="Male" ${value === 'Male' ? 'selected' : ''}>Male</option>
                                    <option value="Female" ${value === 'Female' ? 'selected' : ''}>Female</option>
                                </select>
                            `;
                        } else if (field === "date_of_birth") {
                            cell.innerHTML = `<input type="date" class="form-control form-control-sm input-editable" name="${field}" value="${value}">`;
                        } else {
                            cell.innerHTML = `<input type="text" class="form-control form-control-sm input-editable" name="${field}" value="${value}">`;
                        }
                    });
                });
            });

            document.querySelectorAll('.cancel-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const container = btn.closest('tr, .patient-card');
                    container.classList.remove('editing');
                    location.reload(); // Quick way to revert changes
                });
            });

            document.querySelectorAll('.save-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const container = btn.closest('tr, .patient-card');
                    const patientId = container.dataset.id;
                    const formData = new FormData();

                    formData.append('patient_id', patientId);
                    container.querySelectorAll('.editable').forEach(cell => {
                        const input = cell.querySelector('input, select');
                        if (input) {
                            formData.append(input.name, input.value);
                        }
                    });

                    fetch('update_patient.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(response => {
                        alert(response);
                        location.reload();
                    })
                    .catch(err => {
                        alert("An error occurred while updating.");
                    });
                });
            });
        });
    </script>
</body>
</html>