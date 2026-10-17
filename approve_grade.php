<?php
// api/registrar/approve_grade.php - Updated with success redirect

session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$grade_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$show_success = isset($_GET['show_success']) ? intval($_GET['show_success']) : 0;
$student = isset($_GET['student']) ? urlencode($_GET['student']) : '';

if ($grade_id <= 0) {
    header('Location: ../../pages/registrar/dashboard.php?error=Invalid grade');
    exit();
}

// Get grade info
$gradeInfo = $conn->query("
    SELECT g.*, s.user_id as student_user_id, sub.subject_code
    FROM grades g
    JOIN students s ON g.student_id = s.student_id
    JOIN subjects sub ON g.subject_id = sub.subject_id
    WHERE g.grade_id = $grade_id
")->fetch_assoc();

if (!$gradeInfo) {
    header('Location: ../../pages/registrar/dashboard.php?error=Grade not found');
    exit();
}

// Update status
$conn->query("
    UPDATE grades 
    SET status = 'Approved',
        date_approved = NOW()
    WHERE grade_id = $grade_id
");

// Notify student
$subject = $gradeInfo['subject_code'] ?? 'a subject';
$conn->query("
    INSERT INTO notifications (user_id, title, message, type) 
    VALUES (
        " . $gradeInfo['student_user_id'] . ",
        '📚 Grade Approved',
        'Your grade for $subject has been approved by the Registrar. Log in to view it.',
        'Grade'
    )
");

$conn->close();

// Build redirect URL
$redirect = '../../pages/registrar/dashboard.php';
$params = [];

if ($show_success == 1) {
    $params[] = 'show_success=1';
    if (!empty($student)) {
        $params[] = 'student=' . $student;
    }
} else {
    $params[] = 'success=' . urlencode('Grade approved and student notified');
}

$redirect .= '?' . implode('&', $params);
header("Location: $redirect");
exit();
?>