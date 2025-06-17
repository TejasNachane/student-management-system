<?php
require_once '../config/database.php';

// Check if user is logged in as principal
if (!isLoggedIn() || getUserType() !== 'faculty' || (isset($_SESSION['role']) && $_SESSION['role'] !== 'principal')) {
    showNotification('Only principals can add new teachers', 'error');
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_id = trim($_POST['faculty_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $department = trim($_POST['department']);

    // Validation
    if (empty($faculty_id) || empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields including password';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        try {
            // Check if faculty_id, username, or email already exists
            $check_query = "SELECT id FROM faculty WHERE faculty_id = ? OR username = ? OR email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$faculty_id, $username, $email]);
            
            if ($check_stmt->fetch()) {
                $error = 'Faculty ID, username, or email already exists';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new teacher (role is always 'teacher' when added by principal)
                $insert_query = "INSERT INTO faculty (faculty_id, username, email, password, full_name, role, phone, address, qualification, department, is_first_login) VALUES (?, ?, ?, ?, ?, 'teacher', ?, ?, ?, ?, TRUE)";
                $insert_stmt = $db->prepare($insert_query);
                
                if ($insert_stmt->execute([$faculty_id, $username, $email, $hashed_password, $full_name, $phone, $address, $qualification, $department])) {
                    $success = 'Teacher added successfully';
                    showNotification('Teacher added successfully. Faculty ID: ' . $faculty_id . ', Password: ' . $password . ' (Teacher will need to change on first login)', 'success');
                    
                    // Clear form data
                    $_POST = array();
                } else {
                    $error = 'Failed to add teacher';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Generate a unique faculty ID
function generateFacultyId() {
    return 'TCH' . date('Y') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Add New Teacher</h1>
                <a href="manage_teachers.php" class="btn btn-primary">Back to Teachers List</a>
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

            <form method="POST" class="validate-form" id="addTeacherForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="faculty_id">Faculty ID *</label>
                        <input type="text" id="faculty_id" name="faculty_id" class="form-control" 
                               value="<?php echo isset($_POST['faculty_id']) ? htmlspecialchars($_POST['faculty_id']) : generateFacultyId(); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Initial Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                        <small style="color: #666; font-size: 0.8rem;">Teacher will be required to change this on first login.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" class="form-control" 
                               value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>" 
                               placeholder="e.g., Computer Science, Mathematics">
                    </div>
                </div>

                <div class="form-group">
                    <label for="qualification">Qualification</label>
                    <input type="text" id="qualification" name="qualification" class="form-control" 
                           value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>" 
                           placeholder="e.g., MSc Computer Science, PhD Mathematics">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>

        <!-- Instructions Card -->
        <div class="card" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white;">
            <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                <h3 style="margin: 0; color: white;">Teacher Registration Instructions</h3>
            </div>
            <div style="padding-top: 1rem;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <li>Provide the generated Faculty ID and initial password to the teacher</li>
                    <li>Teacher must change their password on first login</li>
                    <li>Ensure all required fields are filled accurately</li>
                    <li>Email address will be used for communication</li>
                    <li>Teachers can add students and mark attendance</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Auto-generate username from full name
        document.getElementById('full_name').addEventListener('input', function() {
            const fullName = this.value.toLowerCase().replace(/\s+/g, '');
            const usernameField = document.getElementById('username');
            if (usernameField.value === '') {
                usernameField.value = fullName;
            }
        });

        // Auto-generate email from username
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const emailField = document.getElementById('email');
            if (emailField.value === '' && username !== '') {
                emailField.value = username + '@school.com';
            }
        });

        // Generate a random password
        function generatePassword() {
            const password = 'teacher' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            document.getElementById('password').value = password;
        }

        // Add button to generate password
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const generateBtn = document.createElement('button');
            generateBtn.type = 'button';
            generateBtn.className = 'btn btn-sm btn-secondary';
            generateBtn.textContent = 'Generate';
            generateBtn.onclick = generatePassword;
            generateBtn.style.marginTop = '0.5rem';
            
            passwordField.parentNode.appendChild(generateBtn);
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
