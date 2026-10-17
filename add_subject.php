<?php
// api/admin/add_subject.php

session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'grade_portal';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$subject_code = trim($_POST['subject_code'] ?? '');
$subject_name = trim($_POST['subject_name'] ?? '');
$units = floatval($_POST['units'] ?? 3);
$course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;

if (empty($subject_code) || empty($subject_name)) {
    header('Location: ../../index.php?error=Subject code and name required');
    exit();
}

$courseIdValue = $course_id ? $course_id : 'NULL';
$conn->query("INSERT INTO subjects (subject_code, subject_name, units, course_id, is_active) 
              VALUES ('$subject_code', '$subject_name', $units, $courseIdValue, 1)");

header('Location: ../../index.php?success=Subject added successfully');
exit();
?>