<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'] ?? null;
if (!$faculty_id) {
    header("Location: ../login.php");
    exit();
}

// Get faculty role
$stmt = $pdo->prepare("SELECT role FROM faculty WHERE faculty_id = ?");
$stmt->execute([$faculty_id]);
$faculty = $stmt->fetch();
$faculty_role = $faculty['role'] ?? '';

$message = '';
$error = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    
    if ($student_id && $course_id) {
        try {
            // Check if student is already enrolled in this course
            $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);
            
            if ($stmt->fetch()) {
                $error = "Student is already enrolled in this course.";
            } else {
                // For teachers, only allow enrollment in courses they teach
                if ($faculty_role === 'teacher') {
                    $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_id = ? AND instructor_id = ?");
                    $stmt->execute([$course_id, $faculty_id]);
                    if (!$stmt->fetch()) {
                        $error = "You can only enroll students in courses you teach.";
                    }
                }
                  if (empty($error)) {
                    // Get the course_code for the selected course_id
                    $stmt = $pdo->prepare("SELECT course_code FROM courses WHERE id = ?");
                    $stmt->execute([$course_id]);
                    $course = $stmt->fetch();
                    $course_code = $course['course_code'];
                    
                    // Enroll the student
                    $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, course_code, enrollment_date, status) VALUES (?, ?, ?, NOW(), 'active')");
                    if ($stmt->execute([$student_id, $course_id, $course_code])) {
                        $message = "Student enrolled successfully!";
                    } else {
                        $error = "Failed to enroll student.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please select both student and course.";
    }
}

// Get all students
$stmt = $pdo->prepare("SELECT student_id, first_name, last_name, email FROM students ORDER BY first_name, last_name");
$stmt->execute();
$students = $stmt->fetchAll();

// Get courses based on faculty role
if ($faculty_role === 'principal') {
    // Principal can enroll students in any course
    $stmt = $pdo->prepare("
        SELECT c.id as course_id, c.course_name, c.course_code, 
               f.first_name as instructor_first_name, f.last_name as instructor_last_name
        FROM courses c 
        LEFT JOIN faculty f ON c.instructor_id = f.faculty_id
        ORDER BY c.course_name
    ");
    $stmt->execute();
} else {
    // Teachers can only enroll students in courses they teach
    $stmt = $pdo->prepare("
        SELECT id as course_id, course_name, course_code
        FROM courses 
        WHERE instructor_id = ?
        ORDER BY course_name
    ");
    $stmt->execute([$faculty_id]);
}
$courses = $stmt->fetchAll();

// Get recent enrollments for display
$stmt = $pdo->prepare("
    SELECT e.enrollment_id, e.enrollment_date, e.status,
           s.first_name as student_first_name, s.last_name as student_last_name,
           c.course_name, c.course_code
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN courses c ON e.course_id = c.course_id
    ORDER BY e.enrollment_date DESC
    LIMIT 10
");
$stmt->execute();
$recent_enrollments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Student - Faculty Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .enrollment-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .student-info,
        .course-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 4px solid #007bff;
        }
        
        .recent-enrollments {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .enrollment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .enrollment-item:last-child {
            border-bottom: none;
        }
        
        .student-name {
            font-weight: bold;
            color: #333;
        }
        
        .course-name {
            color: #666;
        }
        
        .enrollment-date {
            font-size: 0.9em;
            color: #888;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .search-box {
            margin-bottom: 15px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Enroll Student in Course</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="enrollment-form">
            <h3>New Enrollment</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Select Student:</label>
                        <div class="search-box">
                            <input type="text" id="studentSearch" placeholder="Search students...">
                        </div>
                        <select name="student_id" id="student_id" required>
                            <option value="">Choose a student...</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($student['email']); ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                                    (<?php echo htmlspecialchars($student['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="selectedStudentInfo" class="student-info" style="display: none;">
                            <strong>Selected Student:</strong>
                            <div id="studentDetails"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_id">Select Course:</label>
                        <div class="search-box">
                            <input type="text" id="courseSearch" placeholder="Search courses...">
                        </div>
                        <select name="course_id" id="course_id" required>
                            <option value="">Choose a course...</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                                        data-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                                        <?php if (isset($course['instructor_first_name'])): ?>
                                        data-instructor="<?php echo htmlspecialchars($course['instructor_first_name'] . ' ' . $course['instructor_last_name']); ?>"
                                        <?php endif; ?>>
                                    <?php echo htmlspecialchars($course['course_name']); ?> 
                                    (<?php echo htmlspecialchars($course['course_code']); ?>)
                                    <?php if (isset($course['instructor_first_name'])): ?>
                                        - <?php echo htmlspecialchars($course['instructor_first_name'] . ' ' . $course['instructor_last_name']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="selectedCourseInfo" class="course-info" style="display: none;">
                            <strong>Selected Course:</strong>
                            <div id="courseDetails"></div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($courses)): ?>
                    <div class="alert alert-warning">
                        <?php echo $faculty_role === 'principal' ? 'No courses available. Please add courses first.' : 'You are not assigned to teach any courses yet.'; ?>
                    </div>
                <?php elseif (empty($students)): ?>
                    <div class="alert alert-warning">
                        No students available. Please add students first.
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Enroll Student</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Recent Enrollments -->
        <?php if (!empty($recent_enrollments)): ?>
        <div class="recent-enrollments">
            <h3>Recent Enrollments</h3>
            <?php foreach ($recent_enrollments as $enrollment): ?>
                <div class="enrollment-item">
                    <div>
                        <div class="student-name">
                            <?php echo htmlspecialchars($enrollment['student_first_name'] . ' ' . $enrollment['student_last_name']); ?>
                        </div>
                        <div class="course-name">
                            <?php echo htmlspecialchars($enrollment['course_name']); ?> 
                            (<?php echo htmlspecialchars($enrollment['course_code']); ?>)
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="enrollment-date">
                            <?php echo date('M j, Y', strtotime($enrollment['enrollment_date'])); ?>
                        </div>
                        <span class="status-badge status-<?php echo $enrollment['status']; ?>">
                            <?php echo ucfirst($enrollment['status']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Student search functionality
        document.getElementById('studentSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const select = document.getElementById('student_id');
            const options = select.options;
            
            for (let i = 1; i < options.length; i++) {
                const option = options[i];
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
        
        // Course search functionality
        document.getElementById('courseSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const select = document.getElementById('course_id');
            const options = select.options;
            
            for (let i = 1; i < options.length; i++) {
                const option = options[i];
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });
        
        // Show selected student info
        document.getElementById('student_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('selectedStudentInfo');
            const detailsDiv = document.getElementById('studentDetails');
            
            if (this.value) {
                const name = selectedOption.getAttribute('data-name');
                const email = selectedOption.getAttribute('data-email');
                detailsDiv.innerHTML = `<div><strong>Name:</strong> ${name}</div><div><strong>Email:</strong> ${email}</div>`;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
        
        // Show selected course info
        document.getElementById('course_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('selectedCourseInfo');
            const detailsDiv = document.getElementById('courseDetails');
            
            if (this.value) {
                const name = selectedOption.getAttribute('data-name');
                const code = selectedOption.getAttribute('data-code');
                const instructor = selectedOption.getAttribute('data-instructor');
                
                let html = `<div><strong>Course:</strong> ${name}</div><div><strong>Code:</strong> ${code}</div>`;
                if (instructor) {
                    html += `<div><strong>Instructor:</strong> ${instructor}</div>`;
                }
                
                detailsDiv.innerHTML = html;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
