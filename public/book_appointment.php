<?php
session_start();
require_once '../database/db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: home.php");
    exit();
}
define('INCLUDED', true);

$patient_id = $_SESSION['user_id'];

if (isset($_GET['fetch_slots'])) {
    header('Content-Type: application/json');
    $doctor_id = intval($_GET['doctor_id']);
    $date = $_GET['date'];
    $start = "$date 09:00:00";
    $end = "$date 17:00:00";

    $stmt = $conn->prepare("SELECT HOUR(appointment_datetime) as hour FROM appointments 
                            WHERE doctor_id = ? 
                            AND appointment_datetime BETWEEN ? AND ?");
    $stmt->bind_param("iss", $doctor_id, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_hours = [];
    while ($row = $result->fetch_assoc()) {
        $booked_hours[] = intval($row['hour']);
    }

    echo json_encode($booked_hours);
    exit();
}

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = intval($_POST['cancel_appointment']);
    
    // Verify the appointment belongs to this patient
    $check_query = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND patient_id = ?");
    $check_query->bind_param("ii", $appointment_id, $patient_id);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if ($check_result->num_rows > 0) {
        // Delete the appointment
        $delete_query = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $delete_query->bind_param("i", $appointment_id);
        
        if ($delete_query->execute()) {
            $cancel_success = "Appointment cancelled successfully.";
            error_log("Successfully cancelled appointment ID: " . $appointment_id);
        } else {
            $cancel_error = "Failed to cancel appointment. Please try again.";
            error_log("Error cancelling appointment: " . $conn->error);
        }
    } else {
        $cancel_error = "Invalid appointment or permission denied.";
  
        
        // Get the actual appointment details for debugging
        $debug_query = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
        $debug_query->bind_param("i", $appointment_id);
        $debug_query->execute();
        $debug_result = $debug_query->get_result();
        
        if ($debug_result->num_rows > 0) {
            $debug_appointment = $debug_result->fetch_assoc();
            error_log("Found appointment with patient_id=" . $debug_appointment['patient_id'] . 
                     " while current patient_id=" . $patient_id);
        } else {
            error_log("No appointment found with ID: " . $appointment_id);
        }
    }
}

// Handle appointment editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_appointment'])) {
    $appointment_id = intval($_POST['edit_appointment_id']);
    $new_date = $_POST['edit_appointment_date'];
    $new_hour = intval($_POST['edit_appointment_hour']);
    $new_datetime = "$new_date $new_hour:00:00";

    // Verify the appointment belongs to this patient
    $check_query = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND patient_id = ?");
    $check_query->bind_param("ii", $appointment_id, $patient_id);
    $check_query->execute();
    $check_result = $check_query->get_result();

    if ($check_result->num_rows > 0) {
        // Check if the new time slot is available
        $appointment = $check_result->fetch_assoc();
        $doctor_id = $appointment['doctor_id'];

        $stmt = $conn->prepare("SELECT * FROM appointments 
                                WHERE doctor_id = ? 
                                AND appointment_datetime = ?
                                AND appointment_id != ?");
        $stmt->bind_param("isi", $doctor_id, $new_datetime, $appointment_id);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $error = "This time slot is already booked.";
        } else {
            // Update the appointment
            $update_query = $conn->prepare("UPDATE appointments 
                                          SET appointment_datetime = ?,
                                              jitsi_link = ?
                                          WHERE appointment_id = ?");
            
            // Generate new Jitsi link
            $new_jitsi_link = "https://meet.jit.si/Appointment_{$doctor_id}_{$patient_id}_" . time();
            
            $update_query->bind_param("ssi", $new_datetime, $new_jitsi_link, $appointment_id);
            
            if ($update_query->execute()) {
                $success = "Appointment updated successfully.";
                
                // Send email notification
                $patient_query = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
                $patient_query->bind_param("i", $patient_id);
                $patient_query->execute();
                $patient_result = $patient_query->get_result();

                if ($patient = $patient_result->fetch_assoc()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'dulerodha@gmail.com';
                        $mail->Password = 'lvgg abya iorv jpku'; // App password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->setFrom('your_email@gmail.com', 'Your Clinic Name');
                        $mail->addAddress($patient['email'], $patient['full_name']);
                        $mail->Subject = 'Appointment Updated';
                        $mail->Body = "Hello {$patient['full_name']},\n\n" .
                                    "Your appointment has been updated:\n" .
                                    "New Date & Time: $new_datetime\n" .
                                    "Join via Jitsi: $new_jitsi_link\n\nThank you!";

                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Mailer Error: " . $mail->ErrorInfo);
                    }
                }
            } else {
                $error = "Failed to update appointment. Please try again.";
            }
        }
    } else {
        $error = "Invalid appointment or permission denied.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $date = $_POST['appointment_date'];
    $hour = intval($_POST['appointment_hour']);
    $appointment_datetime = "$date $hour:00:00";

    $stmt = $conn->prepare("SELECT * FROM appointments 
                            WHERE doctor_id = ? 
                            AND appointment_datetime = ?");
    $stmt->bind_param("is", $doctor_id, $appointment_datetime);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        $error = "This time slot is already booked.";
    } else {
        $jitsi_link = "https://meet.jit.si/Appointment_{$doctor_id}_{$patient_id}_" . time();

        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, status, jitsi_link, created_at) 
                                VALUES (?, ?, ?, 'booked', ?, NOW())");
        $stmt->bind_param("iiss", $patient_id, $doctor_id, $appointment_datetime, $jitsi_link);

        if ($stmt->execute()) {
            $success = true;

            $patient_query = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
            $patient_query->bind_param("i", $patient_id);
            $patient_query->execute();
            $patient_result = $patient_query->get_result();

            if ($patient = $patient_result->fetch_assoc()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'dulerodha@gmail.com';
                    $mail->Password = 'lvgg abya iorv jpku'; // App password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('your_email@gmail.com', 'Your Clinic Name');
                    $mail->addAddress($patient['email'], $patient['full_name']);
                    $mail->Subject = 'Appointment Confirmation';
                    $mail->Body = "Hello {$patient['full_name']},\n\n" .
                                  "Your appointment has been confirmed:\n" .
                                  "Doctor ID: $doctor_id\nDate & Time: $appointment_datetime\n" .
                                  "Join via Jitsi: $jitsi_link\n\nThank you!";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                }
            }
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
                    <h1 class="h2">
                        <i class="bi bi-calendar-plus me-2"></i>Book an Appointment
                    </h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-plus me-2"></i>
                        New Appointment
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">Appointment booked successfully!</div>
                        <?php elseif (!empty($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php elseif (!empty($cancel_success)): ?>
                            <div class="alert alert-success"><?= $cancel_success ?></div>
                        <?php elseif (!empty($cancel_error)): ?>
                            <div class="alert alert-danger"><?= $cancel_error ?></div>
                        <?php endif; ?>

                        <form method="POST" id="bookingForm">
                            <div class="mb-3">
                                <label for="doctor" class="form-label">Choose Doctor:</label>
                                <select class="form-select" name="doctor_id" id="doctor" required>
                                    <option value="">-- Select --</option>
                                    <?php
                                    $doctors = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'doctor'");
                                    while ($doc = $doctors->fetch_assoc()):
                                        echo "<option value='{$doc['user_id']}'>{$doc['full_name']}</option>";
                                    endwhile;
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Pick a Date:</label>
                                <input type="text" class="form-control" id="appointment_date" name="appointment_date" disabled required>
                            </div>

                            <div class="mb-3" id="time_slots_container" style="display:none;">
                                <label class="form-label">Available Time Slots:</label>
                                <div id="time_slots" class="d-flex flex-wrap gap-2 mt-2"></div>
                                <input type="hidden" name="appointment_hour" id="selected_hour" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Book Appointment</button>
                        </form>
                        
                        <!-- Hidden form for appointment cancellation -->
                        <form id="cancelForm" method="POST" style="display:none;">
                            <input type="hidden" name="cancel_appointment" id="cancel_appointment" value="">
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="bi bi-calendar-check me-2"></i>
                        Your Appointments
                    </div>
                    <div class="card-body">
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
                                    SELECT a.*, u.full_name AS doctor_name 
                                    FROM appointments a
                                    JOIN users u ON a.doctor_id = u.user_id
                                    WHERE a.patient_id = ? 
                                    AND a.appointment_datetime >= NOW()
                                    ORDER BY a.appointment_datetime ASC
                                ");
                                $upcoming_query->bind_param("i", $patient_id);
                                $upcoming_query->execute();
                                $upcoming_result = $upcoming_query->get_result();
                                
                                if ($upcoming_result->num_rows > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Doctor</th>';
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
                                        echo '<td>' . htmlspecialchars($appointment['doctor_name']) . '</td>';
                                        echo '<td>' . $date . '</td>';
                                        echo '<td>' . $time . '</td>';
                                        echo '<td><span class="badge bg-success">Upcoming</span></td>';
                                        echo '<td>';
                                        
                                        // Check if appointment is within 15 minutes of current time
                                        $appointment_time = strtotime($appointment['appointment_datetime']);
                                        $current_time = time();
                                        $time_diff = $appointment_time - $current_time;
                                        
                                        if ($time_diff <= 900 && $time_diff > 0) { // 15 minutes = 900 seconds
                                            echo '<a href="' . htmlspecialchars($appointment['jitsi_link']) . '" target="_blank" class="btn btn-sm btn-primary me-1">Join</a>';
                                        }
                                        
                                        echo '<button type="button" class="btn btn-sm btn-warning me-1" onclick="openEditModal(' . $appointment['appointment_id'] . ', \'' . $appointment['doctor_name'] . '\', \'' . date('Y-m-d', strtotime($appointment['appointment_datetime'])) . '\', ' . date('G', strtotime($appointment['appointment_datetime'])) . ')">';
                                        echo '<i class="bi bi-pencil me-1"></i>Edit</button>';
                                        
                                        echo '<button type="button" class="btn btn-sm btn-danger" onclick="confirmCancel(' . $appointment['appointment_id'] . ')">Cancel</button>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-info">';
                                    echo 'You have no upcoming appointments.';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            
                            <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                                <?php
                                // Get past appointments
                                $past_query = $conn->prepare("
                                    SELECT a.*, u.full_name AS doctor_name 
                                    FROM appointments a
                                    JOIN users u ON a.doctor_id = u.user_id
                                    WHERE a.patient_id = ? 
                                    AND a.appointment_datetime < NOW()
                                    ORDER BY a.appointment_datetime DESC
                                    LIMIT 10
                                ");
                                $past_query->bind_param("i", $patient_id);
                                $past_query->execute();
                                $past_result = $past_query->get_result();
                                
                                if ($past_result->num_rows > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Doctor</th>';
                                    echo '<th>Date</th>';
                                    echo '<th>Time</th>';
                                    echo '<th>Status</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    
                                    while ($appointment = $past_result->fetch_assoc()) {
                                        $date = date('l, F j, Y', strtotime($appointment['appointment_datetime']));
                                        $time = date('g:i A', strtotime($appointment['appointment_datetime']));
                                        
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($appointment['doctor_name']) . '</td>';
                                        echo '<td>' . $date . '</td>';
                                        echo '<td>' . $time . '</td>';
                                        echo '<td><span class="badge bg-secondary">Completed</span></td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-info">';
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

    <?php include_once '../includes/footer.php'; ?>
    
    <!-- Edit Appointment Modal -->
    <div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-labelledby="editAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAppointmentModalLabel">Edit Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAppointmentForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="edit_appointment_id" id="edit_appointment_id">
                        <div class="mb-3">
                            <label class="form-label">Doctor:</label>
                            <input type="text" class="form-control" id="edit_doctor_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_appointment_date" class="form-label">New Date:</label>
                            <input type="text" class="form-control" id="edit_appointment_date" name="edit_appointment_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Time Slot:</label>
                            <div id="edit_time_slots" class="d-flex flex-wrap gap-2 mt-2"></div>
                            <input type="hidden" name="edit_appointment_hour" id="edit_selected_hour" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function confirmCancel(appointmentId) {
            if (confirm("Are you sure you want to cancel this appointment?")) {
                document.getElementById('cancel_appointment').value = appointmentId;
                document.getElementById('cancelForm').submit();
            }
        }

        const doctorSelect = document.getElementById('doctor');
        const dateInput = document.getElementById('appointment_date');
        const timeSlotsContainer = document.getElementById('time_slots_container');
        const timeSlots = document.getElementById('time_slots');
        const selectedHourInput = document.getElementById('selected_hour');

        doctorSelect.addEventListener('change', function () {
            if (this.value) {
                dateInput.disabled = false;
                flatpickr(dateInput, {
                    minDate: "today",
                    onChange: function (selectedDates, dateStr) {
                        fetchAvailableSlots(doctorSelect.value, dateStr);
                    }
                });
            } else {
                dateInput.disabled = true;
                timeSlotsContainer.style.display = 'none';
            }
        });

        function fetchAvailableSlots(doctorId, date) {
            fetch(`book_appointment.php?fetch_slots=1&doctor_id=${doctorId}&date=${date}`)
                .then(response => response.json())
                .then(bookedHours => {
                    const hours = [...Array(8)].map((_, i) => i + 9); // 9am to 4pm
                    timeSlots.innerHTML = '';
                    timeSlotsContainer.style.display = 'block';

                    hours.forEach(hour => {
                        const isBooked = bookedHours.includes(hour);
                        const label = `${hour <= 12 ? hour : hour - 12} ${hour < 12 ? 'AM' : 'PM'}`;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm ' + (isBooked ? 'btn-secondary' : 'btn-outline-success');
                        btn.textContent = label;
                        btn.disabled = isBooked;
                        btn.dataset.hour = hour;

                        if (!isBooked) {
                            btn.addEventListener('click', () => {
                                document.querySelectorAll('#time_slots button').forEach(b => b.classList.remove('active'));
                                btn.classList.add('active');
                                selectedHourInput.value = hour;
                            });
                        }

                        timeSlots.appendChild(btn);
                    });
                });
        }

        // Edit appointment functionality
        function openEditModal(appointmentId, doctorName, currentDate, currentHour) {
            document.getElementById('edit_appointment_id').value = appointmentId;
            document.getElementById('edit_doctor_name').value = doctorName;
            
            // Initialize date picker for edit modal
            const editDatePicker = flatpickr("#edit_appointment_date", {
                minDate: "today",
                defaultDate: currentDate,
                onChange: function(selectedDates, dateStr) {
                    fetchAvailableSlotsForEdit(doctorSelect.value, dateStr, currentHour);
                }
            });
            
            // Show the modal
            const editModal = new bootstrap.Modal(document.getElementById('editAppointmentModal'));
            editModal.show();
        }

        function fetchAvailableSlotsForEdit(doctorId, date, currentHour) {
            fetch(`book_appointment.php?fetch_slots=1&doctor_id=${doctorId}&date=${date}`)
                .then(response => response.json())
                .then(bookedHours => {
                    const hours = [...Array(8)].map((_, i) => i + 9); // 9am to 4pm
                    const editTimeSlots = document.getElementById('edit_time_slots');
                    editTimeSlots.innerHTML = '';

                    hours.forEach(hour => {
                        // Allow booking the current hour if it's the same appointment
                        const isBooked = bookedHours.includes(hour) && hour !== currentHour;
                        const label = `${hour <= 12 ? hour : hour - 12} ${hour < 12 ? 'AM' : 'PM'}`;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm ' + (isBooked ? 'btn-secondary' : 'btn-outline-success');
                        if (hour === currentHour) {
                            btn.classList.add('active');
                            document.getElementById('edit_selected_hour').value = hour;
                        }
                        btn.textContent = label;
                        btn.disabled = isBooked;
                        btn.dataset.hour = hour;

                        if (!isBooked) {
                            btn.addEventListener('click', () => {
                                document.querySelectorAll('#edit_time_slots button').forEach(b => b.classList.remove('active'));
                                btn.classList.add('active');
                                document.getElementById('edit_selected_hour').value = hour;
                            });
                        }

                        editTimeSlots.appendChild(btn);
                    });
                });
        }

        // Handle edit form submission
        document.getElementById('editAppointmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('edit_appointment', '1');

            fetch('book_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the appointment.');
            });
        });
    </script>
</body>
</html>
