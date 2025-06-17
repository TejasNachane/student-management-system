<?php
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isLoggedIn() || getUserType() !== 'admin') {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
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
    $query = "SELECT * FROM students ORDER BY full_name ASC";
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
                <a href="add_student.php" class="btn btn-primary">Add New Student</a>
            </div>

            <!-- Search Box -->
            <div class="form-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search students by name, email, or student ID...">
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
                            <th>Date of Birth</th>
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
                            <td><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></td>
                            <td>
                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">View</a>
                                <a href="?action=delete&id=<?php echo $student['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p>No students found. <a href="add_student.php">Add the first student</a></p>
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
