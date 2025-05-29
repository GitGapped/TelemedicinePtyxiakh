<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: home.php");
    exit();
}

require_once '../database/db_connection.php';
require_once '../includes/encryption.php';
define('INCLUDED', true);

$patient_id = $_SESSION['user_id'];

// Get the selected doctor ID from GET parameter, or find the most recent chat doctor
$selected_doctor_id = null;

if (isset($_GET['doctor_id'])) {
    // If doctor_id is in URL, use that
    $selected_doctor_id = intval($_GET['doctor_id']);
} else {
    // Otherwise, find the most recent chat
    $recent_query = $conn->prepare("
        SELECT IF(sender_id = ?, receiver_id, sender_id) as doctor_id
        FROM chat_messages 
        WHERE sender_id = ? OR receiver_id = ?
        ORDER BY sent_at DESC 
        LIMIT 1
    ");
    $recent_query->bind_param("iii", $patient_id, $patient_id, $patient_id);
    $recent_query->execute();
    $recent_result = $recent_query->get_result();
    
    if ($recent_result->num_rows > 0) {
        $row = $recent_result->fetch_assoc();
        // Make sure it's a doctor
        $check_doctor = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'doctor'");
        $check_doctor->bind_param("i", $row['doctor_id']);
        $check_doctor->execute();
        if ($check_doctor->get_result()->num_rows > 0) {
            $selected_doctor_id = $row['doctor_id'];
        }
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['receiver_id'])) {
    $message = trim($_POST['message']);
    $receiver_id = filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT);
    
    // Validate inputs
    if (empty($message) || $receiver_id === false) {
        header("Location: patient_chat.php?doctor_id=$receiver_id&error=invalid_input");
        exit();
    }
    
    // Verify the receiver is actually a doctor
    $verify_doctor = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'doctor'");
    $verify_doctor->bind_param("i", $receiver_id);
    $verify_doctor->execute();
    if ($verify_doctor->get_result()->num_rows === 0) {
        header("Location: patient_chat.php?error=invalid_receiver");
        exit();
    }
    
    // Encrypt the message before storing
    $encrypted_message = encryptMessage($message);
    if ($encrypted_message === false) {
        header("Location: patient_chat.php?doctor_id=$receiver_id&error=encryption_failed");
        exit();
    }
    
    // Insert encrypted message using prepared statement
    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $patient_id, $receiver_id, $encrypted_message);
    
    if ($stmt->execute()) {
        header("Location: patient_chat.php?doctor_id=$receiver_id");
    } else {
        header("Location: patient_chat.php?doctor_id=$receiver_id&error=message_failed");
    }
    exit();
}

// Fetch doctors that patient has communicated with
$doctor_query = "
    SELECT DISTINCT u.user_id, u.full_name 
    FROM users u
    LEFT JOIN chat_messages cm ON (u.user_id = cm.sender_id OR u.user_id = cm.receiver_id)
                              AND (cm.sender_id = $patient_id OR cm.receiver_id = $patient_id)
    WHERE u.role = 'doctor'
    ORDER BY 
        CASE WHEN cm.message_id IS NOT NULL THEN 0 ELSE 1 END, 
        cm.sent_at DESC
";
$doctors = $conn->query($doctor_query);

