<?php
require_once '../config/database.php';

// Check if user is logged in as student
if (!isLoggedIn() || getUserType() !== 'student') {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();
$student_id = $_SESSION['student_id'];

// Get student information
try {
    $student_query = "SELECT * FROM students WHERE student_id = ?";
    $student_stmt = $db->prepare($student_query);
    $student_stmt->execute([$student_id]);
    $student_info = $student_stmt->fetch(PDO::FETCH_ASSOC);

    // Get enrolled courses
    $courses_query = "
        SELECT c.*, e.enrollment_date, e.status as enrollment_status
        FROM courses c
        JOIN enrollments e ON c.course_code = e.course_code
        WHERE e.student_id = ?
        ORDER BY e.enrollment_date DESC
    ";
    $courses_stmt = $db->prepare($courses_query);
    $courses_stmt->execute([$student_id]);
    $enrolled_courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent attendance
    $attendance_query = "
        SELECT a.*, c.course_name, c.course_code
        FROM attendance a
        JOIN courses c ON a.course_code = c.course_code
        WHERE a.student_id = ?
        ORDER BY a.attendance_date DESC
        LIMIT 10
    ";
    $attendance_stmt = $db->prepare($attendance_query);
    $attendance_stmt->execute([$student_id]);
    $recent_attendance = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate attendance statistics
    $stats_query = "
        SELECT 
            c.course_code,
            c.course_name,
            COUNT(a.id) as total_classes,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM courses c
        JOIN enrollments e ON c.course_code = e.course_code
        LEFT JOIN attendance a ON c.course_code = a.course_code AND a.student_id = e.student_id
        WHERE e.student_id = ? AND e.status = 'active'
        GROUP BY c.course_code, c.course_name
    ";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$student_id]);
    $attendance_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Student Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($student_info['full_name']); ?></p>
            </div>

            <!-- Student Information Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Information</h2>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Student ID</label>
                        <div class="form-control" style="background: #f8f9fa;"><?php echo htmlspecialchars($student_info['student_id']); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <div class="form-control" style="background: #f8f9fa;"><?php echo htmlspecialchars($student_info['email']); ?></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <div class="form-control" style="background: #f8f9fa;"><?php echo htmlspecialchars($student_info['phone'] ?: 'Not provided'); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <div class="form-control" style="background: #f8f9fa;">
                            <?php echo $student_info['date_of_birth'] ? date('F d, Y', strtotime($student_info['date_of_birth'])) : 'Not provided'; ?>
                        </div>
                    </div>
                </div>
                <?php if ($student_info['parent_name']): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent/Guardian</label>
                        <div class="form-control" style="background: #f8f9fa;"><?php echo htmlspecialchars($student_info['parent_name']); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <div class="form-control" style="background: #f8f9fa;"><?php echo htmlspecialchars($student_info['parent_phone'] ?: 'Not provided'); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Enrolled Courses -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">My Courses</h2>
                </div>
                <?php if (!empty($enrolled_courses)): ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Department</th>
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
                                <td><?php echo htmlspecialchars($course['semester'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($course['department'] ?: 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo ucfirst($course['enrollment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <p>You are not enrolled in any courses yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Attendance Statistics -->
            <?php if (!empty($attendance_stats)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Attendance Overview</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Total Classes</th>
                                <th>Present</th>
                                <th>Late</th>
                                <th>Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_stats as $stat): ?>
                            <?php 
                            $total = $stat['total_classes'];
                            $present = $stat['present_count'] + $stat['late_count']; // Count late as present for percentage
                            $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['course_name']); ?></td>
                                <td><?php echo $stat['total_classes']; ?></td>
                                <td><?php echo $stat['present_count']; ?></td>
                                <td><?php echo $stat['late_count']; ?></td>
                                <td><?php echo $stat['absent_count']; ?></td>
                                <td>
                                    <span class="badge <?php echo $percentage >= 75 ? 'badge-success' : ($percentage >= 60 ? 'badge-warning' : 'badge-danger'); ?>">
                                        <?php echo $percentage; ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Attendance -->
            <?php if (!empty($recent_attendance)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Attendance</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attendance as $attendance): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($attendance['attendance_date'])); ?></td>
                                <td><?php echo htmlspecialchars($attendance['course_name']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $attendance['status'] == 'present' ? 'badge-success' : 
                                             ($attendance['status'] == 'late' ? 'badge-warning' : 'badge-danger'); 
                                    ?>">
                                        <?php echo ucfirst($attendance['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        .badge-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        .badge-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
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
