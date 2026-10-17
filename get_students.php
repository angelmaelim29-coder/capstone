<?php
// api/faculty/get_students.php - With Teacher Comments

session_start();

header('Content-Type: application/json');

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$section_id = isset($_GET['section']) ? intval($_GET['section']) : 0;
$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 0;

if ($section_id <= 0 || $subject_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$facultyResult = $conn->query("SELECT faculty_id FROM faculty WHERE user_id = " . $_SESSION['user_id']);
if (!$facultyResult || $facultyResult->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Faculty not found']);
    exit();
}
$faculty = $facultyResult->fetch_assoc();

// Get students WITH comment
$studentsResult = $conn->query("
    SELECT s.student_id, s.student_number, 
           CONCAT(u.first_name, ' ', u.last_name) as name,
           g.grade_id, g.prelim_grade, g.midterm_grade, g.prefinal_grade, g.final_grade, g.grade,
           g.teacher_comment,
           g.status as grade_status
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN grades g ON g.student_id = s.student_id 
        AND g.subject_id = $subject_id 
        AND g.section_id = $section_id
        AND g.semester = '1st Semester'
        AND g.school_year = '2025-2026'
    GROUP BY s.student_id
    ORDER BY s.student_number
");

$studentsList = [];
if ($studentsResult && $studentsResult->num_rows > 0) {
    while ($row = $studentsResult->fetch_assoc()) {
        $studentsList[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'students' => $studentsList,
    'faculty_id' => $faculty['faculty_id']
]);

$conn->close();
?>