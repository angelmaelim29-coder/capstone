<?php
// api/student/mark_all_read.php

session_start();

$host = 'localhost';
$user = if0_43020735;
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);

header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$conn->query("UPDATE notifications SET read_status = 1 WHERE user_id = $user_id AND read_status = 0");
$conn->close();

echo json_encode(['success' => true]);
exit();
?>