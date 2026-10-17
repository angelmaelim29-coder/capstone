<?php
// api/registrar/bulk_approve.php - Bulk Approve Grades

session_start();

$conn = new mysqli('localhost', if0_43020735, '', 'grade_portal');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($ids)) {
    header('Location: ../../pages/registrar/dashboard.php?error=No grades selected');
    exit();
}

$successCount = 0;

foreach ($ids as $id) {
    $grade_id = intval($id);
    $conn->query("
        UPDATE grades 
        SET status = 'Approved',
            date_approved = NOW()
        WHERE grade_id = $grade_id
    ");
    if ($conn->affected_rows > 0) {
        $successCount++;
    }
}

header('Location: ../../pages/registrar/dashboard.php?success=' . $successCount . ' grades approved');
exit();
?>