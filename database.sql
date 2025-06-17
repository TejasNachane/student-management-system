-- Student Management System Database
CREATE DATABASE IF NOT EXISTS student_management_system;
USE student_management_system;

-- Admin table
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Faculty table (teachers and principal)
CREATE TABLE faculty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id VARCHAR(20) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('teacher', 'principal') NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    qualification VARCHAR(100),
    department VARCHAR(50),
    is_first_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    parent_name VARCHAR(100),
    parent_phone VARCHAR(15),
    is_first_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    semester INT,
    department VARCHAR(50),
    instructor_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES faculty(faculty_id) ON DELETE SET NULL
);

-- Student-Course enrollment table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20),
    course_id INT,
    course_code VARCHAR(20),
    enrollment_date DATE DEFAULT CURRENT_DATE,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20),
    course_id INT,
    course_code VARCHAR(20),
    faculty_id VARCHAR(20),
    attendance_date DATE,
    status ENUM('present', 'absent', 'late') DEFAULT 'absent',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE CASCADE
);

-- Insert default admin
INSERT INTO admin (username, email, password, full_name) VALUES 
('admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

-- Insert sample principal
INSERT INTO faculty (faculty_id, username, email, password, full_name, role, phone, department, qualification) VALUES 
('PRIN001', 'principal', 'principal@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'principal', '1234567890', 'Administration', 'PhD in Education');

-- Insert sample teacher
INSERT INTO faculty (faculty_id, username, email, password, full_name, role, phone, department, qualification) VALUES 
('TCH001', 'teacher1', 'teacher1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Jane Doe', 'teacher', '1234567891', 'Computer Science', 'MSc Computer Science');

-- Insert sample courses with instructor assignments
INSERT INTO courses (course_code, course_name, description, credits, semester, department, instructor_id) VALUES 
('CS101', 'Introduction to Computer Science', 'Basic concepts of computer science', 3, 1, 'Computer Science', 'TCH001'),
('MATH101', 'Calculus I', 'Differential and integral calculus', 4, 1, 'Mathematics', 'TCH001'),
('ENG101', 'English Composition', 'Academic writing and communication', 3, 1, 'English', NULL);