// Fetch chat messages with selected doctor
$chat_messages = [];
if ($selected_doctor_id) {
    $stmt = $conn->prepare("SELECT * FROM chat_messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC");
    $stmt->bind_param("iiii", $patient_id, $selected_doctor_id, $selected_doctor_id, $patient_id);
    $stmt->execute();
    $chat_messages = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Chat</title>
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
                    <h1 class="h2">Message Your Doctor</h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-chat-dots me-2"></i>
                        Secure Messaging
                    </div>
                    <div class="card-body">
                        <div class="chat-layout">
                            <!-- Doctors column (left side) -->
                            <div class="doctors-column">
                                <div class="chat-section-title">
                                    <i class="bi bi-people-fill me-2"></i>Select a Doctor
                                </div>
                                
                                <div class="doctor-filter">
                                    <input type="text" class="form-control" id="doctorSearch" placeholder="Search doctors..." onkeyup="filterDoctors()">
                                </div>
                                
                                <div class="doctor-cards">
                                    <?php 
                                    // Reset the doctors result pointer in case it was already read
                                    if ($doctors->num_rows > 0) {
                                        $doctors->data_seek(0);
                                    }
                                    
                                    while ($d = $doctors->fetch_assoc()): 
                                        $initial = strtoupper(substr($d['full_name'], 0, 1));
                                        $isRecent = ($selected_doctor_id == $d['user_id'] && !isset($_GET['doctor_id']));
                                        $isActive = ($selected_doctor_id == $d['user_id']);
                                        
                                        // Check if there's chat history with this doctor
                                        $has_history_query = $conn->prepare("
                                            SELECT message_id FROM chat_messages 
                                            WHERE (sender_id = ? AND receiver_id = ?) 
                                               OR (sender_id = ? AND receiver_id = ?)
                                            LIMIT 1
                                        ");
                                        $has_history_query->bind_param("iiii", $patient_id, $d['user_id'], $d['user_id'], $patient_id);
                                        $has_history_query->execute();
                                        $has_chat_history = $has_history_query->get_result()->num_rows > 0;
                                    ?>
                                    <a href="patient_chat.php?doctor_id=<?= htmlspecialchars($d['user_id'], ENT_QUOTES, 'UTF-8') ?>" class="doctor-card <?= $isActive ? 'active' : '' ?>">
                                        <div class="doctor-avatar" style="background-color: <?= $isActive ? '#0d6efd' : ($has_chat_history ? '#6c757d' : '#adb5bd') ?>">
                                            <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="doctor-name"><?= htmlspecialchars($d['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ($has_chat_history): ?>
                                            <span class="badge bg-primary doctor-badge">Chat</span>
                                        <?php endif; ?>
                                        <?php if ($isRecent): ?>
                                            <span class="recent-badge" title="Most recent conversation"></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            
                            <!-- Chat column (right side) -->
                            <div class="chat-column">
                                <?php if ($selected_doctor_id): ?>
                                    <?php 
                                    // Get doctor name for display
                                    $doctor_name_query = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
                                    $doctor_name_query->bind_param("i", $selected_doctor_id);
                                    $doctor_name_query->execute();
                                    $doctor_result = $doctor_name_query->get_result();
                                    $doctor_name = $doctor_result->fetch_assoc()['full_name'] ?? 'Doctor';
                                    ?>
                                    
                                    <div class="chat-header">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-circle fs-3 me-3 text-primary"></i>
                                            <div>
                                                <h5 class="mb-0"><?= htmlspecialchars($doctor_name) ?></h5>
                                                <small class="text-muted">Messages are private and secure</small>
                                            </div>
                                            <button type="button" class="btn btn-success ms-auto" onclick="startVideoCall()">
                                                <i class="bi bi-camera-video-fill me-2"></i>Video Call
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="chat-box">
                                        <div class="chat-container">
                                        <?php while ($msg = $chat_messages->fetch_assoc()): 
                                            $decrypted_message = decryptMessage($msg['message']);
                                            if ($decrypted_message === false) {
                                                $decrypted_message = "Error: Unable to decrypt message";
                                            }
                                        ?>
                                            <div class="message <?= ($msg['sender_id'] == $patient_id) ? 'sent' : 'received'; ?>">
                                                <div class="message-content">
                                                    <?= htmlspecialchars($decrypted_message); ?>
                                                    <?php if ($msg['sender_id'] == $patient_id && $msg['is_read']): ?>
                                                        <span class="read-mark" title="Read"><i class="bi bi-check2-all"></i></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="message-time">
                                                    <?= date('g:i a, M j', strtotime($msg['sent_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                        </div>
                                    </div>

                                    <!-- Message form -->
                                    <form method="POST" action="patient_chat.php?doctor_id=<?= htmlspecialchars($selected_doctor_id, ENT_QUOTES, 'UTF-8'); ?>" class="mt-3">
                                        <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($selected_doctor_id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="mb-3">
                                            <textarea name="message" class="form-control chat-textarea" required placeholder="Type your message..." rows="3"></textarea>
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" class="btn btn-primary send-btn">
                                                <i class="bi bi-send me-2"></i>Send Message
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <div class="text-center p-5">
                                            <i class="bi bi-chat-square-text fs-1 text-muted mb-3"></i>
                                            <h5>Select a doctor to start chatting</h5>
                                            <p class="text-muted">Choose a doctor from the list on the left to view your conversation</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include_once '../includes/footer.php'; ?>
    
    <script>
        // Auto-scroll to bottom of chat
        window.onload = function() {
            var chatBox = document.querySelector('.chat-box');
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        };
        
        // Filter doctors by name
        function filterDoctors() {
            const searchValue = document.getElementById('doctorSearch').value.toLowerCase();
            const doctorCards = document.querySelectorAll('.doctor-card');
            
            doctorCards.forEach(card => {
                const doctorName = card.querySelector('.doctor-name').textContent.toLowerCase();
                if (doctorName.includes(searchValue)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Video call function
        function startVideoCall() {
            const roomName = "DocChat_<?php echo $selected_doctor_id . '_' . $patient_id . '_' . time(); ?>";
            const domain = "meet.jit.si";
            
            // Calculate popup dimensions (80% of screen size)
            const width = Math.min(window.innerWidth * 0.8, 1200);
            const height = Math.min(window.innerHeight * 0.8, 800);
            
            // Calculate popup position (center of screen)
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;
            
            // Open popup window
            const popup = window.open(
                `https://${domain}/${roomName}`,
                'Video Call',
                `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes,status=yes`
            );
            
            // Focus the popup
            if (popup) {
                popup.focus();
            } else {
                alert('Please allow popups for this website to start video calls.');
            }
        }
    </script>
</body>
</html>
