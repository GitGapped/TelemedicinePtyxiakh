<?php
session_start();
require_once '../includes/session_manager.php';

// Update the last activity time
$_SESSION['last_activity'] = time();

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true]); 