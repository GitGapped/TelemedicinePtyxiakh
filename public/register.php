<?php
session_start();
require_once '../database/db_connection.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If user is already logged in, log them out and redirect to register page
    session_destroy();
    header("Location: register.php");
    exit;
}

$success = '';
$error = '';
$redirect_url = '';
$delay_seconds = 5;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the form data
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $raw_password = $_POST['password'];
    $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
    $privacy_policy = isset($_POST['privacy_policy']) ? true : false;

    // Check if privacy policy is agreed to
    if (!$privacy_policy) {
        $error = "You must agree to the Privacy Policy to register.";
    } else {
        // Check if the email already exists
        $check_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // Password validation
            if (!preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=[\]{};:\'"\\|,.<>\/?]).{6,}$/', $raw_password)) {
                $error = "Password must be at least 6 characters long, contain at least one uppercase letter, and one special character.";
            } else {
                $password = password_hash($raw_password, PASSWORD_DEFAULT);

                // Insert the user
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $full_name, $email, $password, $role);

                if ($stmt->execute()) {
                    // Get the inserted user ID (auto-incremented)
                    $user_id = $stmt->insert_id;

                    // Set success message and redirect URL
                    $success = "Registration successful! You can now log in.";
                    $redirect_url = "login.php";
                } else {
                    $error = "Error: " . $conn->error;
                }

                $stmt->close();
            }
        }

        $check_email->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Telehealth System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .register-container {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin-top: 2%;
        }
        .register-card {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
<?php include_once '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="register-container">
        <div class="register-card">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-4 text-center">Register</h1>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success text-center">
                            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?><br>
                            Redirecting to login page in <span id="countdown"><?php echo htmlspecialchars($delay_seconds, ENT_QUOTES, 'UTF-8'); ?></span> seconds...
                        </div>
                        <meta http-equiv="refresh" content="<?php echo htmlspecialchars($delay_seconds, ENT_QUOTES, 'UTF-8'); ?>;url=<?php echo htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <script>
                            let seconds = <?php echo (int)$delay_seconds; ?>;
                            let redirectUrl = "<?php echo htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8'); ?>";
                            const countdown = document.getElementById('countdown');
                            const interval = setInterval(() => {
                                seconds--;
                                countdown.textContent = seconds;
                                if (seconds <= 0) {
                                    clearInterval(interval);
                                    window.location.href = redirectUrl;
                                }
                            }, 1000);
                        </script>
                    <?php endif; ?>

                    <?php if (empty($success)): ?>
                        <form method="POST" action="register.php" id="registerForm">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required pattern="^(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=[\]{};':\\\|,.<>/?]).{6,}$" title="Password must be at least 6 characters long, contain at least one uppercase letter, and one special character.">
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="patient">Patient</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="privacy_policy" name="privacy_policy" required>
                                <label class="form-check-label" for="privacy_policy">
                                    I have read and agree to the <a href="privacy_policy.php" target="_blank">Privacy Policy</a>
                                </label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="text-decoration-none">Already have an account? Login</a>
                            </div>
                        </form>
                        <div id="errorMessage" style="text-align: center; margin-top: 20px;"></div>
                        <script>
                            document.getElementById('registerForm').addEventListener('submit', function(event) {
                                event.preventDefault();
                                const formData = new FormData(this);
                                fetch('register.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.text())
                                .then(html => {
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');
                                    const errorDiv = doc.querySelector('.alert-danger');
                                    const successDiv = doc.querySelector('.alert-success');
                                    if (errorDiv) {
                                        document.getElementById('errorMessage').innerHTML = errorDiv.innerHTML;
                                        document.getElementById('errorMessage').style.color = 'red';
                                    } else if (successDiv) {
                                        document.getElementById('errorMessage').innerHTML = successDiv.innerHTML;
                                        document.getElementById('errorMessage').style.color = 'green';
                                        setTimeout(() => {
                                            window.location.href = 'login.php';
                                        }, 3000);
                                    }
                                });
                            });
                        </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

</body>
</html>
