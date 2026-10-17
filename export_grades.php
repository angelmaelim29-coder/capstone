<?php
// api/registrar/export_grades.php - Clean Excel export

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Registrar') {
    header('Location: ../../login.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$filter_semester = isset($_GET['semester_filter']) ? $_GET['semester_filter'] : '';

$sql = "SELECT 
            s.student_number,
            CONCAT(u.first_name, ' ', u.last_name) as student_name,
            sub.subject_code,
            sub.subject_name,
            g.grade,
            g.status,
            g.semester,
            g.school_year,
            CONCAT(fu.first_name, ' ', fu.last_name) as faculty_name
        FROM grades g
        LEFT JOIN students s ON g.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN subjects sub ON g.subject_id = sub.subject_id
        LEFT JOIN faculty f ON g.faculty_id = f.faculty_id
        LEFT JOIN users fu ON f.user_id = fu.user_id
        WHERE g.grade IS NOT NULL AND g.grade > 0";

if (!empty($filter_semester)) {
    $sql .= " AND g.semester = '" . $conn->real_escape_string($filter_semester) . "' ";
}

$sql .= " ORDER BY u.last_name, u.first_name, sub.subject_code";

$result = $conn->query($sql);

$gradesArray = [];
while ($row = $result->fetch_assoc()) {
    $gradesArray[] = $row;
}

function convertToTIFTCIGrade($percentage) {
    if ($percentage === null || $percentage === '') return '';
    $num = floatval($percentage);
    if ($num >= 97) return '1.00';
    if ($num >= 94) return '1.25';
    if ($num >= 91) return '1.50';
    if ($num >= 88) return '1.75';
    if ($num >= 85) return '2.00';
    if ($num >= 83) return '2.25';
    if ($num >= 80) return '2.50';
    if ($num >= 78) return '2.75';
    if ($num >= 75) return '3.00';
    return '5.00';
}

function getTIFTCIDescription($grade) {
    $descriptions = [
        '1.00' => 'Excellent', '1.25' => 'Superior', '1.50' => 'Very Good',
        '1.75' => 'Good', '2.00' => 'Very Satisfactory', '2.25' => 'Satisfactory',
        '2.50' => 'Fair', '2.75' => 'Average', '3.00' => 'Passed',
        '5.00' => 'Failed'
    ];
    return $descriptions[$grade] ?? '';
}

$filename = "GradeReport_" . date('Y-m-d') . ".xls";
if (!empty($filter_semester)) {
    $filename = "GradeReport_" . str_replace(' ', '_', $filter_semester) . "_" . date('Y-m-d') . ".xls";
}

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
<x:Name>Grade Report</x:Name>
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
    .filter-info { font-size: 11pt; font-weight: 600; color: #1b3a5c; text-align: center; border: none; padding: 8px 0 14px 0; }
    .spacer { border: none; height: 5px; }
    .col-head { 
        background: #1b3a5c; 
        color: #ffffff; 
        font-weight: 600; 
        font-size: 10pt;
        text-align: center; 
        padding: 12px 8px;
        border: 1px solid #1b3a5c;
        letter-spacing: 0.5px;
    }
    .col-head-computed {
        background: #f5b400;
        color: #1b3a5c;
        font-weight: 700;
        font-size: 10pt;
        text-align: center;
        padding: 12px 8px;
        border: 1px solid #f5b400;
        letter-spacing: 0.5px;
    }
    .cell {
        border: 1px solid #e2e8f0;
        font-size: 10pt;
        padding: 10px 12px;
    }
    .cell-center { text-align: center; }
    .student-num { color: #1b3a5c; font-weight: 600; }
    .grade-cell { background: #fffbeb; font-weight: 700; color: #b45309; text-align: center; font-size: 11pt; }
    .tiftci-cell { background: #fef3c7; font-weight: 700; color: #92400e; text-align: center; font-size: 11pt; }
    .status-approved { color: #166534; font-weight: 600; text-align: center; }
    .status-pending { color: #92400e; font-weight: 600; text-align: center; }
    .status-rejected { color: #991b1b; font-weight: 600; text-align: center; }
    .status-draft { color: #64748b; font-weight: 600; text-align: center; }
</style>
</head>
<body>

<table>
    <tr>
        <td colspan="10" class="title">TRENT INFORMATION FIRST TECHNICAL CAREER INSTITUTE INC.</td>
    </tr>
    <tr>
        <td colspan="10" class="subtitle">TRENTIANS GRADEHUB — Official Grade Report</td>
    </tr>
    <tr>
        <td colspan="10" class="filter-info">
            <?php if (!empty($filter_semester)): ?>
                <?php echo htmlspecialchars($filter_semester); ?> | School Year 2025-2026
            <?php else: ?>
                All Semesters | School Year 2025-2026
            <?php endif; ?>
        </td>
    </tr>
    
    <tr><td colspan="10" class="spacer"></td></tr>
    
    <tr>
        <td class="col-head" style="width: 130px;">STUDENT NUMBER</td>
        <td class="col-head" style="width: 200px;">STUDENT NAME</td>
        <td class="col-head" style="width: 90px;">SUBJECT CODE</td>
        <td class="col-head" style="width: 220px;">SUBJECT NAME</td>
        <td class="col-head-computed" style="width: 100px;">GRADE (%)</td>
        <td class="col-head-computed" style="width: 100px;">TIFTCI GRADE</td>
        <td class="col-head-computed" style="width: 130px;">REMARKS</td>
        <td class="col-head" style="width: 100px;">STATUS</td>
        <td class="col-head" style="width: 120px;">SEMESTER</td>
        <td class="col-head" style="width: 200px;">FACULTY</td>
    </tr>
    
    <?php if (!empty($gradesArray)): 
        foreach ($gradesArray as $g): 
            $tiftciGrade = convertToTIFTCIGrade($g['grade']);
            $remarks = getTIFTCIDescription($tiftciGrade);
            $statusClass = 'status-draft';
            if ($g['status'] === 'Approved') $statusClass = 'status-approved';
            elseif ($g['status'] === 'Submitted') $statusClass = 'status-pending';
            elseif ($g['status'] === 'Rejected') $statusClass = 'status-rejected';
    ?>
    <tr>
        <td class="cell student-num"><?php echo htmlspecialchars($g['student_number'] ?? 'N/A'); ?></td>
        <td class="cell"><?php echo htmlspecialchars($g['student_name'] ?? 'Unknown'); ?></td>
        <td class="cell cell-center"><strong><?php echo htmlspecialchars($g['subject_code'] ?? 'N/A'); ?></strong></td>
        <td class="cell"><?php echo htmlspecialchars($g['subject_name'] ?? 'N/A'); ?></td>
        <td class="cell grade-cell"><?php echo number_format($g['grade'], 2) . '%'; ?></td>
        <td class="cell tiftci-cell"><?php echo $tiftciGrade; ?></td>
        <td class="cell cell-center"><?php echo $remarks; ?></td>
        <td class="cell <?php echo $statusClass; ?>"><?php echo htmlspecialchars($g['status'] ?? 'Draft'); ?></td>
        <td class="cell cell-center" style="font-size: 9.5pt; color: #64748b;"><?php echo htmlspecialchars($g['semester'] ?? ''); ?></td>
        <td class="cell" style="font-size: 9pt; color: #64748b;"><?php echo htmlspecialchars($g['faculty_name'] ?? 'N/A'); ?></td>
    </tr>
    <?php 
        endforeach;
    else:
    ?>
    <tr>
        <td colspan="10" class="cell" style="text-align: center; padding: 30px; color: #94a3b8;">
            No grade records found. Grades will appear here once Faculty submits them.
        </td>
    </tr>
    <?php endif; ?>
    
    <tr><td colspan="10" class="spacer"></td></tr>
    
    <tr>
        <td colspan="10" style="border: none; background: #f8fafc; padding: 12px 15px; font-size: 9pt; color: #64748b; border-left: 3px solid #f5b400;">
            <strong style="color: #1b3a5c;">Total Records:</strong> <?php echo count($gradesArray); ?> &nbsp; | &nbsp; 
            <strong style="color: #1b3a5c;">Generated:</strong> <?php echo date('F d, Y h:i A'); ?>
        </td>
    </tr>
</table>

</body>
</html>
<?php
$conn->close();
exit();
?>