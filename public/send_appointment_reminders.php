<?php
require_once '../database/db_connection.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get appointments that are exactly 24 hours away
$reminder_query = $conn->prepare("
    SELECT a.*, 
           p.full_name as patient_name, p.email as patient_email,
           d.full_name as doctor_name
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    JOIN users d ON a.doctor_id = d.user_id
    WHERE a.appointment_datetime BETWEEN DATE_ADD(NOW(), INTERVAL 24 HOUR) AND DATE_ADD(NOW(), INTERVAL 24 HOUR + 1 HOUR)
    AND a.status = 'booked'
");

$reminder_query->execute();
$result = $reminder_query->get_result();

while ($appointment = $result->fetch_assoc()) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dulerodha@gmail.com';
        $mail->Password = 'lvgg abya iorv jpku';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('dulerodha@gmail.com', 'Your Clinic Name');
        $mail->addAddress($appointment['patient_email'], $appointment['patient_name']);
        
        $mail->Subject = 'Appointment Reminder';
        
        $appointment_time = date('g:i A', strtotime($appointment['appointment_datetime']));
        $appointment_date = date('l, F j, Y', strtotime($appointment['appointment_datetime']));
        
        $mail->Body = "Dear {$appointment['patient_name']},\n\n" .
                     "This is a reminder that you have an appointment tomorrow:\n\n" .
                     "Date: $appointment_date\n" .
                     "Time: $appointment_time\n" .
                     "Doctor: {$appointment['doctor_name']}\n\n" .
                     "Join via Jitsi: {$appointment['jitsi_link']}\n\n" .
                     "Please make sure to join the video call 5 minutes before your scheduled time.\n\n" .
                     "Best regards,\nYour Clinic Name";

        $mail->send();
        
    } catch (Exception $e) {
        error_log("Failed to send reminder email for appointment {$appointment['appointment_id']}: " . $mail->ErrorInfo);
    }
} 