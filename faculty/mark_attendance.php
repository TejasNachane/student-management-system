<?php
require_once '../config/database.php';

// Check if user is logged in as faculty (teachers only for attendance)
if (!isLoggedIn() || getUserType() !== 'faculty' || (isset($_SESSION['role']) && $_SESSION['role'] !== 'teacher')) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'principal') {
        showNotification('Principals cannot mark attendance. Only teachers can mark attendance.', 'warning');
        redirect('dashboard.php');
    } else {
        redirect('../login.php');
    }
}

$database = new Database();
$db = $database->getConnection();
$faculty_id = isset($_SESSION['faculty_id']) ? $_SESSION['faculty_id'] : null;

// If faculty_id is not set, try to get it from the database
if (!$faculty_id && isset($_SESSION['user_id'])) {
    try {
        $faculty_query = "SELECT faculty_id FROM faculty WHERE id = ?";
        $faculty_stmt = $db->prepare($faculty_query);
        $faculty_stmt->execute([$_SESSION['user_id']]);
        $faculty_data = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
        if ($faculty_data) {
            $faculty_id = $faculty_data['faculty_id'];
            $_SESSION['faculty_id'] = $faculty_id; // Store it in session for future use
        }
    } catch (PDOException $e) {
        $error = "Error fetching faculty information: " . $e->getMessage();
    }
}

$error = '';
$success = '';

// Get available courses (only courses assigned to this teacher)
try {
    $courses_query = "SELECT id, course_code, course_name FROM courses WHERE instructor_id = ? ORDER BY course_name";
    $courses_stmt = $db->prepare($courses_query);
    $courses_stmt->execute([$faculty_id]);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching courses: " . $e->getMessage();
}

$students = [];
$selected_course_id = '';
$selected_course_code = '';
$attendance_date = date('Y-m-d');

// Handle course selection
if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
    $selected_course_id = $_POST['course_id'];
    $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
    
    // Get the course_code for the selected course_id
    try {
        $course_query = "SELECT course_code FROM courses WHERE id = ?";
        $course_stmt = $db->prepare($course_query);
        $course_stmt->execute([$selected_course_id]);
        $course_data = $course_stmt->fetch(PDO::FETCH_ASSOC);
        $selected_course_code = $course_data['course_code'];
    } catch (PDOException $e) {
        $error = "Error fetching course: " . $e->getMessage();
    }
    
    try {
        // Get students enrolled in the selected course
        $students_query = "
            SELECT s.*, e.enrollment_date,
                   a.status as attendance_status,
                   a.id as attendance_id
            FROM students s
            JOIN enrollments e ON s.student_id = e.student_id
            LEFT JOIN attendance a ON s.student_id = a.student_id 
                AND a.course_id = ? 
                AND a.attendance_date = ?
            WHERE e.course_id = ? AND e.status = 'active'
            ORDER BY s.full_name
        ";
        $students_stmt = $db->prepare($students_query);
        $students_stmt->execute([$selected_course_id, $attendance_date, $selected_course_id]);
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching students: " . $e->getMessage();
    }
}

