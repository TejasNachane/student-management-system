<?php
require_once '../config/database.php';

// Check if user is logged in as faculty
if (!isLoggedIn() || getUserType() !== 'faculty') {
    redirect('../login.php');
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    showNotification('Student ID not provided', 'error');
    redirect('manage_students.php');
}

$database = new Database();
$db = $database->getConnection();
$student_db_id = $_GET['id'];
$faculty_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';

$error = '';
$success = '';

// Get student information
try {
    $student_query = "SELECT * FROM students WHERE id = ?";
    $student_stmt = $db->prepare($student_query);
    $student_stmt->execute([$student_db_id]);
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        showNotification('Student not found', 'error');
        redirect('manage_students.php');
    }
} catch (PDOException $e) {
    showNotification('Database error: ' . $e->getMessage(), 'error');
    redirect('manage_students.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $parent_name = trim($_POST['parent_name']);
    $parent_phone = trim($_POST['parent_phone']);

    // Validation
    if (empty($username) || empty($email) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if username or email already exists for other students
            $check_query = "SELECT id FROM students WHERE (username = ? OR email = ?) AND id != ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$username, $email, $student_db_id]);
            
            if ($check_stmt->fetch()) {
                $error = 'Username or email already exists for another student';
            } else {
                // Update student information
                $update_query = "UPDATE students SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ?, parent_name = ?, parent_phone = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                
                if ($update_stmt->execute([$username, $email, $full_name, $phone, $address, $date_of_birth, $gender, $parent_name, $parent_phone, $student_db_id])) {
                    showNotification('Student information updated successfully', 'success');
                    
                    // Refresh student data
                    $student_stmt->execute([$student_db_id]);
                    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $success = 'Student information updated successfully';
                } else {
                    $error = 'Failed to update student information';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get student's enrolled courses
try {
    $courses_query = "
        SELECT c.course_code, c.course_name, c.credits, e.enrollment_date, e.status
        FROM enrollments e
        JOIN courses c ON e.course_code = c.course_code
        WHERE e.student_id = ?
        ORDER BY e.enrollment_date DESC
    ";
    $courses_stmt = $db->prepare($courses_query);
    $courses_stmt->execute([$student['student_id']]);
    $enrolled_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $enrolled_courses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Edit Student Information</h1>
                <div style="display: flex; gap: 1rem;">
                    <a href="manage_students.php" class="btn btn-primary">Back to Students</a>
                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-info">View Details</a>
                </div>
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

            <!-- Student Basic Info -->
            <div class="card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; margin-bottom: 2rem;">
                <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                    <h3 style="margin: 0; color: white;">Student: <?php echo htmlspecialchars($student['full_name']); ?></h3>
                </div>
                <div style="padding-top: 1rem;">
                    <div class="form-row">
                        <div><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></div>
                        <div><strong>Registration Date:</strong> <?php echo date('F d, Y', strtotime($student['created_at'])); ?></div>
                    </div>
                </div>
            </div>

            <form method="POST" class="validate-form" id="editStudentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($student['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                               value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($student['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="parent_name">Parent/Guardian Name</label>
                        <input type="text" id="parent_name" name="parent_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['parent_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="parent_phone">Parent/Guardian Phone</label>
                        <input type="tel" id="parent_phone" name="parent_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($student['parent_phone']); ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>

        <!-- Enrolled Courses -->
        <?php if (!empty($enrolled_courses)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Enrolled Courses</h2>
                <?php if ($faculty_role == 'principal'): ?>
                    <a href="../admin/enroll_student.php" class="btn btn-success">Enroll in New Course</a>
                <?php endif; ?>
            </div>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolled_courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['credits']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></td>
                            <td>
                                <span class="badge <?php echo $course['status'] == 'active' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($course['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Enrolled Courses</h2>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <p>This student is not enrolled in any courses yet.</p>
                <?php if ($faculty_role == 'principal'): ?>
                    <a href="../admin/enroll_student.php" class="btn btn-primary">Enroll in Course</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Additional Actions</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-info">View Full Details</a>
                <?php if ($faculty_role == 'principal'): ?>
                    <a href="../admin/enroll_student.php" class="btn btn-success">Enroll in Course</a>
                <?php endif; ?>
                <?php if ($faculty_role == 'teacher'): ?>
                    <a href="mark_attendance.php" class="btn btn-warning">Mark Attendance</a>
                <?php endif; ?>
                <a href="manage_students.php" class="btn btn-primary">Back to Students List</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    
    <style>
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }
        .badge-success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        .badge-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
    </style>

    <?php
    $notification = getNotification();
    if ($notification):
    ?>
    <div data-notification data-message="<?php echo htmlspecialchars($notification['message']); ?>" data-type="<?php echo htmlspecialchars($notification['type']); ?>"></div>
    <?php endif; ?>
</body>
</html>
