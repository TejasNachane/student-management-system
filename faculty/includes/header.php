<header class="header">
    <div class="nav-container">
        <div class="logo">SMS - <?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Faculty'; ?> Panel</div>
        <nav>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher'): ?>
                    <li><a href="manage_students.php">Students</a></li>
                    <li><a href="enroll_student.php">Enroll</a></li>
                    <li><a href="mark_attendance.php">Attendance</a></li>
                    <li><a href="view_courses.php">Courses</a></li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'principal'): ?>
                    <li><a href="manage_students.php">Students</a></li>
                    <li><a href="enroll_student.php">Enroll</a></li>
                    <li><a href="manage_teachers.php">Teachers</a></li>
                    <li><a href="manage_courses.php">Courses</a></li>
                    <li><a href="view_stats.php">Statistics</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>
