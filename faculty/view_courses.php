<?php
require_once '../config/database.php';

// Check if user is logged in as faculty
if (!isLoggedIn() || getUserType() !== 'faculty') {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();
$faculty_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';

// Get all courses with enrollment statistics
try {
    $courses_query = "
        SELECT c.*, 
               COUNT(e.id) as enrolled_students,
               GROUP_CONCAT(DISTINCT s.full_name ORDER BY s.full_name SEPARATOR ', ') as student_names
        FROM courses c
        LEFT JOIN enrollments e ON c.course_code = e.course_code AND e.status = 'active'
        LEFT JOIN students s ON e.student_id = s.student_id
        GROUP BY c.id
        ORDER BY c.course_name ASC
    ";
    $courses_stmt = $db->query($courses_query);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total statistics
    $total_courses = count($courses);
    $total_enrollments = 0;
    foreach ($courses as $course) {
        $total_enrollments += $course['enrolled_students'];
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get courses with attendance data (for teachers)
$attendance_data = [];
if ($faculty_role == 'teacher') {
    try {
        $attendance_query = "
            SELECT c.course_code, c.course_name,
                   COUNT(DISTINCT a.student_id) as students_with_attendance,
                   COUNT(a.id) as total_attendance_records,
                   SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                   SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                   SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
            FROM courses c
            LEFT JOIN attendance a ON c.course_code = a.course_code
            GROUP BY c.course_code, c.course_name
            ORDER BY c.course_name
        ";
        $attendance_stmt = $db->query($attendance_query);
        $attendance_data = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error silently for attendance data
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courses - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Course Information</h1>
                <div style="display: flex; gap: 1rem;">
                    <?php if ($faculty_role == 'principal'): ?>
                        <a href="add_course.php" class="btn btn-primary">Add New Course</a>
                        <a href="../admin/enroll_student.php" class="btn btn-success">Enroll Students</a>
                    <?php else: ?>
                        <a href="mark_attendance.php" class="btn btn-primary">Mark Attendance</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_enrollments; ?></div>
                    <div class="stat-label">Total Enrollments</div>
                </div>
                <?php if ($faculty_role == 'teacher'): ?>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $active_courses = 0;
                        foreach ($courses as $course) {
                            if ($course['enrolled_students'] > 0) $active_courses++;
                        }
                        echo $active_courses;
                        ?>
                    </div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Search Box -->
            <div class="form-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search courses by name, code, or department...">
            </div>

            <!-- Courses List -->
            <?php if (!empty($courses)): ?>
            <div style="overflow-x: auto;">
                <table class="table" id="dataTable">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Semester</th>
                            <th>Department</th>
                            <th>Enrolled Students</th>
                            <th>Student Names</th>
                            <?php if ($faculty_role == 'teacher'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['credits']); ?></td>
                            <td><?php echo htmlspecialchars($course['semester'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($course['department'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="badge <?php echo $course['enrolled_students'] > 0 ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $course['enrolled_students']; ?> students
                                </span>
                            </td>
                            <td>
                                <small title="<?php echo htmlspecialchars($course['student_names'] ?: 'No students enrolled'); ?>">
                                    <?php 
                                    $names = $course['student_names'];
                                    if (strlen($names) > 50) {
                                        echo htmlspecialchars(substr($names, 0, 50)) . '...';
                                    } else {
                                        echo htmlspecialchars($names ?: 'No students');
                                    }
                                    ?>
                                </small>
                            </td>
                            <?php if ($faculty_role == 'teacher'): ?>
                            <td>
                                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                    <?php if ($course['enrolled_students'] > 0): ?>
                                        <a href="mark_attendance.php?course=<?php echo urlencode($course['course_code']); ?>" 
                                           class="btn btn-primary btn-sm">Mark Attendance</a>
                                        <a href="view_attendance.php?course=<?php echo urlencode($course['course_code']); ?>" 
                                           class="btn btn-info btn-sm">View Attendance</a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Students</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Course Details Cards -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">Course Details</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <?php foreach ($courses as $course): ?>
                        <?php if ($course['enrolled_students'] > 0): ?>
                        <div class="card" style="border: 1px solid #e0e0e0;">
                            <div class="card-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                <h4 style="margin: 0; color: white;"><?php echo htmlspecialchars($course['course_code']); ?></h4>
                                <p style="margin: 0; opacity: 0.9;"><?php echo htmlspecialchars($course['course_name']); ?></p>
                            </div>
                            <div style="padding: 1rem;">
                                <p><strong>Credits:</strong> <?php echo htmlspecialchars($course['credits']); ?></p>
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($course['department'] ?: 'N/A'); ?></p>
                                <p><strong>Enrolled Students:</strong> <?php echo $course['enrolled_students']; ?></p>
                                <?php if ($course['description']): ?>
                                    <p><strong>Description:</strong></p>
                                    <p style="font-size: 0.9rem; color: #666;"><?php echo htmlspecialchars($course['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p>No courses found in the system.</p>
                <?php if ($faculty_role == 'principal'): ?>
                    <a href="add_course.php" class="btn btn-primary">Add the first course</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Teacher-specific Attendance Overview -->
            <?php if ($faculty_role == 'teacher' && !empty($attendance_data)): ?>
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">Attendance Overview</h3>
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
                            <?php foreach ($attendance_data as $data): ?>
                                <?php if ($data['total_attendance_records'] > 0): ?>
                                <?php 
                                $total = $data['total_attendance_records'];
                                $present_late = $data['present_count'] + $data['late_count'];
                                $rate = $total > 0 ? round(($present_late / $total) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['course_name']); ?></td>
                                    <td><?php echo $data['students_with_attendance']; ?></td>
                                    <td><?php echo $data['total_attendance_records']; ?></td>
                                    <td><?php echo $data['present_count']; ?></td>
                                    <td><?php echo $data['absent_count']; ?></td>
                                    <td><?php echo $data['late_count']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $rate >= 75 ? 'badge-success' : ($rate >= 60 ? 'badge-warning' : 'badge-danger'); ?>">
                                            <?php echo $rate; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
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
        .badge-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
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
