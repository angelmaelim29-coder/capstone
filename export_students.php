<?php
// api/registrar/export_students.php - Professional Student List Export

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

// Get optional course filter
$course_filter = isset($_GET['course_filter']) ? intval($_GET['course_filter']) : 0;

$sql = "SELECT 
            s.student_number,
            u.first_name,
            u.last_name,
            u.email,
            c.course_code,
            c.course_name,
            s.year_level,
            s.enrollment_status
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE 1=1";

if ($course_filter > 0) {
    $sql .= " AND s.course_id = $course_filter";
}

$sql .= " ORDER BY u.last_name, u.first_name";

$result = $conn->query($sql);

$studentsArray = [];
while ($row = $result->fetch_assoc()) {
    $studentsArray[] = $row;
}

// Filename
$filename = "Student_List_" . date('Y-m-d') . ".xls";

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// UTF-8 BOM
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
<x:Name>Student List</x:Name>
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
        padding: 12px 10px;
        border: 1px solid #1b3a5c;
        letter-spacing: 0.5px;
    }
    .col-head-accent {
        background: #f5b400;
        color: #1b3a5c;
        font-weight: 700;
        font-size: 10pt;
        text-align: center;
        padding: 12px 10px;
        border: 1px solid #f5b400;
        letter-spacing: 0.5px;
    }
    .cell {
        border: 1px solid #e2e8f0;
        font-size: 10pt;
        padding: 10px 12px;
        vertical-align: middle;
    }
    .cell-center { text-align: center; }
    .student-num { color: #1b3a5c; font-weight: 600; font-family: 'Consolas', 'Monaco', monospace; }
    .email-cell { color: #64748b; font-size: 9.5pt; }
    .course-badge { color: #1b3a5c; font-weight: 600; }
    .year-badge { 
        background: #fef3c7; 
        color: #92400e; 
        font-weight: 700; 
        text-align: center;
        font-size: 10pt;
    }
    .status-active { 
        color: #166534; 
        font-weight: 700;
        text-align: center;
        font-size: 10pt;
    }
    .status-inactive { 
        color: #991b1b; 
        font-weight: 700;
        text-align: center;
        font-size: 10pt;
    }
</style>
</head>
<body>

<table>
    <!-- Title -->
    <tr>
        <td colspan="7" class="title">TRENT INFORMATION FIRST TECHNICAL CAREER INSTITUTE INC.</td>
    </tr>
    <tr>
        <td colspan="7" class="subtitle">TRENTIANS GRADEHUB — Official Student Directory</td>
    </tr>
    <tr>
        <td colspan="7" class="filter-info">
            <?php if ($course_filter > 0): ?>
                Filtered by Course | Generated on <?php echo date('F d, Y'); ?>
            <?php else: ?>
                All Students | Generated on <?php echo date('F d, Y'); ?>
            <?php endif; ?>
        </td>
    </tr>
    
    <tr><td colspan="7" class="spacer"></td></tr>
    
    <!-- Column Headers -->
    <tr>
        <td class="col-head" style="width: 130px;">STUDENT NUMBER</td>
        <td class="col-head" style="width: 180px;">LAST NAME</td>
        <td class="col-head" style="width: 180px;">FIRST NAME</td>
        <td class="col-head" style="width: 250px;">EMAIL ADDRESS</td>
        <td class="col-head-accent" style="width: 100px;">COURSE</td>
        <td class="col-head-accent" style="width: 100px;">YEAR LEVEL</td>
        <td class="col-head-accent" style="width: 120px;">STATUS</td>
    </tr>
    
    <?php if (!empty($studentsArray)): 
        foreach ($studentsArray as $s): 
            $statusClass = ($s['enrollment_status'] === 'Active') ? 'status-active' : 'status-inactive';
            $yearText = $s['year_level'] . (($s['year_level'] == 1) ? 'st' : (($s['year_level'] == 2) ? 'nd' : (($s['year_level'] == 3) ? 'rd' : 'th'))) . ' Year';
    ?>
    <tr>
        <td class="cell student-num"><?php echo htmlspecialchars($s['student_number'] ?? 'N/A'); ?></td>
        <td class="cell"><strong><?php echo htmlspecialchars($s['last_name'] ?? ''); ?></strong></td>
        <td class="cell"><?php echo htmlspecialchars($s['first_name'] ?? ''); ?></td>
        <td class="cell email-cell"><?php echo htmlspecialchars($s['email'] ?? 'N/A'); ?></td>
        <td class="cell cell-center course-badge"><?php echo htmlspecialchars($s['course_code'] ?? 'N/A'); ?></td>
        <td class="cell year-badge"><?php echo $yearText; ?></td>
        <td class="cell <?php echo $statusClass; ?>"><?php echo htmlspecialchars($s['enrollment_status'] ?? 'N/A'); ?></td>
    </tr>
    <?php 
        endforeach;
    else:
    ?>
    <tr>
        <td colspan="7" class="cell" style="text-align: center; padding: 30px; color: #94a3b8;">
            No student records found.
        </td>
    </tr>
    <?php endif; ?>
    
    <tr><td colspan="7" class="spacer"></td></tr>
    
    <!-- Footer -->
    <tr>
        <td colspan="7" style="border: none; background: #f8fafc; padding: 12px 15px; font-size: 9pt; color: #64748b; border-left: 3px solid #f5b400;">
            <strong style="color: #1b3a5c;">Total Students:</strong> <?php echo count($studentsArray); ?> &nbsp; | &nbsp; 
            <strong style="color: #1b3a5c;">Generated:</strong> <?php echo date('F d, Y h:i A'); ?> &nbsp; | &nbsp; 
            <strong style="color: #1b3a5c;">Institution:</strong> Trent Information First Technical Career Institute Inc.
        </td>
    </tr>
</table>

</body>
</html>
<?php
$conn->close();
exit();
?>