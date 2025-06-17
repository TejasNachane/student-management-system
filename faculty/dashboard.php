<?php
require_once '../config/database.php';

// Check if user is logged in as faculty
if (!isLoggedIn() || getUserType() !== 'faculty') {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();
$faculty_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher'; // Default to teacher if role not set

// Get statistics
try {
    if ($faculty_role == 'principal') {
        // Principal can see all stats
        $stmt = $db->query("SELECT COUNT(*) as count FROM students");
        $student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM faculty WHERE role = 'teacher'");
        $teacher_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM courses");
        $course_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
        $enrollment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } else {
        // Teachers see limited stats
        $stmt = $db->query("SELECT COUNT(*) as count FROM students");
        $student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM courses");
        $course_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
        $enrollment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $teacher_count = null;
    }

    // Recent activities based on role
    if ($faculty_role == 'principal') {
        $stmt = $db->query("
            SELECT e.*, s.full_name as student_name, c.course_name 
            FROM enrollments e 
            JOIN students s ON e.student_id = s.student_id 
            JOIN courses c ON e.course_code = c.course_code 
            ORDER BY e.enrollment_date DESC 
            LIMIT 5
        ");
    } else {
        $stmt = $db->query("
            SELECT e.*, s.full_name as student_name, c.course_name 
            FROM enrollments e 
            JOIN students s ON e.student_id = s.student_id 
            JOIN courses c ON e.course_code = c.course_code 
            ORDER BY e.enrollment_date DESC 
            LIMIT 5
        ");
    }
    $recent_enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title"><?php echo ucfirst($faculty_role); ?> Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $student_count; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <?php if ($faculty_role == 'principal'): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $teacher_count; ?></div>
                    <div class="stat-label">Teachers</div>
                </div>
                <?php endif; ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $course_count; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $enrollment_count; ?></div>
                    <div class="stat-label">Active Enrollments</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <?php if ($faculty_role == 'teacher'): ?>
                        <a href="add_student.php" class="btn btn-primary">Add New Student</a>
                        <a href="manage_students.php" class="btn btn-success">Manage Students</a>
                        <a href="enroll_student.php" class="btn btn-secondary">Enroll Student</a>
                        <a href="mark_attendance.php" class="btn btn-warning">Mark Attendance</a>
                        <a href="view_courses.php" class="btn btn-info">View Courses</a>                    <?php else: // Principal ?>
                        <a href="add_student.php" class="btn btn-primary">Add New Student</a>
                        <a href="add_teacher.php" class="btn btn-success">Add New Teacher</a>
                        <a href="add_course.php" class="btn btn-warning">Add New Course</a>
                        <a href="enroll_student.php" class="btn btn-secondary">Enroll Student</a>
                        <a href="view_stats.php" class="btn btn-info">View Statistics</a>
                        <a href="manage_students.php" class="btn btn-primary">Manage Students</a>
                        <a href="manage_courses.php" class="btn btn-success">Manage Courses</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activities -->
            <?php if (!empty($recent_enrollments)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Enrollments</h2>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_enrollments as $enrollment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                            <td>
                                <span class="badge badge-success">
                                    <?php echo ucfirst($enrollment['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($faculty_role == 'principal'): ?>
            <!-- Principal Notice -->
            <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                    <h3 style="margin: 0; color: white;">Principal Access</h3>
                </div>
                <div style="padding-top: 1rem;">
                    <p>As a Principal, you have view-only access to attendance. Teachers are responsible for marking attendance for their respective courses.</p>
                    <p><strong>Your privileges include:</strong></p>
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <li>View all statistics and reports</li>
                        <li>Add and manage students</li>
                        <li>Add and manage teachers</li>
                        <li>Create and manage courses</li>
                        <li>Delete students and manage enrollment</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
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
    </style>

    <?php
    $notification = getNotification();
    if ($notification):
    ?>
    <div data-notification data-message="<?php echo htmlspecialchars($notification['message']); ?>" data-type="<?php echo htmlspecialchars($notification['type']); ?>"></div>
    <?php endif; ?>
</body>
</html>
