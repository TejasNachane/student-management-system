<?php
require_once '../config/database.php';

// Check if user is logged in as faculty
if (!isLoggedIn() || getUserType() !== 'faculty') {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();
$faculty_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';

// Handle delete action (only for principal)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && $faculty_role == 'principal') {
    try {
        $delete_query = "DELETE FROM students WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute([$_GET['id']])) {
            showNotification('Student deleted successfully', 'success');
        } else {
            showNotification('Failed to delete student', 'error');
        }
    } catch (PDOException $e) {
        showNotification('Error: ' . $e->getMessage(), 'error');
    }
    redirect('manage_students.php');
}

// Get all students
try {
    $query = "SELECT s.*, 
                     COUNT(e.id) as enrolled_courses,
                     GROUP_CONCAT(c.course_name SEPARATOR ', ') as course_names
              FROM students s
              LEFT JOIN enrollments e ON s.student_id = e.student_id AND e.status = 'active'
              LEFT JOIN courses c ON e.course_code = c.course_code
              GROUP BY s.id
              ORDER BY s.full_name ASC";
    $stmt = $db->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Manage Students</h1>
                <div style="display: flex; gap: 1rem;">
                    <a href="add_student.php" class="btn btn-primary">Add New Student</a>
                    <?php if ($faculty_role == 'principal'): ?>
                        <a href="../admin/enroll_student.php" class="btn btn-success">Enroll in Course</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search Box -->
            <div class="form-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search students by name, email, or student ID...">
            </div>

            <!-- Role Information -->
            <div class="card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white; margin-bottom: 1rem;">
                <div style="padding: 1rem;">
                    <h3 style="margin: 0; color: white;">
                        <?php echo ucfirst($faculty_role); ?> Access Level
                    </h3>
                    <p style="margin: 0.5rem 0 0 0;">
                        <?php if ($faculty_role == 'principal'): ?>
                            As a Principal, you can view, add, edit, and delete students. You can also enroll students in courses.
                        <?php else: ?>
                            As a Teacher, you can view student information, add new students, and edit existing student details.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($students)): ?>
            <div style="overflow-x: auto;">
                <table class="table" id="dataTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Enrolled Courses</th>
                            <th>Course Names</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($student['gender'] ?: 'N/A')); ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo $student['enrolled_courses']; ?> courses
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($student['course_names'] ?: 'No courses'); ?></small>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">View</a>
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <?php if ($faculty_role == 'principal'): ?>
                                        <a href="?action=delete&id=<?php echo $student['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this student? This will also remove all their enrollments and attendance records.')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Statistics Summary -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">Student Statistics</h3>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                        <div class="stat-number"><?php echo count($students); ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <div class="stat-number">
                            <?php 
                            $enrolled_count = 0;
                            foreach ($students as $student) {
                                if ($student['enrolled_courses'] > 0) $enrolled_count++;
                            }
                            echo $enrolled_count;
                            ?>
                        </div>
                        <div class="stat-label">Students with Courses</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                        <div class="stat-number">
                            <?php 
                            $not_enrolled = count($students) - $enrolled_count;
                            echo $not_enrolled;
                            ?>
                        </div>
                        <div class="stat-label">Students without Courses</div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p>No students found. <a href="add_student.php">Add the first student</a></p>
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
        .badge-info {
            background: linear-gradient(135deg, #3498db, #2980b9);
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