// Handle attendance submission
if (isset($_POST['submit_attendance']) && !empty($_POST['attendance'])) {
    try {
        $db->beginTransaction();
        
        foreach ($_POST['attendance'] as $student_id => $status) {
            // Check if attendance already exists for this date
            $check_query = "SELECT id FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$student_id, $selected_course_id, $attendance_date]);
            
            if ($check_stmt->fetch()) {
                // Update existing attendance
                $update_query = "UPDATE attendance SET status = ?, faculty_id = ? WHERE student_id = ? AND course_id = ? AND attendance_date = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$status, $faculty_id, $student_id, $selected_course_id, $attendance_date]);
            } else {
                // Insert new attendance record
                $insert_query = "INSERT INTO attendance (student_id, course_id, course_code, faculty_id, attendance_date, status) VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$student_id, $selected_course_id, $selected_course_code, $faculty_id, $attendance_date, $status]);
            }
        }
        
        $db->commit();
        showNotification('Attendance marked successfully for ' . count($_POST['attendance']) . ' students', 'success');
        
        // Refresh the student list to show updated attendance
        $students_stmt = $db->prepare($students_query);
        $students_stmt->execute([$selected_course_id, $attendance_date, $selected_course_id]);
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $db->rollback();
        $error = "Error marking attendance: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Mark Attendance</h1>
                <p>Select course and date to mark attendance</p>
            </div>            <?php if ($error): ?>
                <div class="notification error" style="position: static; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($courses)): ?>
                <div class="alert alert-warning">
                    <h3>No Courses Assigned</h3>
                    <p>You are not currently assigned to teach any courses. Please contact the principal to get course assignments.</p>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php else: ?>

            <!-- Course Selection Form -->
            <form method="POST" class="validate-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_id">Select Course *</label>
                        <select id="course_id" name="course_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">Choose a course...</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['id']); ?>" 
                                        <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_date">Date *</label>
                        <input type="date" id="attendance_date" name="attendance_date" class="form-control" 
                               value="<?php echo htmlspecialchars($attendance_date); ?>" required onchange="this.form.submit()">
                    </div>
                </div>
            </form>            <?php if (!empty($students)): ?>
            <!-- Attendance Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Students Enrolled in <?php echo htmlspecialchars($selected_course_code); ?></h2>
                    <p>Date: <?php echo date('F d, Y', strtotime($attendance_date)); ?></p>
                </div>
                
                <form method="POST" id="attendanceForm">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date); ?>">

                    <!-- Bulk Actions -->
                    <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <label>
                            <input type="checkbox" id="select-all"> Select All
                        </label>
                        <div style="margin-top: 0.5rem;">
                            <button type="button" class="btn btn-success btn-sm" onclick="markSelected('present')">Mark Selected Present</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="markSelected('absent')">Mark Selected Absent</button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="markSelected('late')">Mark Selected Late</button>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all-header"></th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Attendance Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="student-checkbox" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <label style="display: flex; align-items: center; gap: 0.3rem;">
                                                <input type="radio" name="attendance[<?php echo htmlspecialchars($student['student_id']); ?>]" 
                                                       value="present" 
                                                       <?php echo ($student['attendance_status'] == 'present') ? 'checked' : ''; ?>>
                                                <span class="badge badge-success">Present</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.3rem;">
                                                <input type="radio" name="attendance[<?php echo htmlspecialchars($student['student_id']); ?>]" 
                                                       value="absent" 
                                                       <?php echo ($student['attendance_status'] == 'absent' || !$student['attendance_status']) ? 'checked' : ''; ?>>
                                                <span class="badge badge-danger">Absent</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.3rem;">
                                                <input type="radio" name="attendance[<?php echo htmlspecialchars($student['student_id']); ?>]" 
                                                       value="late" 
                                                       <?php echo ($student['attendance_status'] == 'late') ? 'checked' : ''; ?>>
                                                <span class="badge badge-warning">Late</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" name="submit_attendance" class="btn btn-primary">Save Attendance</button>
                    </div>
                </form>
            </div>
            <?php elseif (!empty($selected_course)): ?>
            <div style="text-align: center; padding: 2rem;">
                <p>No students enrolled in this course.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Toggle all checkboxes
        function toggleAllCheckboxes() {
            const masterCheckbox = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = masterCheckbox.checked;
            });
        }

        // Mark selected students with specified status
        function markSelected(status) {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            
            checkboxes.forEach(checkbox => {
                const studentId = checkbox.value;
                const radioButton = document.querySelector(`input[name="attendance[${studentId}]"][value="${status}"]`);
                if (radioButton) {
                    radioButton.checked = true;
                }
            });
            
            showNotification(`Marked ${checkboxes.length} students as ${status}`, 'info');
        }

        // Initialize checkbox events
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const selectAllHeader = document.getElementById('select-all-header');
            
            if (selectAll) {
                selectAll.addEventListener('change', toggleAllCheckboxes);
            }
            
            if (selectAllHeader) {
                selectAllHeader.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.student-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }        });
    </script>

    <?php endif; ?>

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
        .badge-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        .badge-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
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
