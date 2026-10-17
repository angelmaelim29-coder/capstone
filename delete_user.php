<?php
// api/admin/delete_user.php

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

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    header('Location: ../../index.php?error=Invalid user ID');
    exit();
}

// Prevent deleting main admin
$checkAdmin = $conn->query("SELECT username FROM users WHERE user_id = $user_id AND username = 'admin'");
if ($checkAdmin && $checkAdmin->num_rows > 0) {
    header('Location: ../../index.php?error=Cannot delete main admin');
    exit();
}

// Get role
$userResult = $conn->query("SELECT role_id FROM users WHERE user_id = $user_id");
if (!$userResult || $userResult->num_rows == 0) {
    header('Location: ../../index.php?error=User not found');
    exit();
}
$user = $userResult->fetch_assoc();
$role_id = $user['role_id'];

// Delete related records
if ($role_id == 1) {
    $conn->query("DELETE FROM students WHERE user_id = $user_id");
}
if ($role_id == 2) {
    $conn->query("DELETE FROM faculty WHERE user_id = $user_id");
}

$conn->query("DELETE FROM notifications WHERE user_id = $user_id");
$conn->query("DELETE FROM users WHERE user_id = $user_id");

header('Location: ../../index.php?success=User deleted');
exit();
?>