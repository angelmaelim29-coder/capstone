<?php
// fresh_install.php - Complete reinstall with simple passwords

require_once 'includes/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

// Connect without database first
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and recreate database
echo "<h1>Fresh Installation</h1>";

// Drop database if exists
$conn->query("DROP DATABASE IF EXISTS $dbname");
echo "<p>✅ Dropped existing database.</p>";

// Create database
$conn->query("CREATE DATABASE $dbname");
echo "<p>✅ Created new database.</p>";

// Select database
$conn->select_db($dbname);

// Create tables and insert data
$sql = "
-- ROLES
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

INSERT INTO roles (role_name, description) VALUES 
('Student', 'Can view grades'),
('Faculty', 'Can encode grades'),
('Registrar', 'Can approve grades'),
('Accounting', 'Can manage finances'),
('Admin', 'Full access');

-- USERS with md5 passwords
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert users with md5 passwords
INSERT INTO users (username, email, password_hash, role_id, first_name, last_name) VALUES 
('admin', 'admin@tiftci.edu.ph', MD5('admin123'), 5, 'System', 'Admin'),
('student1', 'student@tiftci.edu.ph', MD5('student123'), 1, 'Juan', 'Dela Cruz'),
('faculty1', 'faculty@tiftci.edu.ph', MD5('faculty123'), 2, 'Maria', 'Santos'),
('registrar1', 'registrar@tiftci.edu.ph', MD5('registrar123'), 3, 'Anna', 'Reyes'),
('accounting1', 'accounting@tiftci.edu.ph', MD5('accounting123'), 4, 'Carlos', 'Gomez');

-- COURSES
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL
);

INSERT INTO courses (course_code, course_name) VALUES 
('BSIT', 'BS Information Technology'),
('BSCS', 'BS Computer Science');

-- STUDENTS
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    course_id INT NOT NULL,
    year_level INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

INSERT INTO students (user_id, student_number, course_id, year_level) VALUES 
((SELECT user_id FROM users WHERE username = 'student1'), '2024-001', 1, 3);

-- FINANCIAL RECORDS
CREATE TABLE financial_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    semester VARCHAR(10) NOT NULL,
    school_year VARCHAR(10) NOT NULL,
    tuition_fee DECIMAL(12,2) DEFAULT 0,
    misc_fee DECIMAL(12,2) DEFAULT 0,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    clearance_status ENUM('Cleared', 'Pending', 'Overdue') DEFAULT 'Pending',
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);

INSERT INTO financial_records (student_id, semester, school_year, tuition_fee, misc_fee, amount_paid) VALUES 
(1, '1st Semester', '2025-2026', 12000, 2500, 0);

-- GRADE ACCESS REQUESTS
CREATE TABLE grade_access_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    semester VARCHAR(10) NOT NULL,
    school_year VARCHAR(10) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);
";

// Execute SQL
if ($conn->multi_query($sql)) {
    echo "<p style='color:green;'>✅ Database setup complete!</p>";
} else {
    echo "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h2>Login Credentials:</h2>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / admin123</li>";
echo "<li><strong>Student:</strong> student1 / student123</li>";
echo "<li><strong>Faculty:</strong> faculty1 / faculty123</li>";
echo "<li><strong>Registrar:</strong> registrar1 / registrar123</li>";
echo "<li><strong>Accounting:</strong> accounting1 / accounting123</li>";
echo "</ul>";

echo "<p><a href='login.php' style='font-size:20px;'>Go to Login →</a></p>";

$conn->close();
?>