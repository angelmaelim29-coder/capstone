<?php
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

if ($section_id <= 0 || $subject_id <= 0) { die("Invalid parameters"); }

$infoQuery = $conn->query("
    SELECT sec.section_name, sub.subject_code, sub.subject_name 
    FROM sections sec, subjects sub 
    WHERE sec.section_id = $section_id AND sub.subject_id = $subject_id
");
$info = $infoQuery->fetch_assoc();
$section_code = $info['section_name'] ?? 'Section';
$subject_code = $info['subject_code'] ?? 'Subject';
$subject_name = $info['subject_name'] ?? '';

// ITO ANG INAYOS KO: Gumamit ng e.user_id at e.enrollment_id
$students = $conn->query("
    SELECT s.student_number, 
           CONCAT(u.first_name, ' ', u.last_name) as name,
           g.prelim_grade, g.midterm_grade, g.prefinal_grade, g.final_grade,
           g.teacher_comment
    FROM enrollments e
    JOIN users u ON e.user_id = u.user_id
    JOIN students s ON e.user_id = s.user_id
    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
    WHERE e.section_id = $section_id AND e.subject_id = $subject_id
    ORDER BY u.last_name, u.first_name
");

$studentsArray = [];
if($students) {
    while ($s = $students->fetch_assoc()) {
        $studentsArray[] = $s;
    }
}

$filename = "GradeSheet_" . $section_code . "_" . $subject_code . ".xls";

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]>
<xml>
<x:ExcelWorkbook>
<x:ExcelWorksheets>
<x:ExcelWorksheet>
<x:Name>Grades</x:Name>
<x:WorksheetOptions>
<x:DisplayGridlines>False</x:DisplayGridlines>
</x:WorksheetOptions>
</x:ExcelWorksheet>
</x:ExcelWorksheets>
</x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
    table { border-collapse: collapse; font-family: 'Segoe UI', Calibri, Arial, sans-serif; font-size: 11pt; }
    td { padding: 8px 12px; vertical-align: middle; }
    .title { font-size: 14pt; font-weight: 700; color: #1b3a5c; text-align: center; border: none; padding: 15px 0 4px 0; }
    .subtitle { font-size: 10pt; color: #64748b; text-align: center; border: none; padding: 2px 0; }
    .class-info { font-size: 11pt; font-weight: 600; color: #1b3a5c; text-align: center; border: none; padding: 8px 0 14px 0; }
    .spacer { border: none; height: 5px; }
    .instruction { background: #f0f9ff; color: #075985; border: 1px solid #bae6fd; padding: 10px 14px; font-size: 10pt; text-align: center; }
    .col-head { background: #1b3a5c; color: #ffffff; font-weight: 600; font-size: 10pt; text-align: center; padding: 12px 8px; border: 1px solid #1b3a5c; letter-spacing: 0.5px; }
    .col-head-input { background: #2563eb; color: #ffffff; font-weight: 700; font-size: 10pt; text-align: center; padding: 12px 8px; border: 1px solid #2563eb; letter-spacing: 0.5px; }
    .col-head-comment { background: #f59e0b; color: #ffffff; font-weight: 700; font-size: 10pt; text-align: center; padding: 12px 8px; border: 1px solid #f59e0b; letter-spacing: 0.5px; }
    .cell { border: 1px solid #e2e8f0; font-size: 10pt; padding: 10px 12px; }
    .cell-center { text-align: center; }
    .student-num { color: #1b3a5c; font-weight: 600; }
    .input-cell { background: #ffffff; text-align: center; font-size: 11pt; font-weight: 500; }
    .comment-cell { background: #fffbeb; font-size: 9.5pt; }
</style>
</head>
<body>

<table>
    <tr><td colspan="7" class="title">TRENT INFORMATION FIRST TECHNICAL CAREER INSTITUTE INC.</td></tr>
    <tr><td colspan="7" class="subtitle">TRENTIANS GRADEHUB — Official Grade Sheet</td></tr>
    <tr><td colspan="7" class="class-info"><?php echo htmlspecialchars($section_code . '   |   ' . $subject_code . ' - ' . $subject_name); ?></td></tr>
    <tr><td colspan="7" class="spacer"></td></tr>
    <tr>
        <td colspan="7" class="instruction">
            <strong>HOW TO USE:</strong> Type grade percentages (e.g., 90, 85.5) in the blue columns. 
            Add a comment for each student in the yellow column. Then save the file and upload it back.
        </td>
    </tr>
    <tr><td colspan="7" class="spacer"></td></tr>
    
    <tr>
        <td class="col-head" style="width: 140px;">STUDENT NUMBER</td>
        <td class="col-head" style="width: 220px;">STUDENT NAME</td>
        <td class="col-head-input" style="width: 80px;">PRELIM</td>
        <td class="col-head-input" style="width: 80px;">MIDTERM</td>
        <td class="col-head-input" style="width: 90px;">PREFINAL</td>
        <td class="col-head-input" style="width: 80px;">FINAL</td>
        <td class="col-head-comment" style="width: 320px;">COMMENT</td>
    </tr>
    
    <?php if (!empty($studentsArray)): foreach ($studentsArray as $s): ?>
    <tr>
        <td class="cell student-num"><?php echo htmlspecialchars($s['student_number']); ?></td>
        <td class="cell"><?php echo htmlspecialchars($s['name']); ?></td>
        <td class="cell input-cell"><?php echo $s['prelim_grade'] !== null ? $s['prelim_grade'] : ''; ?></td>
        <td class="cell input-cell"><?php echo $s['midterm_grade'] !== null ? $s['midterm_grade'] : ''; ?></td>
        <td class="cell input-cell"><?php echo $s['prefinal_grade'] !== null ? $s['prefinal_grade'] : ''; ?></td>
        <td class="cell input-cell"><?php echo $s['final_grade'] !== null ? $s['final_grade'] : ''; ?></td>
        <td class="cell comment-cell"><?php echo htmlspecialchars($s['teacher_comment'] ?? ''); ?></td>
    </tr>
    <?php endforeach; else: ?>
    <tr><td colspan="7" class="cell" style="text-align: center; padding: 30px; color: #94a3b8;">No students enrolled in this class.</td></tr>
    <?php endif; ?>
    
    <tr><td colspan="7" class="spacer"></td></tr>
    <tr>
        <td colspan="7" style="border: none; background: #f8fafc; padding: 12px 15px; font-size: 9pt; color: #64748b; border-left: 3px solid #f5b400;">
            <strong style="color: #1b3a5c;">TIFTCI Grading Scale:</strong> 
            1.00 (97-100) · 1.25 (94-96) · 1.50 (91-93) · 1.75 (88-90) · 2.00 (85-87) · 2.25 (83-84) · 2.50 (80-82) · 2.75 (78-79) · 3.00 (75-77) · 5.00 (below 75)
        </td>
    </tr>
</table>
</body>
</html>
<?php
$conn->close();
exit();
?>