<?php 
// Check if a session is already started, if not, start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get unread messages count if user is logged in
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    require_once '../database/db_connection.php';
    $user_id = $_SESSION['user_id'];
    $unread_query = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM chat_messages 
        WHERE receiver_id = ? 
        AND is_read = 0
    ");
    $unread_query->bind_param("i", $user_id);
    $unread_query->execute();
    $unread_count = $unread_query->get_result()->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telehealth System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
    <!-- Google Translate Script -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="home.php">
                <i class="fas fa-hospital-user"></i>
                Telehealth App
            </a>
            
            <!-- Sidebar Toggle Button -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <button class="btn btn-link text-light d-lg-none me-2 sidebar-toggler" type="button" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <?php endif; ?>
            
            <!-- Navbar Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Language Selector -->
                    <li class="nav-item language-selector">
                        <select id="customLangSelect" class="form-select">
                            <option value="en">🇬🇧 English</option>
                            <option value="sq">🇦🇱 Shqip</option>
                            <option value="es">🇪🇸 Español</option>
                            <option value="fr">🇫🇷 Français</option>
                            <option value="de">🇩🇪 Deutsch</option>
                            <option value="it">🇮🇹 Italiano</option>
                            <option value="pt">🇵🇹 Português</option>
                            <option value="ru">🇷🇺 Русский</option>
                            <option value="zh-CN">🇨🇳 中文</option>
                            <option value="ja">🇯🇵 日本語</option>
                            <option value="ko">🇰🇷 한국어</option>
                            <option value="ar">🇸🇦 العربية</option>
                            <option value="hi">🇮🇳 हिन्दी</option>
                            <option value="bn">🇧🇩 বাংলা</option>
                            <option value="tr">🇹🇷 Türkçe</option>
                            <option value="el">🇬🇷 Ελληνικά</option>
                            <option value="nl">🇳🇱 Nederlands</option>
                            <option value="pl">🇵🇱 Polski</option>
                            <option value="sv">🇸🇪 Svenska</option>
                            <option value="da">🇩🇰 Dansk</option>
                            <option value="fi">🇫🇮 Suomi</option>
                            <option value="no">🇳🇴 Norsk</option>
                            <option value="cs">🇨🇿 Čeština</option>
                            <option value="hu">🇭🇺 Magyar</option>
                            <option value="ro">🇷🇴 Română</option>
                            <option value="sk">🇸🇰 Slovenčina</option>
                            <option value="sl">🇸🇮 Slovenščina</option>
                            <option value="et">🇪🇪 Eesti</option>
                            <option value="lv">🇱🇻 Latviešu</option>
                            <option value="lt">🇱🇹 Lietuvių</option>
                        </select>
                    </li>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item me-2">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light" href="register.php">
                                <i class="fas fa-user-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Notification Bell -->
                        <li class="nav-item me-3 position-relative">
                            <a class="nav-link" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                                <h6 class="dropdown-header">Notifications</h6>
                                <div id="notificationList">
                                    <!-- Notifications will be loaded here via AJAX -->
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#" onclick="clearNotifications()">Clear notifications</a>
                            </div>
                        </li>
                        <li class="nav-item me-3">
                            <span class="welcome-message">
                                <i class="fas fa-user-circle"></i>
                                <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?>
                            </span>
                        </li>
                        <?php if (isset($_SESSION['role'])): ?>
                            <li class="nav-item me-2">
                                <?php if ($_SESSION['role'] == 'doctor' ): ?>
                                    <a class="nav-link" href="dashboard.php">
                                        <i class="fas fa-columns me-1"></i> Dashboard
                                    </a>
                                <?php elseif ($_SESSION['role'] == 'patient'): ?>
                                    <a class="nav-link" href="patient_dashboard.php">
                                        <i class="fas fa-columns me-1"></i> Dashboard
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-light" href="logout.php" onclick="return confirmLogout()">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        // Hide Google Translate bar
        function hideGoogleBar() {
            var gtFrame = document.querySelector('iframe.goog-te-banner-frame');
            if (gtFrame) gtFrame.style.display = 'none';
            var gtBar = document.querySelector('.goog-te-banner-frame.skiptranslate');
            if (gtBar) gtBar.style.display = 'none';
            document.body.style.top = '0px';
        }
        setInterval(hideGoogleBar, 500);

        // Google Translate initialization (hidden)
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                autoDisplay: false
            }, 'google_translate_element_hidden');
        }

        // Custom language selector logic
        const langMap = {
            'en': 'en', 'sq': 'sq', 'es': 'es', 'fr': 'fr', 'de': 'de', 'it': 'it', 'pt': 'pt', 'ru': 'ru', 'zh-CN': 'zh-CN', 'ja': 'ja', 'ko': 'ko', 'ar': 'ar', 'hi': 'hi', 'bn': 'bn', 'tr': 'tr', 'el': 'el', 'nl': 'nl', 'pl': 'pl', 'sv': 'sv', 'da': 'da', 'fi': 'fi', 'no': 'no', 'cs': 'cs', 'hu': 'hu', 'ro': 'ro', 'bg': 'bg', 'hr': 'hr', 'sk': 'sk', 'sl': 'sl', 'et': 'et', 'lv': 'lv', 'lt': 'lt'
        };
        document.addEventListener('DOMContentLoaded', function() {
            // Set dropdown to current language
            var currentLang = 'en';
            var gtCookie = document.cookie.match(/googtrans=\/([a-zA-Z-]+)\/([a-zA-Z-]+)/);
            if (gtCookie && gtCookie[2]) {
                currentLang = gtCookie[2];
            }
            document.getElementById('customLangSelect').value = currentLang;
            document.getElementById('customLangSelect').addEventListener('change', function() {
                var lang = this.value;
                document.cookie = 'googtrans=/en/' + lang + ';path=/';
                document.cookie = 'googtrans=/en/' + lang + ';domain=' + window.location.hostname + ';path=/';
                location.reload();
            });
        });

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        // Function to load notifications
        function loadNotifications() {
            fetch('notification_handler.php?action=get_notifications')
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    notificationList.innerHTML = '';
                    
                    if (data.length === 0) {
                        notificationList.innerHTML = '<div class="dropdown-item text-center">No new notifications</div>';
                        return;
                    }

                    data.forEach(notification => {
                        const item = document.createElement('div');
                        item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                        item.innerHTML = `
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${notification.sender_name}</strong>
                                    <p class="mb-0">${notification.message}</p>
                                </div>
                                <small class="notification-time">${notification.time_ago}</small>
                            </div>
                        `;
                        item.onclick = () => {
                            // Mark only this notification as read
                            fetch('notification_handler.php?action=mark_read', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ message_id: notification.id })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove just this notification from the list
                                    item.remove();
                                    
                                    // Update badge count
                                    const badge = document.querySelector('.notification-badge');
                                    if (badge) {
                                        const currentCount = parseInt(badge.textContent);
                                        if (currentCount > 1) {
                                            badge.textContent = currentCount - 1;
                                        } else {
                                            badge.style.display = 'none';
                                        }
                                    }
                                    
                                    // Navigate to the chat
                                    window.location.href = notification.link;
                                }
                            });
                        };
                        notificationList.appendChild(item);
                    });
                });
        }

        // Function to clear notifications
        function clearNotifications() {
            fetch('notification_handler.php?action=mark_read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationList = document.getElementById('notificationList');
                    notificationList.innerHTML = '<div class="dropdown-item text-center">No new notifications</div>';
                    
                    // Update the badge count to 0
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.style.display = 'none';
                    }

                    // Close the dropdown
                    const dropdown = document.querySelector('.dropdown-menu');
                    if (dropdown) {
                        const bsDropdown = bootstrap.Dropdown.getInstance(dropdown);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        }
                    }
                }
            });
        }

        // Load notifications when the dropdown is shown
        document.getElementById('notificationDropdown').addEventListener('show.bs.dropdown', loadNotifications);

        // Check for new notifications every 30 seconds
        setInterval(() => {
            fetch('notification_handler.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.notification-badge');
                    if (data.unread_count > 0) {
                        if (badge) {
                            badge.textContent = data.unread_count;
                            badge.style.display = 'block';
                        }
                    } else if (badge) {
                        badge.style.display = 'none';
                    }
                });
        }, 30000);

        setInterval(function() {
            var gtFrame = document.querySelector('iframe.goog-te-banner-frame');
            if (gtFrame) gtFrame.style.display = 'none';
            var gtBar = document.querySelector('.goog-te-banner-frame.skiptranslate');
            if (gtBar) gtBar.style.display = 'none';
            var skipTranslate = document.querySelector('body > .skiptranslate');
            if (skipTranslate) skipTranslate.style.display = 'none';
            document.body.style.top = '0px';
        }, 500);
    </script>
    <div id="google_translate_element_hidden" style="display:none;"></div>

    <!-- Session Warning Modal -->
    <div class="modal fade" id="sessionWarningModal" tabindex="-1" aria-labelledby="sessionWarningModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sessionWarningModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        Session Expiring Soon
                    </h5>
                </div>
                <div class="modal-body">
                    <p>Your session will expire soon due to inactivity. Would you like to extend your session?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='logout.php'">Logout</button>
                    <button type="button" class="btn btn-primary" onclick="extendSession()">Extend Session</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include session warning script -->
    <script src="../assets/js/session_warning.js"></script>
</body>
</html>