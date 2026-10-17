<?php
// api/admin/add_section.php

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

$section_code = trim($_POST['section_code'] ?? '');
$section_name = trim($_POST['section_name'] ?? '');
$course_id = intval($_POST['course_id'] ?? 0);
$year_level = intval($_POST['year_level'] ?? 1);
$max_students = intval($_POST['max_students'] ?? 40);

if (empty($section_code) || empty($section_name) || $course_id <= 0) {
    header('Location: ../../index.php?error=All fields required');
    exit();
}

$conn->query("INSERT INTO sections (section_code, section_name, course_id, year_level, max_students, current_count, is_active) 
              VALUES ('$section_code', '$section_name', $course_id, $year_level, $max_students, 0, 1)");

header('Location: ../../index.php?success=Section added successfully');
exit();
?>