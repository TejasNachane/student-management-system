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

// Handle course updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_course'])) {
        $course_id = $_POST['course_id'];
        $course_name = trim($_POST['course_name']);
        $course_code = trim($_POST['course_code']);
        $credits = (int)$_POST['credits'];
        $description = trim($_POST['description']);
        
        if (!empty($course_name) && !empty($course_code)) {
            try {
                $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_code = ?, credits = ?, description = ? WHERE course_id = ?");
                if ($stmt->execute([$course_name, $course_code, $credits, $description, $course_id])) {
                    $message = "Course updated successfully!";
                } else {
                    $error = "Failed to update course.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Course code already exists!";
                } else {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    }
}

// Get courses based on role
if ($faculty_role === 'principal') {
    // Principal can see all courses
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT e.student_id) as enrolled_students,
               f.first_name as instructor_first_name,
               f.last_name as instructor_last_name
        FROM courses c 
        LEFT JOIN enrollments e ON c.course_id = e.course_id 
        LEFT JOIN faculty f ON c.instructor_id = f.faculty_id
        GROUP BY c.course_id 
        ORDER BY c.course_name
    ");
    $stmt->execute();
} else {
    // Regular teachers can only see their assigned courses
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT e.student_id) as enrolled_students
        FROM courses c 
        LEFT JOIN enrollments e ON c.course_id = e.course_id 
        WHERE c.instructor_id = ?
        GROUP BY c.course_id 
        ORDER BY c.course_name
    ");
    $stmt->execute([$faculty_id]);
}
$courses = $stmt->fetchAll();

// Get total statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses");
$stmt->execute();
$total_courses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_enrollments FROM enrollments");
$stmt->execute();
$total_enrollments = $stmt->fetchColumn();

// Get search filter
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    if ($faculty_role === 'principal') {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT e.student_id) as enrolled_students,
                   f.first_name as instructor_first_name,
                   f.last_name as instructor_last_name
            FROM courses c 
            LEFT JOIN enrollments e ON c.course_id = e.course_id 
            LEFT JOIN faculty f ON c.instructor_id = f.faculty_id
            WHERE c.course_name LIKE ? OR c.course_code LIKE ? OR c.description LIKE ?
            GROUP BY c.course_id 
            ORDER BY c.course_name
        ");
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT e.student_id) as enrolled_students
            FROM courses c 
            LEFT JOIN enrollments e ON c.course_id = e.course_id 
            WHERE c.instructor_id = ? AND (c.course_name LIKE ? OR c.course_code LIKE ? OR c.description LIKE ?)
            GROUP BY c.course_id 
            ORDER BY c.course_name
        ");
        $searchTerm = "%$search%";
        $stmt->execute([$faculty_id, $searchTerm, $searchTerm, $searchTerm]);
    }
    $courses = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Faculty Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .course-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .course-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .course-code {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
        }
        
        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-weight: bold;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .course-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-edit {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .btn-edit:hover {
            background: #218838;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Manage Courses</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($courses); ?></div>
                <div>My Courses</div>
            </div>
            <?php if ($faculty_role === 'principal'): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div>Total Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_enrollments; ?></div>
                <div>Total Enrollments</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Search Box -->
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search courses..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_courses.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Add Course Button (Principal only) -->
        <?php if ($faculty_role === 'principal'): ?>
        <div style="margin-bottom: 20px;">
            <a href="add_course.php" class="btn btn-primary">Add New Course</a>
        </div>
        <?php endif; ?>
        
        <!-- Courses List -->
        <?php if (empty($courses)): ?>
            <div class="alert alert-info">
                <?php echo $faculty_role === 'principal' ? 'No courses found.' : 'You are not assigned to any courses yet.'; ?>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                        <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                    </div>
                    
                    <div class="course-details">
                        <div class="detail-item">
                            <div class="detail-label">Credits</div>
                            <div class="detail-value"><?php echo $course['credits']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Enrolled Students</div>
                            <div class="detail-value"><?php echo $course['enrolled_students']; ?></div>
                        </div>
                        <?php if ($faculty_role === 'principal' && isset($course['instructor_first_name'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">Instructor</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($course['instructor_first_name'] . ' ' . $course['instructor_last_name']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <div class="detail-label">Description</div>
                            <div class="detail-value"><?php echo htmlspecialchars($course['description'] ?: 'No description available'); ?></div>
                        </div>
                    </div>
                    
                    <div class="course-actions">
                        <button class="btn-edit" onclick="editCourse(<?php echo $course['course_id']; ?>, 
                                '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', 
                                '<?php echo htmlspecialchars($course['course_code'], ENT_QUOTES); ?>', 
                                <?php echo $course['credits']; ?>, 
                                '<?php echo htmlspecialchars($course['description'] ?: '', ENT_QUOTES); ?>')">
                            Edit Course
                        </button>
                        <a href="mark_attendance.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-secondary">
                            Mark Attendance
                        </a>
                        <a href="view_courses.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-info">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Edit Course Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Course</h3>
            <form method="POST">
                <input type="hidden" id="edit_course_id" name="course_id">
                
                <div class="form-group">
                    <label for="edit_course_name">Course Name:</label>
                    <input type="text" id="edit_course_name" name="course_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_course_code">Course Code:</label>
                    <input type="text" id="edit_course_code" name="course_code" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_credits">Credits:</label>
                    <input type="number" id="edit_credits" name="credits" min="1" max="10" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_course" class="btn btn-primary">Update Course</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editCourse(courseId, courseName, courseCode, credits, description) {
            document.getElementById('edit_course_id').value = courseId;
            document.getElementById('edit_course_name').value = courseName;
            document.getElementById('edit_course_code').value = courseCode;
            document.getElementById('edit_credits').value = credits;
            document.getElementById('edit_description').value = description;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
