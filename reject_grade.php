<?php
// api/registrar/reject_grade.php

session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname =  'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$grade_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$reason = isset($_GET['reason']) ? trim($_GET['reason']) : 'No reason provided';

if ($grade_id <= 0) {
    header('Location: ../../pages/registrar/dashboard.php?error=Invalid grade');
    exit();
}

$conn->query("
    UPDATE grades 
    SET status = 'Rejected',
        remarks = '" . $conn->real_escape_string($reason) . "'
    WHERE grade_id = $grade_id
");

header('Location: ../../pages/registrar/dashboard.php?success=Grade rejected');
exit();
?>