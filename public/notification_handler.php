<?php
session_start();
require_once '../database/db_connection.php';
require_once '../includes/encryption.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get the action from the request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        // Get unread messages
        $query = $conn->prepare("
            SELECT 
                cm.message_id,
                cm.message,
                cm.sent_at,
                cm.is_read,
                u.full_name as sender_name,
                CASE 
                    WHEN ? = 'doctor' THEN 'doctor_chat.php?patient_id='
                    ELSE 'patient_chat.php?doctor_id='
                END as base_url,
                cm.sender_id as chat_id
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.user_id
            WHERE cm.receiver_id = ? AND cm.is_read = 0
            ORDER BY cm.sent_at DESC
            LIMIT 10
        ");

        $query->bind_param("si", $role, $user_id);
        $query->execute();
        $result = $query->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            // Decrypt the message
            $decrypted_message = decryptMessage($row['message']);
            if ($decrypted_message === false) {
                $decrypted_message = "Error: Unable to decrypt message";
            }

            $time_ago = '';
            $sent_time = new DateTime($row['sent_at']);
            $now = new DateTime();
            $interval = $sent_time->diff($now);
            
            if ($interval->d == 0) {
                if ($interval->h == 0) {
                    $time_ago = $interval->i . ' min ago';
                } else {
                    $time_ago = $interval->h . ' hours ago';
                }
            } else {
                $time_ago = $interval->d . ' days ago';
            }
            
            $notifications[] = [
                'id' => $row['message_id'],
                'message' => $decrypted_message,
                'sender_name' => $row['sender_name'],
                'time_ago' => $time_ago,
                'is_read' => $row['is_read'],
                'link' => $row['base_url'] . $row['chat_id']
            ];
        }
        
        echo json_encode($notifications);
        break;
        
    case 'get_unread_count':
        $query = $conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM chat_messages 
            WHERE receiver_id = ? AND is_read = 0
        ");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode(['unread_count' => $row['unread_count']]);
        break;
        
    case 'mark_read':
        // Get the JSON data from the request
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (isset($data['message_id'])) {
            // Mark single message as read
            $query = $conn->prepare("
                UPDATE chat_messages 
                SET is_read = 1 
                WHERE message_id = ? AND receiver_id = ?
            ");
            $query->bind_param("ii", $data['message_id'], $user_id);
        } else {
            // Mark all messages as read
            $query = $conn->prepare("
                UPDATE chat_messages 
                SET is_read = 1 
                WHERE receiver_id = ? AND is_read = 0
            ");
            $query->bind_param("i", $user_id);
        }
        
        if ($query->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update message status']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
} 