<?php
require_once 'config/database.php';

// Check if user is logged in and it's their first login
if (!isLoggedIn() || !isset($_SESSION['first_login'])) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        try {
            $user_type = getUserType();
            $user_id = getUserId();

            // Determine table based on user type
            $table = '';
            switch ($user_type) {
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
                // Verify current password
                $query = "SELECT password FROM $table WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($current_password, $user['password'])) {
                    // Update password and set is_first_login to false
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE $table SET password = ?, is_first_login = FALSE WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    
                    if ($update_stmt->execute([$hashed_password, $user_id])) {
                        unset($_SESSION['first_login']);
                        showNotification('Password updated successfully! Redirecting to dashboard...', 'success');
                        
                        // Redirect to appropriate dashboard after 2 seconds
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = '" . ($user_type == 'faculty' ? 'faculty/dashboard.php' : 'student/dashboard.php') . "';
                            }, 2000);
                        </script>";
                        $success = 'Password updated successfully!';
                    } else {
                        $error = 'Failed to update password';
                    }
                } else {
                    $error = 'Current password is incorrect';
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
    <title>Reset Password - Student Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">First Time Login</h1>
                <p class="login-subtitle">Please change your password to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="notification error" style="position: static; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="notification success" style="position: static; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="validate-form" id="resetPasswordForm">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <small style="color: #666; font-size: 0.8rem;">Use the default password provided by your administrator</small>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                    <small style="color: #666; font-size: 0.8rem;">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Password</button>
            </form>

            <div style="text-align: center; margin-top: 1rem;">
                <a href="logout.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Logout</a>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>

    <?php
    $notification = getNotification();
    if ($notification):
    ?>
    <div data-notification data-message="<?php echo htmlspecialchars($notification['message']); ?>" data-type="<?php echo htmlspecialchars($notification['type']); ?>"></div>
    <?php endif; ?>
</body>
</html>
