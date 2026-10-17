<?php
// api/registrar/export_faculty.php - Professional Faculty List Export

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

$sql = "SELECT 
            f.faculty_number,
            u.first_name,
            u.last_name,
            u.email,
            f.department,
            f.specialization,
            f.status
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        ORDER BY u.last_name, u.first_name";

$result = $conn->query($sql);

$facultyArray = [];
while ($row = $result->fetch_assoc()) {
    $facultyArray[] = $row;
}

// Filename
$filename = "Faculty_List_" . date('Y-m-d') . ".xls";

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
<x:Name>Faculty List</x:Name>
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
    .faculty-num { color: #1b3a5c; font-weight: 600; font-family: 'Consolas', 'Monaco', monospace; }
    .email-cell { color: #64748b; font-size: 9.5pt; }
    .dept-badge { 
        background: #dbeafe; 
        color: #1e40af; 
        font-weight: 600; 
        text-align: center;
        font-size: 10pt;
    }
    .spec-cell { color: #475569; font-size: 9.5pt; font-style: italic; }
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
        <td colspan="7" class="subtitle">TRENTIANS GRADEHUB — Official Faculty Directory</td>
    </tr>
    <tr>
        <td colspan="7" class="filter-info">
            Complete Faculty List | Generated on <?php echo date('F d, Y'); ?>
        </td>
    </tr>
    
    <tr><td colspan="7" class="spacer"></td></tr>
    
    <!-- Column Headers -->
    <tr>
        <td class="col-head" style="width: 130px;">FACULTY NUMBER</td>
        <td class="col-head" style="width: 180px;">LAST NAME</td>
        <td class="col-head" style="width: 180px;">FIRST NAME</td>
        <td class="col-head" style="width: 250px;">EMAIL ADDRESS</td>
        <td class="col-head-accent" style="width: 130px;">DEPARTMENT</td>
        <td class="col-head" style="width: 180px;">SPECIALIZATION</td>
        <td class="col-head-accent" style="width: 100px;">STATUS</td>
    </tr>
    
    <?php if (!empty($facultyArray)): 
        foreach ($facultyArray as $f): 
            $statusClass = ($f['status'] === 'Active') ? 'status-active' : 'status-inactive';
    ?>
    <tr>
        <td class="cell faculty-num"><?php echo htmlspecialchars($f['faculty_number'] ?? 'N/A'); ?></td>
        <td class="cell"><strong><?php echo htmlspecialchars($f['last_name'] ?? ''); ?></strong></td>
        <td class="cell"><?php echo htmlspecialchars($f['first_name'] ?? ''); ?></td>
        <td class="cell email-cell"><?php echo htmlspecialchars($f['email'] ?? 'N/A'); ?></td>
        <td class="cell dept-badge"><?php echo htmlspecialchars($f['department'] ?? 'N/A'); ?></td>
        <td class="cell spec-cell"><?php echo htmlspecialchars($f['specialization'] ?? 'N/A'); ?></td>
        <td class="cell <?php echo $statusClass; ?>"><?php echo htmlspecialchars($f['status'] ?? 'N/A'); ?></td>
    </tr>
    <?php 
        endforeach;
    else:
    ?>
    <tr>
        <td colspan="7" class="cell" style="text-align: center; padding: 30px; color: #94a3b8;">
            No faculty records found.
        </td>
    </tr>
    <?php endif; ?>
    
    <tr><td colspan="7" class="spacer"></td></tr>
    
    <!-- Footer -->
    <tr>
        <td colspan="7" style="border: none; background: #f8fafc; padding: 12px 15px; font-size: 9pt; color: #64748b; border-left: 3px solid #f5b400;">
            <strong style="color: #1b3a5c;">Total Faculty:</strong> <?php echo count($facultyArray); ?> &nbsp; | &nbsp; 
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