<?php
require_once '../config/database.php';

// Check if user is logged in as principal
if (!isLoggedIn() || getUserType() !== 'faculty' || (isset($_SESSION['role']) && $_SESSION['role'] !== 'principal')) {
    showNotification('Only principals can view detailed statistics', 'error');
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

// Get comprehensive statistics
try {
    // Basic counts
    $student_count_query = "SELECT COUNT(*) as count FROM students";
    $student_count = $db->query($student_count_query)->fetch(PDO::FETCH_ASSOC)['count'];

    $teacher_count_query = "SELECT COUNT(*) as count FROM faculty WHERE role = 'teacher'";
    $teacher_count = $db->query($teacher_count_query)->fetch(PDO::FETCH_ASSOC)['count'];

    $course_count_query = "SELECT COUNT(*) as count FROM courses";
    $course_count = $db->query($course_count_query)->fetch(PDO::FETCH_ASSOC)['count'];

    $enrollment_count_query = "SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'";
    $enrollment_count = $db->query($enrollment_count_query)->fetch(PDO::FETCH_ASSOC)['count'];

    // Gender distribution
    $gender_stats_query = "
        SELECT gender, COUNT(*) as count 
        FROM students 
        WHERE gender IS NOT NULL AND gender != ''
        GROUP BY gender
    ";
    $gender_stats = $db->query($gender_stats_query)->fetchAll(PDO::FETCH_ASSOC);

    // Department-wise statistics
    $dept_stats_query = "
        SELECT 
            f.department,
            COUNT(DISTINCT f.id) as teacher_count,
            COUNT(DISTINCT c.id) as course_count,
            COUNT(DISTINCT e.student_id) as enrolled_students
        FROM faculty f
        LEFT JOIN courses c ON f.department = c.department
        LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.status = 'active'
        WHERE f.role = 'teacher' AND f.department IS NOT NULL AND f.department != ''
        GROUP BY f.department
        ORDER BY teacher_count DESC
    ";
    $dept_stats = $db->query($dept_stats_query)->fetchAll(PDO::FETCH_ASSOC);

    // Course enrollment statistics
    $course_enrollment_query = "
        SELECT 
            c.course_code,
            c.course_name,
            c.department,
            c.credits,
            COUNT(e.id) as enrolled_count,
            GROUP_CONCAT(DISTINCT s.full_name ORDER BY s.full_name SEPARATOR ', ') as student_names
        FROM courses c
        LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.status = 'active'
        LEFT JOIN students s ON e.student_id = s.student_id
        GROUP BY c.id
        ORDER BY enrolled_count DESC, c.course_name
    ";
    $course_enrollments = $db->query($course_enrollment_query)->fetchAll(PDO::FETCH_ASSOC);

    // Attendance statistics
    $attendance_stats_query = "
        SELECT 
            c.course_name,
            COUNT(DISTINCT a.student_id) as students_tracked,
            COUNT(a.id) as total_records,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
            ROUND(AVG(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) * 100, 1) as attendance_rate
        FROM courses c
        LEFT JOIN attendance a ON c.course_code = a.course_code
        WHERE a.id IS NOT NULL
        GROUP BY c.id, c.course_name
        HAVING total_records > 0
        ORDER BY attendance_rate DESC
    ";
    $attendance_stats = $db->query($attendance_stats_query)->fetchAll(PDO::FETCH_ASSOC);

    // Recent registrations
    $recent_students_query = "
        SELECT full_name, student_id, email, created_at
        FROM students 
        ORDER BY created_at DESC 
        LIMIT 10
    ";
    $recent_students = $db->query($recent_students_query)->fetchAll(PDO::FETCH_ASSOC);

    // Students without courses
    $unassigned_students_query = "
        SELECT s.student_id, s.full_name, s.email
        FROM students s
        LEFT JOIN enrollments e ON s.student_id = e.student_id AND e.status = 'active'
        WHERE e.id IS NULL
        ORDER BY s.full_name
    ";
    $unassigned_students = $db->query($unassigned_students_query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">System Statistics & Reports</h1>
                <p>Comprehensive overview of the student management system</p>
            </div>

            <!-- Main Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                    <div class="stat-number"><?php echo $student_count; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <div class="stat-number"><?php echo $teacher_count; ?></div>
                    <div class="stat-label">Teachers</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <div class="stat-number"><?php echo $course_count; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <div class="stat-number"><?php echo $enrollment_count; ?></div>
                    <div class="stat-label">Active Enrollments</div>
                </div>
            </div>

            <!-- Gender Distribution -->
            <?php if (!empty($gender_stats)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Student Gender Distribution</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <?php foreach ($gender_stats as $gender): ?>
                    <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <div class="stat-number"><?php echo $gender['count']; ?></div>
                        <div class="stat-label"><?php echo ucfirst($gender['gender']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Department Statistics -->
            <?php if (!empty($dept_stats)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Department Statistics</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Teachers</th>
                                <th>Courses</th>
                                <th>Enrolled Students</th>
                                <th>Avg Students per Course</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dept_stats as $dept): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dept['department']); ?></strong></td>
                                <td><?php echo $dept['teacher_count']; ?></td>
                                <td><?php echo $dept['course_count']; ?></td>
                                <td><?php echo $dept['enrolled_students']; ?></td>
                                <td>
                                    <?php 
                                    $avg = $dept['course_count'] > 0 ? round($dept['enrolled_students'] / $dept['course_count'], 1) : 0;
                                    echo $avg;
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Course Enrollment Overview -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Course Enrollment Overview</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Enrolled Students</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_enrollments as $course): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['department'] ?: 'N/A'); ?></td>
                                <td><?php echo $course['credits']; ?></td>
                                <td><?php echo $course['enrolled_count']; ?></td>
                                <td>
                                    <span class="badge <?php echo $course['enrolled_count'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $course['enrolled_count'] > 0 ? 'Active' : 'No Students'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Attendance Statistics -->
            <?php if (!empty($attendance_stats)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Course Attendance Statistics</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Students Tracked</th>
                                <th>Total Records</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_stats as $stats): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stats['course_name']); ?></td>
                                <td><?php echo $stats['students_tracked']; ?></td>
                                <td><?php echo $stats['total_records']; ?></td>
                                <td><?php echo $stats['present_count']; ?></td>
                                <td><?php echo $stats['absent_count']; ?></td>
                                <td><?php echo $stats['late_count']; ?></td>
                                <td>
                                    <span class="badge <?php echo $stats['attendance_rate'] >= 75 ? 'badge-success' : ($stats['attendance_rate'] >= 60 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $stats['attendance_rate']; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Student Registrations -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Student Registrations</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Students Without Courses -->
            <?php if (!empty($unassigned_students)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Students Not Enrolled in Any Course</h2>
                    <span class="badge badge-warning"><?php echo count($unassigned_students); ?> students</span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unassigned_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td>
                                    <a href="../admin/enroll_student.php" class="btn btn-primary btn-sm">Enroll in Course</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="add_student.php" class="btn btn-primary">Add New Student</a>
                    <a href="add_teacher.php" class="btn btn-success">Add New Teacher</a>
                    <a href="add_course.php" class="btn btn-warning">Add New Course</a>
                    <a href="../admin/enroll_student.php" class="btn btn-info">Enroll Students</a>
                    <a href="manage_students.php" class="btn btn-primary">Manage Students</a>
                    <a href="manage_courses.php" class="btn btn-success">Manage Courses</a>
                </div>
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
        .badge-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        .btn-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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
