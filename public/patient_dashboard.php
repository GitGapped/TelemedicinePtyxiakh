<?php
// Start session to access the user data
session_start();

// Check if the user is logged in and has the 'patient' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    // If the user is not a patient, redirect to login or another page
    header("Location: login.php");
    exit();
}

// Define INCLUDED constant to prevent direct access to included files
define('INCLUDED', true);

// Include database connection
require_once '../database/db_connection.php';

// Get patient's upcoming appointments count
$appointment_query = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND appointment_datetime >= NOW() AND status = 'booked'";
$stmt = $conn->prepare($appointment_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$appointment_count = $stmt->get_result()->fetch_assoc()['count'];

// Get messages count
$message_query = "SELECT COUNT(*) as count FROM chat_messages WHERE receiver_id = ?";
$stmt = $conn->prepare($message_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$message_count = $stmt->get_result()->fetch_assoc()['count'];

// Get medical records count
$records_query = "SELECT COUNT(*) as count FROM emr WHERE patient_id = ?";
$stmt = $conn->prepare($records_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$records_count = $stmt->get_result()->fetch_assoc()['count'];

// Get recent activity (last 5 activities)
$activity_query = "SELECT * FROM (
    SELECT 'appointment' as type, appointment_datetime as date, doctor_id, 
           CASE 
               WHEN status = 'booked' THEN 'Appointment booked'
               WHEN status = 'free' THEN 'Appointment slot available'
               ELSE 'Appointment updated'
           END as description 
    FROM appointments 
    WHERE patient_id = ?
    UNION ALL
    SELECT 'message' as type, sent_at as date, sender_id as doctor_id, 'You received a message' as description 
    FROM chat_messages 
    WHERE receiver_id = ?
    UNION ALL
    SELECT 'emr' as type, created_at as date, doctor_id, 
           CASE 
               WHEN diagnosis IS NOT NULL THEN CONCAT('Diagnosis: ', diagnosis)
               WHEN notes IS NOT NULL THEN CONCAT('Notes: ', LEFT(notes, 50), '...')
               ELSE 'EMR updated'
           END as description 
    FROM emr 
    WHERE patient_id = ?
) as activities 
ORDER BY date DESC LIMIT 5";

$stmt = $conn->prepare($activity_query);
$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$activities = $stmt->get_result();

// Get upcoming appointments
$upcoming_query = "SELECT a.*, u.full_name as doctor_name, dp.specialty 
                  FROM appointments a 
                  JOIN users u ON a.doctor_id = u.user_id 
                  JOIN doctor_profiles dp ON a.doctor_id = dp.doctor_id
                  WHERE a.patient_id = ? AND a.appointment_datetime >= NOW() AND a.status = 'booked'
                  ORDER BY a.appointment_datetime ASC LIMIT 2";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
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
            <?php include_once '../includes/patient_sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 page-title">Patient Dashboard</h1>
                </div>

                <!-- Welcome Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                            </div>
                            <div class="profile-info">
                                <h3 class="profile-name">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
                                <p class="text-muted mb-1">Your health is our priority. Use this dashboard to manage your appointments, access your medical records, and communicate with your healthcare providers.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Upcoming Appointments</h6>
                                        <h2 class="mb-0"><?php echo $appointment_count; ?></h2>
                                    </div>
                                    <div class="feature-icon bg-primary text-white">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="book_appointment.php" class="text-decoration-none">View Details <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">New Messages</h6>
                                        <h2 class="mb-0"><?php echo $message_count; ?></h2>
                                    </div>
                                    <div class="feature-icon bg-success text-white">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="patient_chat.php" class="text-decoration-none">View Messages <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted">Medical Records</h6>
                                        <h2 class="mb-0"><?php echo $records_count; ?></h2>
                                    </div>
                                    <div class="feature-icon bg-warning text-white">
                                        <i class="bi bi-file-medical"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="reports.php" class="text-decoration-none">View Records <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Features Row -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-primary text-white mx-auto mb-3">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <h5 class="card-title">View Profile</h5>
                                <p class="card-text">Access and manage your personal information and medical history.</p>
                                <a href="view_profile.php" class="btn btn-primary">
                                    <i class="bi bi-eye"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-success text-white mx-auto mb-3">
                                    <i class="bi bi-calendar-plus"></i>
                                </div>
                                <h5 class="card-title">Book Appointment</h5>
                                <p class="card-text">Schedule appointments with your healthcare providers.</p>
                                <a href="book_appointment.php" class="btn btn-success">
                                    <i class="bi bi-calendar-plus"></i> Book Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-info text-white mx-auto mb-3">
                                    <i class="bi bi-chat-dots"></i>
                                </div>
                                <h5 class="card-title">Messaging</h5>
                                <p class="card-text">Communicate with your healthcare providers through our messaging system.</p>
                                <a href="patient_chat.php" class="btn btn-info text-white">
                                    <i class="bi bi-chat-dots"></i> Go to Messages
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity and Upcoming Appointments -->
                <div class="row mb-4">
                    <!-- Recent Activity -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="bi bi-clock-history me-2"></i>Recent Activity
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if ($activities->num_rows > 0): ?>
                                        <?php while ($activity = $activities->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?php
                                                    $icon = '';
                                                    $text_class = '';
                                                    switch ($activity['type']) {
                                                        case 'appointment':
                                                            $icon = 'calendar-check';
                                                            $text_class = 'success';
                                                            break;
                                                        case 'message':
                                                            $icon = 'chat-dots';
                                                            $text_class = 'warning';
                                                            break;
                                                        case 'emr':
                                                            $icon = 'file-medical';
                                                            $text_class = 'info';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="bi bi-<?php echo $icon; ?> text-<?php echo $text_class; ?> me-2"></i>
                                                    <span><?php echo htmlspecialchars($activity['description']); ?></span>
                                                    <small class="d-block text-muted">
                                                        <?php
                                                        if ($activity['doctor_id']) {
                                                            $doctor_query = "SELECT full_name FROM users WHERE user_id = ?";
                                                            $stmt = $conn->prepare($doctor_query);
                                                            $stmt->bind_param("i", $activity['doctor_id']);
                                                            $stmt->execute();
                                                            $doctor_result = $stmt->get_result();
                                                            if ($doctor = $doctor_result->fetch_assoc()) {
                                                                echo "From: Dr. " . htmlspecialchars($doctor['full_name']);
                                                            }
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php
                                                    $date = new DateTime($activity['date']);
                                                    $now = new DateTime();
                                                    $diff = $date->diff($now);
                                                    
                                                    if ($diff->d == 0) {
                                                        echo "Today";
                                                    } elseif ($diff->d == 1) {
                                                        echo "Yesterday";
                                                    } else {
                                                        echo $diff->d . " days ago";
                                                    }
                                                    ?>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center text-muted">No recent activity</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upcoming Appointments -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="bi bi-calendar me-2"></i>Upcoming Appointments
                            </div>
                            <div class="card-body">
                                <?php if ($upcoming_appointments->num_rows > 0): ?>
                                    <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                        <div class="appointment-item p-3 mb-3 border rounded">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> - <?php echo htmlspecialchars($appointment['specialty']); ?></h6>
                                                <span class="badge bg-<?php 
                                                    echo match($appointment['status']) {
                                                        'booked' => 'success',
                                                        'free' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                            </div>
                                            <p class="mb-1">
                                                <i class="bi bi-calendar me-2"></i>
                                                <?php echo date('F j, Y', strtotime($appointment['appointment_datetime'])); ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-clock me-2"></i>
                                                <?php echo date('g:i A', strtotime($appointment['appointment_datetime'])); ?>
                                            </p>
                                            <?php if ($appointment['jitsi_link']): ?>
                                            <p class="mb-1">
                                                <i class="bi bi-camera-video me-2"></i>
                                                <a href="<?php echo htmlspecialchars($appointment['jitsi_link']); ?>" target="_blank" class="text-primary">
                                                    Join Video Call
                                                </a>
                                            </p>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <?php if ($appointment['status'] === 'booked'): ?>
                                                <a href="book_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="bi bi-pencil"></i> Reschedule
                                                </a>
                                               
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="bi bi-calendar-x fa-2x mb-2"></i>
                                        <p>No upcoming appointments</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="book_appointment.php" class="btn btn-sm btn-outline-primary">Book New Appointment</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <br/>
    <br/>
    </div>

    <?php include_once '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add feature icon styles dynamically
            const featureIcons = document.querySelectorAll('.feature-icon');
            featureIcons.forEach(icon => {
                icon.style.width = '60px';
                icon.style.height = '60px';
                icon.style.borderRadius = '50%';
                icon.style.display = 'flex';
                icon.style.alignItems = 'center';
                icon.style.justifyContent = 'center';
                icon.style.marginBottom = '1rem';
            });

            // Add hover effects to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transition = 'transform 0.2s ease';
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Add hover effects to buttons
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
</body>
</html>