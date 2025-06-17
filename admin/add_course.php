<?php
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isLoggedIn() || getUserType() !== 'admin') {
    redirect('../login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    $credits = (int)$_POST['credits'];
    $semester = $_POST['semester'] ? (int)$_POST['semester'] : null;
    $department = trim($_POST['department']);

    // Validation
    if (empty($course_code) || empty($course_name)) {
        $error = 'Please fill in all required fields';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        try {
            // Check if course code already exists
            $check_query = "SELECT id FROM courses WHERE course_code = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$course_code]);
            
            if ($check_stmt->fetch()) {
                $error = 'Course code already exists';
            } else {
                // Insert new course
                $insert_query = "INSERT INTO courses (course_code, course_name, description, credits, semester, department) VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                
                if ($insert_stmt->execute([$course_code, $course_name, $description, $credits, $semester, $department])) {
                    $success = 'Course added successfully';
                    showNotification('Course added successfully', 'success');
                    
                    // Clear form data
                    $_POST = array();
                } else {
                    $error = 'Failed to add course';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Add New Course</h1>
                <a href="manage_courses.php" class="btn btn-primary">Back to Courses List</a>
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

            <form method="POST" class="validate-form" id="addCourseForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_code">Course Code *</label>
                        <input type="text" id="course_code" name="course_code" class="form-control" 
                               value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>" 
                               required placeholder="e.g., CS101, MATH201">
                    </div>
                    <div class="form-group">
                        <label for="credits">Credits</label>
                        <input type="number" id="credits" name="credits" class="form-control" 
                               value="<?php echo isset($_POST['credits']) ? htmlspecialchars($_POST['credits']) : '3'; ?>" 
                               min="1" max="6">
                    </div>
                </div>

                <div class="form-group">
                    <label for="course_name">Course Name *</label>
                    <input type="text" id="course_name" name="course_name" class="form-control" 
                           value="<?php echo isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : ''; ?>" 
                           required placeholder="e.g., Introduction to Computer Science">
                </div>

                <div class="form-group">
                    <label for="description">Course Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" 
                              placeholder="Brief description of the course content and objectives"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" class="form-control">
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_POST['semester']) && $_POST['semester'] == $i) ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" class="form-control" 
                               value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>" 
                               placeholder="e.g., Computer Science, Mathematics">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="manage_courses.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>

        <!-- Instructions Card -->
        <div class="card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
            <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                <h3 style="margin: 0; color: white;">Course Creation Guidelines</h3>
            </div>
            <div style="padding-top: 1rem;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <li>Use a clear, unique course code (e.g., CS101, MATH201)</li>
                    <li>Provide a descriptive course name</li>
                    <li>Include relevant course description for students</li>
                    <li>Assign appropriate credit hours (typically 1-6)</li>
                    <li>Specify the semester if applicable</li>
                </ul>
            </div>
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
