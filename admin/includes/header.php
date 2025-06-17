<header class="header">
    <div class="nav-container">
        <div class="logo">SMS - Admin Panel</div>
        <nav>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_students.php">Students</a></li>
                <li><a href="manage_faculty.php">Faculty</a></li>
                <li><a href="manage_courses.php">Courses</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
        </nav>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>
