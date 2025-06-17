<header class="header">
    <div class="nav-container">
        <div class="logo">SMS - Student Portal</div>
        <nav>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_courses.php">My Courses</a></li>
                <li><a href="my_attendance.php">Attendance</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
        </nav>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>
