<?php
require_once '../database/db_connection.php';
session_start();
if (isset($_SESSION['user_id'])){
    // If user is already logged in, log them out and redirect to login page
    session_destroy();
    header("Location: login.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Query the database to fetch user details based on the email provided
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hash, $role);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            // Store user data in session
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $_SESSION['role'] = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
            // Check if the user has a profile based on their role
            if ($role == 'doctor') {
                // Check if the doctor has a profile in the doctor_profiles table
                $profile_check = $conn->prepare("SELECT doctor_id FROM doctor_profiles WHERE doctor_id = ?");
                $profile_check->bind_param("i", $id);
                $profile_check->execute();
                $profile_check->store_result();

                // If no doctor profile exists, redirect to the profile creation page
                if ($profile_check->num_rows == 0) {
                    header("Location: create_doctor_profile.php");
                    exit;
                }
            } elseif ($role == 'patient') {
                // Check if the patient has a profile in the patient_profiles table
                $profile_check = $conn->prepare("SELECT patient_id FROM patient_profiles WHERE patient_id = ?");
                $profile_check->bind_param("i", $id);
                $profile_check->execute();
                $profile_check->store_result();

                // If no patient profile exists, redirect to the profile creation page
                if ($profile_check->num_rows == 0) {
                    header("Location: create_patient_profile.php");
                    exit;
                }
            }

            // Redirect based on user role
            if ($_SESSION['role'] == 'doctor' ) {
                header("Location: dashboard.php"); // Redirect to doctor's dashboard
            } elseif ($_SESSION['role'] == 'patient') {
                header("Location: patient_dashboard.php"); // Redirect to patient dashboard
            }
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "Invalid credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Telehealth System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .login-container {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include_once '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="login-container">
            <div class="login-card">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h3 mb-4 text-center">Login</h1>
                        <?php if (isset($error)): ?>
                            <div style="color: red; text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Login</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='register.php';">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/footer.php'; ?>


    <!-- Bootstrap JS Bundle with Popper -->
</body>
</html>
