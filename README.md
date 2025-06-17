# Student Management System

A comprehensive web-based Student Management System built with PHP, HTML, CSS, and JavaScript. The system provides role-based access for Admin, Faculty (Teachers and Principal), and Students.

## Features

### Role-Based Access Control
- **Admin**: Full system access including user management
- **Faculty**: 
  - **Principal**: View statistics, manage students/teachers/courses, but cannot mark attendance
  - **Teacher**: Add students, mark attendance, manage enrolled students
- **Student**: View personal information, courses, and attendance

### Key Functionalities
- User authentication with first-time password reset
- Student registration and management
- Faculty management (Teachers and Principal)
- Course creation and management
- Student enrollment in courses
- Attendance management (Teachers only)
- Dashboard with statistics and recent activities
- Responsive design with modern UI

### Security Features
- Password hashing
- Session management
- Input validation and sanitization
- SQL injection prevention with prepared statements
- Role-based access control

## Installation Instructions

### Prerequisites
- XAMPP (or similar) with PHP 7.4+ and MySQL
- Web browser

### Setup Steps

1. **Start XAMPP Services**
   - Start Apache and MySQL services in XAMPP Control Panel

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `student_management_system`
   - Import the SQL file: `database.sql`

3. **Configure Database Connection**
   - The database configuration is in `config/database.php`
   - Default settings:
     - Host: localhost
     - Database: student_management_system
     - Username: root
     - Password: (empty)

4. **Access the System**
   - Open your web browser
   - Navigate to: `http://localhost/project/`
   - You will be redirected to the login page

## Default Login Credentials

### Admin
- **Username:** admin
- **Password:** password

### Principal
- **Username:** principal
- **Password:** password

### Teacher
- **Username:** teacher1
- **Password:** password

**Note:** All users must change their password on first login (except admin).

## System Structure

```
project/
├── admin/              # Admin panel files
├── faculty/            # Faculty panel files
├── student/            # Student panel files
├── assets/
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── config/            # Configuration files
├── database.sql       # Database structure and sample data
├── login.php          # Login page
├── reset_password.php # Password reset page
├── logout.php         # Logout functionality
└── index.php          # Main entry point
```

## User Roles and Permissions

### Admin
- Add, edit, delete students
- Add, edit, delete faculty
- Create and manage courses
- View all statistics
- Enroll students in courses

### Principal
- View all statistics and reports
- Add and manage students
- Add new teachers
- Create and manage courses
- Delete students and manage enrollment
- **Cannot mark attendance** (Teachers only)

### Teacher
- Register new students (with password generation)
- Edit student details
- Enroll students in specific courses
- Mark attendance for their courses
- View course information

### Student
- View personal information
- View enrolled courses
- Check attendance records
- View attendance statistics

## Special Features

1. **Password Management**
   - Missing password field issue resolved - all user creation forms now include password fields
   - First-time login password reset for faculty and students
   - Secure password hashing

2. **Attendance System**
   - Only teachers can mark attendance
   - Principals have view-only access to attendance
   - Bulk attendance marking features
   - Attendance statistics and reports

3. **User-Friendly Interface**
   - Modern, responsive design
   - Real-time notifications
   - Search functionality
   - Intuitive navigation

4. **Course Management**
   - Course creation and management
   - Student enrollment system
   - Course information display

## Browser Compatibility
- Chrome (recommended)
- Firefox
- Safari
- Edge

## Support
For technical support or questions, please refer to the code documentation or contact the system administrator.

## License
This project is for educational purposes only.
