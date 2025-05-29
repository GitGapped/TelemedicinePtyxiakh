<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: home.php");
    exit();
}

require_once '../database/db_connection.php';
define('INCLUDED', true);

$doctor_id = $_SESSION['user_id'];

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = intval($_POST['cancel_appointment']);
    
    // Verify the appointment belongs to this doctor
    $check_query = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
    $check_query->bind_param("ii", $appointment_id, $doctor_id);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if ($check_result->num_rows > 0) {
        // Delete the appointment
        $delete_query = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $delete_query->bind_param("i", $appointment_id);
        
        if ($delete_query->execute()) {
            $cancel_success = "Appointment cancelled successfully.";
        } else {
            $cancel_error = "Failed to cancel appointment. Please try again.";
        }
    } else {
        $cancel_error = "Invalid appointment or permission denied.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointments</title>
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
            <?php include_once '../includes/doctor_sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-calendar-check me-2"></i>My Appointments
                    </h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-check me-2"></i>
                        Appointment Management
                    </div>
                    <div class="card-body">
                        <?php if (!empty($cancel_success)): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $cancel_success; ?>
                            </div>
                        <?php elseif (!empty($cancel_error)): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $cancel_error; ?>
                            </div>
                        <?php endif; ?>

                        <ul class="nav nav-tabs mb-3" id="appointmentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                                    Upcoming
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">
                                    Past
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="appointmentTabsContent">
                            <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                                <?php
                                // Get upcoming appointments
                                $upcoming_query = $conn->prepare("
                                    SELECT a.*, u.full_name AS patient_name, u.email AS patient_email
                                    FROM appointments a
                                    JOIN users u ON a.patient_id = u.user_id
                                    WHERE a.doctor_id = ? 
                                    AND a.appointment_datetime >= NOW()
                                    ORDER BY a.appointment_datetime ASC
                                ");
                                $upcoming_query->bind_param("i", $doctor_id);
                                $upcoming_query->execute();
                                $upcoming_result = $upcoming_query->get_result();
                                
                                if ($upcoming_result->num_rows > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover align-middle">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Patient</th>';
                                    echo '<th>Date</th>';
                                    echo '<th>Time</th>';
                                    echo '<th>Status</th>';
                                    echo '<th>Actions</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    
                                    while ($appointment = $upcoming_result->fetch_assoc()) {
                                        $date = date('l, F j, Y', strtotime($appointment['appointment_datetime']));
                                        $time = date('g:i A', strtotime($appointment['appointment_datetime']));
                                        
                                        echo '<tr>';
                                        echo '<td>';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<div class="doctor-avatar me-2">' . strtoupper(substr($appointment['patient_name'], 0, 1)) . '</div>';
                                        echo '<div>';
                                        echo '<div class="fw-medium">' . htmlspecialchars($appointment['patient_name']) . '</div>';
                                        echo '<small class="text-muted">' . htmlspecialchars($appointment['patient_email']) . '</small>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '<td>' . $date . '</td>';
                                        echo '<td>' . $time . '</td>';
                                        echo '<td><span class="badge bg-success">Upcoming</span></td>';
                                        echo '<td>';
                                        
                                        // Check if appointment is within 15 minutes of current time
                                        $appointment_time = strtotime($appointment['appointment_datetime']);
                                        $current_time = time();
                                        $time_diff = $appointment_time - $current_time;
                                        
                                        if ($time_diff <= 900 && $time_diff > 0) { // 15 minutes = 900 seconds
                                            echo '<a href="' . htmlspecialchars($appointment['jitsi_link']) . '" target="_blank" class="btn btn-sm btn-primary me-1">';
                                            echo '<i class="bi bi-camera-video me-1"></i>Join Call</a>';
                                        }
                                        
                                        echo '<a href="doctor_chat.php?patient_id=' . $appointment['patient_id'] . '" class="btn btn-sm btn-info me-1">';
                                        echo '<i class="bi bi-chat-dots me-1"></i>Chat/Call</a>';
                                        
                                        echo '<button type="button" class="btn btn-sm btn-danger" onclick="confirmCancel(' . $appointment['appointment_id'] . ')">';
                                        echo '<i class="bi bi-x-circle me-1"></i>Cancel</button>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-info d-flex align-items-center">';
                                    echo '<i class="bi bi-info-circle-fill me-2"></i>';
                                    echo 'You have no upcoming appointments.';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            
                            <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                                <?php
                                // Get past appointments
                                $past_query = $conn->prepare("
                                    SELECT a.*, u.full_name AS patient_name, u.email AS patient_email
                                    FROM appointments a
                                    JOIN users u ON a.patient_id = u.user_id
                                    WHERE a.doctor_id = ? 
                                    AND a.appointment_datetime < NOW()
                                    ORDER BY a.appointment_datetime DESC
                                    LIMIT 10
                                ");
                                $past_query->bind_param("i", $doctor_id);
                                $past_query->execute();
                                $past_result = $past_query->get_result();
                                
                                if ($past_result->num_rows > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover align-middle">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Patient</th>';
                                    echo '<th>Date</th>';
                                    echo '<th>Time</th>';
                                    echo '<th>Status</th>';
                                    echo '<th>Actions</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    
                                    while ($appointment = $past_result->fetch_assoc()) {
                                        $date = date('l, F j, Y', strtotime($appointment['appointment_datetime']));
                                        $time = date('g:i A', strtotime($appointment['appointment_datetime']));
                                        
                                        echo '<tr>';
                                        echo '<td>';
                                        echo '<div class="d-flex align-items-center">';
                                        echo '<div class="doctor-avatar me-2">' . strtoupper(substr($appointment['patient_name'], 0, 1)) . '</div>';
                                        echo '<div>';
                                        echo '<div class="fw-medium">' . htmlspecialchars($appointment['patient_name']) . '</div>';
                                        echo '<small class="text-muted">' . htmlspecialchars($appointment['patient_email']) . '</small>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '<td>' . $date . '</td>';
                                        echo '<td>' . $time . '</td>';
                                        echo '<td><span class="badge bg-secondary">Completed</span></td>';
                                        echo '<td>';
                                        echo '<a href="doctor_chat.php?patient_id=' . $appointment['patient_id'] . '" class="btn btn-sm btn-info">';
                                        echo '<i class="bi bi-chat-dots me-1"></i>Chat</a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-info d-flex align-items-center">';
                                    echo '<i class="bi bi-info-circle-fill me-2"></i>';
                                    echo 'You have no past appointments.';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <br/>
        <br/>
    </div>

    <!-- Hidden form for appointment cancellation -->
    <form id="cancelForm" method="POST" style="display:none;">
        <input type="hidden" name="cancel_appointment" id="cancel_appointment" value="">
    </form>

    <?php include_once '../includes/footer.php'; ?>

    <script>
        function confirmCancel(appointmentId) {
            if (confirm("Are you sure you want to cancel this appointment?")) {
                document.getElementById('cancel_appointment').value = appointmentId;
                document.getElementById('cancelForm').submit();
            }
        }
    </script>
</body>
</html> 