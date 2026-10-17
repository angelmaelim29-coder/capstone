<?php
// api/admin/add_user.php

session_start();

$host = 'localhost';
$user =  'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?error=Invalid method');
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role_id = intval($_POST['role_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$student_number = trim($_POST['student_number'] ?? '');
$course_id = intval($_POST['course_id'] ?? 0);
$year_level = intval($_POST['year_level'] ?? 1);
$department = trim($_POST['department'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');

if (empty($username) || empty($email) || empty($password) || empty($role_id) || empty($first_name) || empty($last_name)) {
    header('Location: ../../index.php?error=All fields are required');
    exit();
}

$check = $conn->query("SELECT user_id FROM users WHERE username = '$username' OR email = '$email'");
if ($check && $check->num_rows > 0) {
    header('Location: ../../index.php?error=Username or email already exists');
    exit();
}

$hashedPassword = md5($password);

$sql = "INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, is_active) 
        VALUES ('$username', '$email', '$hashedPassword', $role_id, '$first_name', '$last_name', 1)";

if ($conn->query($sql)) {
    $userId = $conn->insert_id;
    
    if ($role_id == 1 && !empty($student_number) && $course_id > 0) {
        $conn->query("INSERT INTO students (user_id, student_number, course_id, year_level, enrollment_status) 
                     VALUES ($userId, '$student_number', $course_id, $year_level, 'Active')");
    }
    
    if ($role_id == 2 && !empty($department)) {
        $facultyNumber = 'FAC-' . str_pad($userId, 3, '0', STR_PAD_LEFT);
        $conn->query("INSERT INTO faculty (user_id, faculty_number, department, specialization, status) 
                     VALUES ($userId, '$facultyNumber', '$department', '$specialization', 'Active')");
    }
    
    header('Location: ../../index.php?success=User created successfully');
} else {
    header('Location: ../../index.php?error=' . $conn->error);
}

$conn->close();
exit();
?>