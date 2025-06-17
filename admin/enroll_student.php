<?php
require_once '../config/database.php';

// Check if user is logged in as faculty or admin
if (!isLoggedIn() || (getUserType() !== 'faculty' && getUserType() !== 'admin')) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Get all students and courses for dropdowns
try {
    $students_query = "SELECT student_id, full_name, email FROM students ORDER BY full_name";
    $students_stmt = $db->query($students_query);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    $courses_query = "SELECT course_code, course_name, credits FROM courses ORDER BY course_name";
    $courses_stmt = $db->query($courses_query);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle enrollment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id']);
    $course_code = trim($_POST['course_code']);

    if (empty($student_id) || empty($course_code)) {
        $error = 'Please select both student and course';
    } else {
        try {
            // Check if student is already enrolled in this course
            $check_query = "SELECT id FROM enrollments WHERE student_id = ? AND course_code = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$student_id, $course_code]);
            
            if ($check_stmt->fetch()) {
                $error = 'Student is already enrolled in this course';
            } else {
                // Enroll student in course
                $enroll_query = "INSERT INTO enrollments (student_id, course_code, enrollment_date, status) VALUES (?, ?, CURRENT_DATE, 'active')";
                $enroll_stmt = $db->prepare($enroll_query);
                
                if ($enroll_stmt->execute([$student_id, $course_code])) {
                    // Get student and course names for notification
                    $student_stmt = $db->prepare("SELECT full_name FROM students WHERE student_id = ?");
                    $student_stmt->execute([$student_id]);
                    $student_name = $student_stmt->fetch(PDO::FETCH_ASSOC)['full_name'];

                    $course_stmt = $db->prepare("SELECT course_name FROM courses WHERE course_code = ?");
                    $course_stmt->execute([$course_code]);
                    $course_name = $course_stmt->fetch(PDO::FETCH_ASSOC)['course_name'];

                    showNotification("Successfully enrolled {$student_name} in {$course_name}", 'success');
                    
                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Failed to enroll student';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get current enrollments
try {
    $enrollments_query = "
        SELECT e.*, s.full_name as student_name, s.email as student_email, 
               c.course_name, c.credits
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN courses c ON e.course_code = c.course_code
        WHERE e.status = 'active'
        ORDER BY e.enrollment_date DESC
    ";
    $enrollments_stmt = $db->query($enrollments_query);
    $enrollments = $enrollments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching enrollments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrollment - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    if (getUserType() === 'admin') {
        include 'includes/header.php';
    } else {
        include 'includes/header.php';
    }
    ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Course Enrollment</h1>
                <p>Enroll students in specific courses</p>
            </div>

            <?php if ($error): ?>
                <div class="notification error" style="position: static; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Enrollment Form -->
            <form method="POST" class="validate-form" id="enrollmentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Select Student *</label>
                        <select id="student_id" name="student_id" class="form-control" required onchange="populateStudentInfo()">
                            <option value="">Choose a student...</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                        data-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                        <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course_code">Select Course *</label>
                        <select id="course_code" name="course_code" class="form-control" required>
                            <option value="">Choose a course...</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_code']); ?>"
                                        <?php echo (isset($_POST['course_code']) && $_POST['course_code'] == $course['course_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name'] . ' (' . $course['credits'] . ' credits)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Student Info Display -->
                <div id="studentInfo" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h4>Student Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student Name</label>
                            <input type="text" id="student_name" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Student Email</label>
                            <input type="text" id="student_email" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary">Enroll Student</button>
                </div>
            </form>
        </div>

        <!-- Current Enrollments -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Current Enrollments</h2>
            </div>

            <!-- Search Box -->
            <div class="form-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search enrollments by student name or course...">
            </div>

            <?php if (!empty($enrollments)): ?>
            <div style="overflow-x: auto;">
                <table class="table" id="dataTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enrollment['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($enrollment['credits']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                            <td>
                                <span class="badge badge-success">
                                    <?php echo ucfirst($enrollment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?action=unenroll&id=<?php echo $enrollment['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to unenroll this student?')">Unenroll</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p>No enrollments found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function populateStudentInfo() {
            const select = document.getElementById('student_id');
            const selectedOption = select.options[select.selectedIndex];
            const studentInfo = document.getElementById('studentInfo');
            
            if (selectedOption.value) {
                document.getElementById('student_name').value = selectedOption.getAttribute('data-name');
                document.getElementById('student_email').value = selectedOption.getAttribute('data-email');
                studentInfo.style.display = 'block';
            } else {
                studentInfo.style.display = 'none';
            }
        }
    </script>

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
