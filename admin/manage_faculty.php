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
        $delete_query = "DELETE FROM faculty WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute([$_GET['id']])) {
            showNotification('Faculty member deleted successfully', 'success');
        } else {
            showNotification('Failed to delete faculty member', 'error');
        }
    } catch (PDOException $e) {
        showNotification('Error: ' . $e->getMessage(), 'error');
    }
    redirect('manage_faculty.php');
}

// Get all faculty
try {
    $query = "SELECT * FROM faculty ORDER BY role DESC, full_name ASC";
    $stmt = $db->query($query);
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - Student Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Manage Faculty</h1>
                <a href="add_faculty.php" class="btn btn-primary">Add New Faculty</a>
            </div>

            <!-- Search Box -->
            <div class="form-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search faculty by name, email, or faculty ID...">
            </div>

            <?php if (!empty($faculty)): ?>
            <div style="overflow-x: auto;">
                <table class="table" id="dataTable">
                    <thead>
                        <tr>
                            <th>Faculty ID</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Qualification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['faculty_id']); ?></td>
                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $member['role'] == 'principal' ? 'badge-primary' : 'badge-success'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($member['role'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($member['department'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($member['qualification'] ?: 'N/A'); ?></td>
                            <td>
                                <a href="edit_faculty.php?id=<?php echo $member['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="view_faculty.php?id=<?php echo $member['id']; ?>" class="btn btn-info btn-sm">View</a>
                                <a href="?action=delete&id=<?php echo $member['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this faculty member?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <p>No faculty members found. <a href="add_faculty.php">Add the first faculty member</a></p>
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
        .badge-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
