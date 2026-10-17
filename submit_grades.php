<?php
// api/faculty/submit_grades.php - Submit all draft grades for a class

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    header('Location: ../../login.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$section_id = isset($_GET['section']) ? intval($_GET['section']) : 0;
$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

if ($section_id <= 0 || $subject_id <= 0) {
    header('Location: ../../pages/faculty/dashboard.php?error=Invalid class');
    exit();
}

// Get faculty ID
$facultyResult = $conn->query("SELECT faculty_id FROM faculty WHERE user_id = " . $_SESSION['user_id']);
$faculty = $facultyResult->fetch_assoc();
if (!$faculty) {
    header('Location: ../../pages/faculty/dashboard.php?error=Faculty not found');
    exit();
}
$faculty_id = $faculty['faculty_id'];

$semester = '1st Semester';
$school_year = '2025-2026';

// Update all draft grades to submitted
$conn->query("
    UPDATE grades 
    SET status = 'Submitted',
        date_submitted = NOW()
    WHERE section_id = $section_id 
    AND subject_id = $subject_id
    AND faculty_id = $faculty_id
    AND semester = '$semester'
    AND school_year = '$school_year'
    AND status = 'Draft'
    AND grade IS NOT NULL
");

$affected = $conn->affected_rows;

// Notify all registrars
$registrars = $conn->query("SELECT user_id FROM users WHERE role_id = 3");
if ($registrars) {
    while ($reg = $registrars->fetch_assoc()) {
        $conn->query("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (
                " . $reg['user_id'] . ",
                '📋 New Grade Submission',
                'Faculty submitted grades for approval. Please review.',
                'Grade'
            )
        ");
    }
}

$conn->close();

if ($affected > 0) {
    header('Location: ../../pages/faculty/dashboard.php?success=' . urlencode("$affected grades submitted for approval! Waiting for Registrar review."));
} else {
    header('Location: ../../pages/faculty/dashboard.php?error=No draft grades found to submit. Please upload grades first.');
}
exit();
?>