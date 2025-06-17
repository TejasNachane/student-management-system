<?php
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isLoggedIn() || getUserType() !== 'admin') {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
try {
    // Count students
    $stmt = $db->query("SELECT COUNT(*) as count FROM students");
    $student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count faculty
    $stmt = $db->query("SELECT COUNT(*) as count FROM faculty");
    $faculty_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count courses
    $stmt = $db->query("SELECT COUNT(*) as count FROM courses");
    $course_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count enrollments
    $stmt = $db->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
    $enrollment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Recent activities (latest enrollments)
    $stmt = $db->query("
        SELECT e.*, s.full_name as student_name, c.course_name 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.student_id 
        JOIN courses c ON e.course_code = c.course_code 
        ORDER BY e.enrollment_date DESC 
        LIMIT 5
    ");
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
    <title>Admin Dashboard - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $student_count; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $faculty_count; ?></div>
                    <div class="stat-label">Total Faculty</div>
                </div>
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
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="add_faculty.php" class="btn btn-primary">Add New Faculty</a>
                    <a href="add_student.php" class="btn btn-success">Add New Student</a>
                    <a href="add_course.php" class="btn btn-warning">Add New Course</a>
                    <a href="manage_users.php" class="btn btn-info">Manage Users</a>
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
                                <span class="badge <?php echo $enrollment['status'] == 'active' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($enrollment['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    
    <?php
    $notification = getNotification();
    if ($notification):
    ?>
    <div data-notification data-message="<?php echo htmlspecialchars($notification['message']); ?>" data-type="<?php echo htmlspecialchars($notification['type']); ?>"></div>
    <?php endif; ?>
</body>
</html>
