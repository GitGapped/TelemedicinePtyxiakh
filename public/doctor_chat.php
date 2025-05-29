<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: home.php");
    exit();
}

require_once '../database/db_connection.php';
require_once '../includes/encryption.php';
define('INCLUDED', true);

$doctor_id = $_SESSION['user_id'];
$selected_patient_id = $_GET['patient_id'] ?? null;

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['receiver_id'])) {
    $message = trim($_POST['message']);
    $receiver_id = filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT);
    
    // Validate inputs
    if (empty($message) || $receiver_id === false) {
        header("Location: doctor_chat.php?patient_id=$receiver_id&error=invalid_input");
        exit();
    }
    
    // Verify the receiver is actually a patient
    $verify_patient = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'patient'");
    $verify_patient->bind_param("i", $receiver_id);
    $verify_patient->execute();
    if ($verify_patient->get_result()->num_rows === 0) {
        header("Location: doctor_chat.php?error=invalid_receiver");
        exit();
    }
    
    // Encrypt the message before storing
    $encrypted_message = encryptMessage($message);
    if ($encrypted_message === false) {
        header("Location: doctor_chat.php?patient_id=$receiver_id&error=encryption_failed");
        exit();
    }
    
    // Insert encrypted message using prepared statement
    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $doctor_id, $receiver_id, $encrypted_message);
    
    if ($stmt->execute()) {
        header("Location: doctor_chat.php?patient_id=$receiver_id");
    } else {
        header("Location: doctor_chat.php?patient_id=$receiver_id&error=message_failed");
    }
    exit();
}

// Fetch patients
$patients = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'patient'");

// Fetch chat messages if a patient is selected
$chat_messages = [];
if ($selected_patient_id) {
    $stmt = $conn->prepare("SELECT * FROM chat_messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC");
    $stmt->bind_param("iiii", $doctor_id, $selected_patient_id, $selected_patient_id, $doctor_id);
    $stmt->execute();
    $chat_messages = $stmt->get_result();
}

// Fetch full names for doctor and patient
$doctor_name = $_SESSION['name']; // Already in session

$patient_name = '';
if ($selected_patient_id) {
    $patient_result = $conn->query("SELECT full_name FROM users WHERE user_id = '$selected_patient_id'");
    if ($patient_result && $row = $patient_result->fetch_assoc()) {
        $patient_name = $row['full_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Chat</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
    <script src='https://meet.jit.si/external_api.js'></script>
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
                    <h1 class="h2">Doctor Chat</h1>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-chat-dots me-2"></i>
                        Secure Messaging
                    </div>
                    <div class="card-body">
                        <div class="chat-layout">
                            <!-- Patients column (left side) -->
                            <div class="doctors-column">
                                <div class="chat-section-title">
                                    <i class="bi bi-people-fill me-2"></i>Select a Patient
                                </div>
                                
                                <div class="doctor-filter">
                                    <input type="text" class="form-control" id="patientSearch" placeholder="Search patients..." onkeyup="filterPatients()">
                                </div>
                                
                                <div class="doctor-cards">
                                    <?php 
                                    // Reset the patients result pointer in case it was already read
                                    if ($patients->num_rows > 0) {
                                        $patients->data_seek(0);
                                    }
                                    
                                    while ($p = $patients->fetch_assoc()): 
                                        $initial = strtoupper(substr($p['full_name'], 0, 1));
                                        $isActive = ($selected_patient_id == $p['user_id']);
                                        
                                        // Check if there's chat history with this patient
                                        $has_history_query = $conn->prepare("
                                            SELECT message_id FROM chat_messages 
                                            WHERE (sender_id = ? AND receiver_id = ?) 
                                               OR (sender_id = ? AND receiver_id = ?)
                                            LIMIT 1
                                        ");
                                        $has_history_query->bind_param("iiii", $doctor_id, $p['user_id'], $p['user_id'], $doctor_id);
                                        $has_history_query->execute();
                                        $has_chat_history = $has_history_query->get_result()->num_rows > 0;
                                    ?>
                                    <a href="doctor_chat.php?patient_id=<?= htmlspecialchars($p['user_id'], ENT_QUOTES, 'UTF-8') ?>" class="doctor-card <?= $isActive ? 'active' : '' ?>">
                                        <div class="doctor-avatar" style="background-color: <?= $isActive ? '#0d6efd' : ($has_chat_history ? '#6c757d' : '#adb5bd') ?>">
                                            <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="doctor-name"><?= htmlspecialchars($p['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ($has_chat_history): ?>
                                            <span class="badge bg-primary doctor-badge">Chat</span>
                                        <?php endif; ?>
                                    </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            
                            <!-- Chat column (right side) -->
                            <div class="chat-column">
                                <?php if ($selected_patient_id): ?>
                                    <div class="chat-header">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-circle fs-3 me-3 text-primary"></i>
                                            <div>
                                                <h5 class="mb-0"><?= htmlspecialchars($patient_name) ?></h5>
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
                                            <div class="message <?= ($msg['sender_id'] == $doctor_id) ? 'sent' : 'received'; ?>">
                                                <div class="message-content">
                                                    <?= htmlspecialchars($decrypted_message); ?>
                                                    <?php if ($msg['sender_id'] == $doctor_id && $msg['is_read']): ?>
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

                                    <!-- Message sending form -->
                                    <form method="POST" action="doctor_chat.php?patient_id=<?= htmlspecialchars($selected_patient_id, ENT_QUOTES, 'UTF-8'); ?>" class="mt-3">
                                        <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($selected_patient_id, ENT_QUOTES, 'UTF-8'); ?>">
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
                                            <h5>Select a patient to start chatting</h5>
                                            <p class="text-muted">Choose a patient from the list on the left to view your conversation</p>
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
    <div>
        <br>
        <br>
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
        
        // Filter patients by name
        function filterPatients() {
            const searchValue = document.getElementById('patientSearch').value.toLowerCase();
            const patientCards = document.querySelectorAll('.doctor-card');
            
            patientCards.forEach(card => {
                const patientName = card.querySelector('.doctor-name').textContent.toLowerCase();
                if (patientName.includes(searchValue)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Video call function
        function startVideoCall() {
            const roomName = "DocChat_<?php echo htmlspecialchars($doctor_id . '_' . $selected_patient_id . '_' . time(), ENT_QUOTES, 'UTF-8'); ?>";
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