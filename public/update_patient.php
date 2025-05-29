<?php
require_once '../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];

    // Update users table
    $stmt1 = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
    $stmt1->bind_param("ssi", $full_name, $email, $patient_id);
    $stmt1->execute();

    // Update patient_profiles table
    $stmt2 = $conn->prepare("UPDATE patient_profiles SET date_of_birth = ?, gender = ?, contact_number = ?, address = ? WHERE patient_id = ?");
    $stmt2->bind_param("ssssi", $date_of_birth, $gender, $contact_number, $address, $patient_id);
    $stmt2->execute();

    echo "Patient updated successfully.";
}
?>
