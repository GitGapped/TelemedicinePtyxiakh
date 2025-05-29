<?php
// Start session to access the user data
session_start();

// Check if the user is logged in and has the correct role (doctor)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'doctor')) {
    // If not, redirect them to the home page or another appropriate page
    header("Location: home.php"); // You can replace home.php with the appropriate page
    exit(); // Terminate further execution of the script
}
define('INCLUDED', true);

// Include database connection
require_once '../database/db_connection.php';

// Get today's date in Y-m-d format
$today = date('Y-m-d');

// Get upcoming appointments count
$appointments_query = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM appointments 
    WHERE doctor_id = ? 
    AND appointment_datetime >= NOW()
    AND status = 'booked'
");
$appointments_query->bind_param("i", $_SESSION['user_id']);
$appointments_query->execute();
$appointments_result = $appointments_query->get_result();
$appointments_count = $appointments_result->fetch_assoc()['count'];

// Get total patients count
$patients_query = $conn->prepare("
    SELECT COUNT(DISTINCT patient_id) as count 
    FROM appointments 
    WHERE doctor_id = ?
");
$patients_query->bind_param("i", $_SESSION['user_id']);
$patients_query->execute();
$patients_result = $patients_query->get_result();
$patients_count = $patients_result->fetch_assoc()['count'];

// Get new messages count
$messages_query = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM chat_messages 
    WHERE receiver_id = ? 
    AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$messages_query->bind_param("i", $_SESSION['user_id']);
$messages_query->execute();
$messages_result = $messages_query->get_result();
$messages_count = $messages_result->fetch_assoc()['count'];

// Get pending reports count (EMR entries from last 24 hours)
$reports_query = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM emr 
    WHERE doctor_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$reports_query->bind_param("i", $_SESSION['user_id']);
$reports_query->execute();
$reports_result = $reports_query->get_result();
$reports_count = $reports_result->fetch_assoc()['count'];

// Get upcoming appointments with patient details
$upcoming_appointments_query = $conn->prepare("
    SELECT a.*, u.full_name as patient_name 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.user_id 
    WHERE a.doctor_id = ? 
    AND a.appointment_datetime >= NOW()
    AND a.status = 'booked'
    ORDER BY a.appointment_datetime ASC
    LIMIT 10
");
$upcoming_appointments_query->bind_param("i", $_SESSION['user_id']);
$upcoming_appointments_query->execute();
$upcoming_appointments_result = $upcoming_appointments_query->get_result();

// Get recent activity
$activity_query = $conn->prepare("
    (SELECT 'appointment' as type, a.appointment_datetime as datetime, u.full_name as name, 'Appointment scheduled' as description
    FROM appointments a 
    JOIN users u ON a.patient_id = u.user_id 
    WHERE a.doctor_id = ? 
    ORDER BY a.created_at DESC LIMIT 5)
    UNION
    (SELECT 'message' as type, cm.sent_at as datetime, u.full_name as name, 'New message received' as description
    FROM chat_messages cm 
    JOIN users u ON cm.sender_id = u.user_id 
    WHERE cm.receiver_id = ? 
    ORDER BY cm.sent_at DESC LIMIT 5)
    UNION
    (SELECT 'emr' as type, e.created_at as datetime, u.full_name as name, 'EMR updated' as description
    FROM emr e 
    JOIN users u ON e.patient_id = u.user_id 
    WHERE e.doctor_id = ? 
    ORDER BY e.created_at DESC LIMIT 5)
    ORDER BY datetime DESC LIMIT 5
");
$activity_query->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$activity_query->execute();
$activity_result = $activity_query->get_result();

// Get patient demographics
$demographics_query = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN p.gender = 'Male' THEN 1 END) as male_count,
        COUNT(CASE WHEN p.gender = 'Female' THEN 1 END) as female_count,
        COUNT(CASE WHEN p.gender = 'other' THEN 1 END) as other_count
    FROM appointments a 
    JOIN patient_profiles p ON a.patient_id = p.patient_id 
    WHERE a.doctor_id = ?
");
$demographics_query->bind_param("i", $_SESSION['user_id']);
$demographics_query->execute();
$demographics_result = $demographics_query->get_result();
$demographics = $demographics_result->fetch_assoc();

// Calculate percentages
$total_patients = $demographics['male_count'] + $demographics['female_count'] + $demographics['other_count'];
$male_percentage = $total_patients > 0 ? round(($demographics['male_count'] / $total_patients) * 100) : 0;
$female_percentage = $total_patients > 0 ? round(($demographics['female_count'] / $total_patients) * 100) : 0;
$other_percentage = $total_patients > 0 ? round(($demographics['other_count'] / $total_patients) * 100) : 0;

// Get appointment statistics
$appointment_stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        COUNT(CASE WHEN DATE(appointment_datetime) = CURDATE() THEN 1 END) as today_appointments,
        COUNT(CASE WHEN DATE(appointment_datetime) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as tomorrow_appointments,
        COUNT(CASE WHEN DATE(appointment_datetime) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_appointments
    FROM appointments 
    WHERE doctor_id = ? 
    AND appointment_datetime >= NOW()
    AND status = 'booked'
");
$appointment_stats_query->bind_param("i", $_SESSION['user_id']);
$appointment_stats_query->execute();
$appointment_stats = $appointment_stats_query->get_result()->fetch_assoc();

// Get patient visit frequency
$visit_frequency_query = $conn->prepare("
    SELECT 
        patient_id,
        COUNT(*) as visit_count
    FROM appointments 
    WHERE doctor_id = ? 
    AND appointment_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND status = 'booked'
    GROUP BY patient_id
    ORDER BY visit_count DESC
    LIMIT 5
");
$visit_frequency_query->bind_param("i", $_SESSION['user_id']);
$visit_frequency_query->execute();
$visit_frequency_result = $visit_frequency_query->get_result();

// Get appointment distribution by day of week
$day_distribution_query = $conn->prepare("
    SELECT 
        DAYNAME(appointment_datetime) as day_name,
        COUNT(*) as appointment_count
    FROM appointments 
    WHERE doctor_id = ? 
    AND appointment_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND status = 'booked'
    GROUP BY DAYNAME(appointment_datetime)
    ORDER BY FIELD(DAYNAME(appointment_datetime), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
");
$day_distribution_query->bind_param("i", $_SESSION['user_id']);
$day_distribution_query->execute();
$day_distribution_result = $day_distribution_query->get_result();

// Get recent EMR updates
$recent_emr_query = $conn->prepare("
    SELECT e.*, u.full_name as patient_name, 
           TIMESTAMPDIFF(HOUR, e.created_at, NOW()) as hours_ago
    FROM emr e 
    JOIN users u ON e.patient_id = u.user_id 
    WHERE e.doctor_id = ? 
    ORDER BY e.created_at DESC 
    LIMIT 1
");
$recent_emr_query->bind_param("i", $_SESSION['user_id']);
$recent_emr_query->execute();
$recent_emr = $recent_emr_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
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
                    <h1 class="h2">Doctor Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="card shadow-sm mb-4 border-primary border-start border-0 border-3">
                    <div class="card-body">
                        <h2 class="h4 mb-3"><i class="fas fa-user-md text-primary me-2"></i>Welcome, <?php echo htmlspecialchars(ucfirst($_SESSION['role']), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
                        <p class="lead">Your dedicated provider dashboard gives you access to manage patient information, appointments, and medical records.</p>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i> You have <strong><?php echo htmlspecialchars($appointments_count, ENT_QUOTES, 'UTF-8'); ?> upcoming appointments</strong> scheduled.
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Upcoming Appointments</h6>
                                        <h2 class="mb-0"><?php echo $appointments_count; ?></h2>
                                    </div>
                                    <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="doctor_appointments.php" class="text-white text-decoration-none">View Details</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">New Messages</h6>
                                        <h2 class="mb-0"><?php echo $messages_count; ?></h2>
                                    </div>
                                    <i class="fas fa-envelope fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="doctor_chat.php" class="text-white text-decoration-none">View Messages</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Recent EMR Update</h6>
                                        <?php if ($recent_emr): ?>
                                            <p class="mb-0 small"><?php echo htmlspecialchars($recent_emr['patient_name']); ?></p>
                                            <p class="mb-0 small"><?php echo $recent_emr['hours_ago']; ?> hours ago</p>
                                        <?php else: ?>
                                            <p class="mb-0">No recent updates</p>
                                        <?php endif; ?>
                                    </div>
                                    <i class="fas fa-notes-medical fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="enterEMRData.php" class="text-white text-decoration-none">View EMR</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Total Patients</h6>
                                        <h2 class="mb-0"><?php echo $patients_count; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="view_patient.php" class="text-white text-decoration-none">View All Patients</a>
                                <i class="fas fa-angle-right text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Features Row -->
                <h3 class="mb-3">Quick Actions</h3>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-primary text-white mx-auto mb-3">
                                    <i class="fas fa-user-injured fa-2x"></i>
                                </div>
                                <h5 class="card-title">View Patients</h5>
                                <p class="card-text">Access and manage patient records and information.</p>
                                <a href="view_patient.php" class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i> View Patients
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-success text-white mx-auto mb-3">
                                    <i class="fas fa-notes-medical fa-2x"></i>
                                </div>
                                <h5 class="card-title">Enter EMR Data</h5>
                                <p class="card-text">Create and update electronic medical records for your patients.</p>
                                <a href="enterEMRData.php" class="btn btn-success">
                                    <i class="fas fa-file-medical me-1"></i> Enter EMR
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body text-center">
                                <div class="feature-icon bg-info text-white mx-auto mb-3">
                                    <i class="fas fa-comments fa-2x"></i>
                                </div>
                                <h5 class="card-title">Messaging</h5>
                                <p class="card-text">Communicate with your patients through the messaging system.</p>
                                <a href="doctor_chat.php" class="btn btn-info text-white">
                                    <i class="fas fa-paper-plane me-1"></i> Go to Messages
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments & Recent Activity -->
                <div class="row mb-4">
                    <!-- Upcoming Appointments -->
                    <div class="col-md-7 mb-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-calendar-day me-2 text-primary"></i>Upcoming Appointments</h5>
                                <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Patient</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($appointment = $upcoming_appointments_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $appointment_date = new DateTime($appointment['appointment_datetime']);
                                                    $now = new DateTime();
                                                    $interval = $appointment_date->diff($now);
                                                    
                                                    if ($interval->d == 0) {
                                                        echo 'Today at ' . $appointment_date->format('h:i A');
                                                    } elseif ($interval->d == 1) {
                                                        echo 'Tomorrow at ' . $appointment_date->format('h:i A');
                                                    } else {
                                                        echo $appointment_date->format('M d, Y h:i A');
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                                <td><span class="badge bg-success">Confirmed</span></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="#" class="btn btn-outline-primary"><i class="fas fa-eye"></i></a>
                                                        <?php if ($appointment['jitsi_link']): ?>
                                                        <a href="<?php echo htmlspecialchars($appointment['jitsi_link']); ?>" class="btn btn-outline-success" target="_blank"><i class="fas fa-video"></i></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="col-md-5 mb-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php while ($activity = $activity_result->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php
                                            $icon = '';
                                            $text_class = '';
                                            switch ($activity['type']) {
                                                case 'appointment':
                                                    $icon = 'calendar-check';
                                                    $text_class = 'text-primary';
                                                    break;
                                                case 'message':
                                                    $icon = 'comment-medical';
                                                    $text_class = 'text-info';
                                                    break;
                                                case 'emr':
                                                    $icon = 'notes-medical';
                                                    $text_class = 'text-success';
                                                    break;
                                            }
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> <?php echo $text_class; ?> me-2"></i>
                                            <span><?php echo htmlspecialchars($activity['description']); ?></span>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($activity['name']); ?></small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php
                                            $datetime = new DateTime($activity['datetime']);
                                            $now = new DateTime();
                                            $interval = $datetime->diff($now);
                                            
                                            if ($interval->d == 0) {
                                                if ($interval->h == 0) {
                                                    echo $interval->i . ' min ago';
                                                } else {
                                                    echo $interval->h . ' hours ago';
                                                }
                                            } else {
                                                echo $interval->d . ' days ago';
                                            }
                                            ?>
                                        </span>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="#" class="btn btn-sm btn-outline-primary">View All Activity</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointment Statistics -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Appointment Statistics</h5>
                        <div class="btn-group btn-group-sm">
                           
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-3">Appointment Distribution by Day</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Appointments</th>
                                                <th>Distribution</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_appointments = 0;
                                            $day_distribution = [];
                                            while ($row = $day_distribution_result->fetch_assoc()) {
                                                $day_distribution[$row['day_name']] = $row['appointment_count'];
                                                $total_appointments += $row['appointment_count'];
                                            }
                                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                            foreach ($days as $day): 
                                                $count = $day_distribution[$day] ?? 0;
                                                $percentage = $total_appointments > 0 ? round(($count / $total_appointments) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo $day; ?></td>
                                                <td><?php echo $count; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%;" 
                                                             aria-valuenow="<?php echo $percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Upcoming Schedule</h6>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Today</span>
                                                    <span class="badge bg-primary"><?php echo $appointment_stats['today_appointments']; ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Tomorrow</span>
                                                    <span class="badge bg-info"><?php echo $appointment_stats['tomorrow_appointments']; ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>This Week</span>
                                                    <span class="badge bg-success"><?php echo $appointment_stats['week_appointments']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Most Frequent Patients</h6>
                                                <ul class="list-group list-group-flush">
                                                    <?php while ($patient = $visit_frequency_result->fetch_assoc()): 
                                                        $patient_query = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
                                                        $patient_query->bind_param("i", $patient['patient_id']);
                                                        $patient_query->execute();
                                                        $patient_name = $patient_query->get_result()->fetch_assoc()['full_name'];
                                                    ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                                                        <?php echo htmlspecialchars($patient_name); ?>
                                                        <span class="badge bg-primary rounded-pill"><?php echo $patient['visit_count']; ?> visits</span>
                                                    </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        // Initialize features
        document.addEventListener('DOMContentLoaded', function() {
            // Refresh button functionality
            document.getElementById('refreshBtn').addEventListener('click', function() {
                location.reload();
            });
            
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

            // Initialize any tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html>
