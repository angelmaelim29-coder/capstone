<?php
// setup_db.php - Reset database with simple MD5 passwords

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = if0_43020735;
$pass = '';
$dbname = 'grade_portal';

echo "<h1>Setting Up Database</h1>";

// Connect to MySQL (without database)
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$conn->query("DROP DATABASE IF EXISTS $dbname");
$conn->query("CREATE DATABASE $dbname");
echo "<p>✅ Database created</p>";

// Select database
$conn->select_db($dbname);

// Create tables
$sql = "
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO roles (role_name) VALUES 
('Student'), ('Faculty'), ('Registrar'), ('Accounting'), ('Admin');

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- MD5 passwords: admin123, student123, faculty123, registrar123, accounting123
INSERT INTO users (username, email, password_hash, role_id, first_name, last_name) VALUES 
('admin', 'admin@tiftci.edu.ph', MD5('admin123'), 5, 'System', 'Admin'),
('student1', 'student@tiftci.edu.ph', MD5('student123'), 1, 'Juan', 'Dela Cruz'),
('faculty1', 'faculty@tiftci.edu.ph', MD5('faculty123'), 2, 'Maria', 'Santos'),
('registrar1', 'registrar@tiftci.edu.ph', MD5('registrar123'), 3, 'Anna', 'Reyes'),
('accounting1', 'accounting@tiftci.edu.ph', MD5('accounting123'), 4, 'Carlos', 'Gomez');

CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL
);

INSERT INTO courses (course_code, course_name) VALUES 
('BSIT', 'BS Information Technology'),
('BSCS', 'BS Computer Science');

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

if ($conn->multi_query($sql)) {
    echo "<p style='color:green;'>✅ Tables created successfully!</p>";
} else {
    echo "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
}

$conn->close();

echo "<hr>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / admin123</li>";
echo "<li><strong>Student:</strong> student1 / student123</li>";
echo "<li><strong>Faculty:</strong> faculty1 / faculty123</li>";
echo "<li><strong>Registrar:</strong> registrar1 / registrar123</li>";
echo "<li><strong>Accounting:</strong> accounting1 / accounting123</li>";
echo "</ul>";
echo "<p><a href='login.php' style='font-size:20px;'>▶ Go to Login</a></p>";
?>