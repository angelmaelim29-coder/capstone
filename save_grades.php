<?php
// api/faculty/save_grades.php - With Teacher Comments

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/faculty/dashboard.php?error=Invalid method');
    exit();
}

$section_id = intval($_POST['section_id'] ?? 0);
$subject_id = intval($_POST['subject_id'] ?? 0);
$faculty_id = intval($_POST['faculty_id'] ?? 0);
$semester = '1st Semester';
$school_year = '2025-2026';
$action = $_POST['action'] ?? 'draft';
$grades = $_POST['grades'] ?? [];

if ($section_id <= 0 || $subject_id <= 0 || empty($grades)) {
    header('Location: ../../pages/faculty/dashboard.php?error=Missing data');
    exit();
}

if ($faculty_id <= 0) {
    $facultyResult = $conn->query("SELECT faculty_id FROM faculty WHERE user_id = " . $_SESSION['user_id']);
    if ($facultyResult && $facultyResult->num_rows > 0) {
        $faculty = $facultyResult->fetch_assoc();
        $faculty_id = $faculty['faculty_id'];
    }
}

if ($faculty_id <= 0) {
    header('Location: ../../pages/faculty/dashboard.php?error=Faculty not found');
    exit();
}

$successCount = 0;

foreach ($grades as $student_id => $gradeData) {
    if (!is_array($gradeData)) continue;
    
    $prelim = isset($gradeData['prelim']) && $gradeData['prelim'] !== '' ? $gradeData['prelim'] : null;
    $midterm = isset($gradeData['midterm']) && $gradeData['midterm'] !== '' ? $gradeData['midterm'] : null;
    $prefinal = isset($gradeData['prefinal']) && $gradeData['prefinal'] !== '' ? $gradeData['prefinal'] : null;
    $final = isset($gradeData['final']) && $gradeData['final'] !== '' ? $gradeData['final'] : null;
    $grade = isset($gradeData['grade']) && $gradeData['grade'] !== '' ? $gradeData['grade'] : null;
    
    // 🆕 TEACHER'S COMMENT
    $comment = isset($gradeData['comment']) ? trim($gradeData['comment']) : '';
    
    if (empty($grade) && ($prelim || $midterm || $prefinal || $final)) {
        $scores = array_filter([floatval($prelim), floatval($midterm), floatval($prefinal), floatval($final)]);
        if (count($scores) > 0) {
            $grade = round(array_sum($scores) / count($scores), 2);
        }
    }
    
    $status = ($action === 'submit') ? 'Submitted' : 'Draft';
    $dateSubmitted = ($action === 'submit') ? date('Y-m-d H:i:s') : null;
    
    // 🆕 Handle comment for SQL
    $comment_sql = $comment !== '' ? "'" . $conn->real_escape_string($comment) . "'" : "NULL";
    
    $existingResult = $conn->query("
        SELECT grade_id FROM grades 
        WHERE student_id = $student_id 
        AND subject_id = $subject_id 
        AND section_id = $section_id
        AND semester = '$semester' 
        AND school_year = '$school_year'
    ");
    
    if ($existingResult && $existingResult->num_rows > 0) {
        $existing = $existingResult->fetch_assoc();
        $conn->query("
            UPDATE grades 
            SET prelim_grade = " . ($prelim !== null ? "'$prelim'" : "NULL") . ",
                midterm_grade = " . ($midterm !== null ? "'$midterm'" : "NULL") . ",
                prefinal_grade = " . ($prefinal !== null ? "'$prefinal'" : "NULL") . ",
                final_grade = " . ($final !== null ? "'$final'" : "NULL") . ",
                grade = " . ($grade !== null ? "'$grade'" : "NULL") . ",
                teacher_comment = $comment_sql,
                status = '$status',
                date_submitted = " . ($dateSubmitted ? "'$dateSubmitted'" : "NULL") . "
            WHERE grade_id = " . $existing['grade_id'] . "
        ");
        $successCount++;
    } else {
        $conn->query("
            INSERT INTO grades (student_id, subject_id, section_id, faculty_id, semester, school_year, 
                               prelim_grade, midterm_grade, prefinal_grade, final_grade, grade, teacher_comment, status, date_submitted) 
            VALUES (
                $student_id, $subject_id, $section_id, $faculty_id, '$semester', '$school_year',
                " . ($prelim !== null ? "'$prelim'" : "NULL") . ",
                " . ($midterm !== null ? "'$midterm'" : "NULL") . ",
                " . ($prefinal !== null ? "'$prefinal'" : "NULL") . ",
                " . ($final !== null ? "'$final'" : "NULL") . ",
                " . ($grade !== null ? "'$grade'" : "NULL") . ",
                $comment_sql,
                '$status',
                " . ($dateSubmitted ? "'$dateSubmitted'" : "NULL") . "
            )
        ");
        $successCount++;
    }
}

if ($action === 'submit') {
    $registrars = $conn->query("SELECT user_id FROM users WHERE role_id = 3");
    if ($registrars) {
        while ($reg = $registrars->fetch_assoc()) {
            $conn->query("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (" . $reg['user_id'] . ", 'New Grade Submission', 'Faculty submitted grades. Please review.', 'Grade')
            ");
        }
    }
    $message = 'Grades submitted for approval! (' . $successCount . ' students)';
} else {
    $message = 'Grades and comments saved as draft! (' . $successCount . ' students)';
}

$conn->close();
header('Location: ../../pages/faculty/dashboard.php?success=' . urlencode($message));
exit();
?>