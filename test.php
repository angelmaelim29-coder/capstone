<?php
// test.php - Test database connection

$host = 'localhost';
$user = if0_43020735;
$pass = 'NEzkh9Hxl3';
$dbname ='grade_portal';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "<h1>✅ Connected to: $dbname</h1>";

// Test tables
$tables = ['users', 'students', 'faculty', 'grades', 'grade_access_requests', 'payments'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as total FROM $table");
    if ($result) {
        $count = $result->fetch_assoc()['total'];
        echo "<p>Table <strong>$table</strong>: $count rows</p>";
    } else {
        echo "<p style='color:red;'>Table <strong>$table</strong>: ERROR</p>";
    }
}

$conn->close();
?>