<?php
require_once 'config/database.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $userType = getUserType();
    switch ($userType) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'faculty':
            redirect('faculty/dashboard.php');
            break;
        case 'student':
            redirect('student/dashboard.php');
            break;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    if (empty($username) || empty($password) || empty($user_type)) {
        $error = 'Please fill in all fields';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        try {
            // Determine which table to query based on user type
            $table = '';
            switch ($user_type) {
                case 'admin':
                    $table = 'admin';
                    break;
                case 'faculty':
                    $table = 'faculty';
                    break;
                case 'student':
                    $table = 'students';
                    break;
                default:
                    $error = 'Invalid user type';
                    break;
            }

            if (!$error) {
                $query = "SELECT * FROM $table WHERE username = ? OR email = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);                if ($user && password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];

                    // Store additional info based on user type
                    if ($user_type == 'faculty') {
                        $_SESSION['faculty_id'] = $user['faculty_id'];
                        $_SESSION['role'] = $user['role'];
                    } elseif ($user_type == 'student') {
                        $_SESSION['student_id'] = $user['student_id'];
                    }

                    // Check if it's first login for faculty and students
                    if (($user_type == 'faculty' || $user_type == 'student') && isset($user['is_first_login']) && $user['is_first_login']) {
                        $_SESSION['first_login'] = true;
                        redirect('reset_password.php');
                    }

                    // Redirect to appropriate dashboard
                    switch ($user_type) {
                        case 'admin':
                            redirect('admin/dashboard.php');
                            break;
                        case 'faculty':
                            redirect('faculty/dashboard.php');
                            break;
                        case 'student':
                            redirect('student/dashboard.php');
                            break;
                    }
                } else {
                    $error = 'Invalid username or password';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Student Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="notification error" style="position: static; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="validate-form" id="loginForm">
                <div class="role-selector">
                    <button type="button" class="role-btn" data-role="admin">Admin</button>
                    <button type="button" class="role-btn" data-role="faculty">Faculty</button>
                    <button type="button" class="role-btn" data-role="student">Student</button>
                </div>

                <input type="hidden" name="user_type" id="user_type" required>

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>

            <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                <p style="color: #666; font-size: 0.9rem;">
                    Default credentials:<br>
                    Admin: admin / password<br>
                    Principal: principal / password<br>
                    Teacher: teacher1 / password
                </p>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Auto-select admin role by default
        document.addEventListener('DOMContentLoaded', function() {
            const adminBtn = document.querySelector('[data-role="admin"]');
            if (adminBtn) {
                adminBtn.click();
            }
        });
    </script>
</body>
</html>
