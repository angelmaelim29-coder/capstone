<?php
// api/admin/add_course.php

session_start();

$host = 'localhost';
$user = 'root';
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

$course_code = trim($_POST['course_code'] ?? '');
$course_name = trim($_POST['course_name'] ?? '');
$duration = trim($_POST['duration'] ?? '4 Years');
$department = trim($_POST['department'] ?? 'ICT');

if (empty($course_code) || empty($course_name)) {
    header('Location: ../../index.php?error=Course code and name required');
    exit();
}

$conn->query("INSERT INTO courses (course_code, course_name, duration, department, is_active) 
              VALUES ('$course_code', '$course_name', '$duration', '$department', 1)");

header('Location: ../../index.php?success=Course added successfully');
exit();
?>