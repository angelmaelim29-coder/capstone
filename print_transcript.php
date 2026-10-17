<?php
// pages/student/print_transcript.php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header('Location: ../../login.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Get Student Info
$studentResult = $conn->query("
    SELECT s.*, u.first_name, u.last_name, c.course_code, c.course_name 
    FROM students s 
    JOIN users u ON s.user_id = u.user_id 
    LEFT JOIN courses c ON s.course_id = c.course_id 
    WHERE s.user_id = " . $_SESSION['user_id']
);
$student = $studentResult->fetch_assoc();

if (!$student) { die("Student record not found."); }

// Get Grades
$grades = $conn->query("
    SELECT g.*, sub.subject_code, sub.subject_name, sub.units
    FROM grades g
    JOIN subjects sub ON g.subject_id = sub.subject_id
    WHERE g.student_id = " . $student['student_id'] . "
    AND g.status = 'Approved'
    ORDER BY sub.subject_code
");

// TIFTCI Grade Converter
function convertToTIFTCIGrade($percentage) {
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
    $desc = ['1.00'=>'Excellent','1.25'=>'Superior','1.50'=>'Very Good','1.75'=>'Good','2.00'=>'Very Satisfactory','2.25'=>'Satisfactory','2.50'=>'Fair','2.75'=>'Average','3.00'=>'Passed','5.00'=>'Failed'];
    return $desc[$grade] ?? 'No Grade';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .transcript-container { background: white; max-width: 900px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .transcript-header { text-align: center; border-bottom: 3px double #1b3a5c; padding-bottom: 20px; margin-bottom: 30px; }
        .transcript-header h1 { color: #1b3a5c; font-size: 24px; font-weight: 700; margin: 0; }
        .transcript-header p { color: #64748b; margin: 5px 0 0; font-size: 13px; }
        .transcript-title { text-align: center; font-size: 16px; font-weight: 700; letter-spacing: 2px; color: #1b3a5c; margin-bottom: 30px; text-transform: uppercase; }
        .student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 8px; }
        .info-item { display: flex; justify-content: space-between; border-bottom: 1px dashed #cbd5e1; padding-bottom: 5px; }
        .info-item .label { color: #64748b; font-size: 13px; font-weight: 600; }
        .info-item .value { color: #1e293b; font-size: 13px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #1b3a5c; color: white; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        tr:nth-child(even) { background: #f8fafc; }
        .grade-final { font-weight: 700; color: #1b3a5c; }
        .signature { margin-top: 60px; text-align: right; }
        .signature-line { border-top: 1px solid #1e293b; width: 250px; margin: 0 0 8px auto; }
        .signature p { font-size: 12px; color: #64748b; margin: 0; }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .transcript-container { box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%; }
            .transcript-header { border-bottom: 3px double #000; }
            .transcript-header h1, .transcript-title { color: #000; }
            th { background: #ddd !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr:nth-child(even) { background: #f9f9f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-4">
    <button onclick="window.print()" class="btn btn-primary btn-lg">
        🖨️ Print Transcript
    </button>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">Back to Dashboard</a>
</div>

<div class="transcript-container">
    <div class="transcript-header">
        <h1>TIFTCI</h1>
        <p>Trent Information First Technical Career Institute</p>
        <p>Taytay, Rizal</p>
    </div>
    
    <div class="transcript-title">Official Transcript of Records</div>
    
    <div class="student-info">
        <div class="info-item">
            <span class="label">Student Number:</span>
            <span class="value"><?php echo htmlspecialchars($student['student_number']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Student Name:</span>
            <span class="value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Course:</span>
            <span class="value"><?php echo htmlspecialchars($student['course_code'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Year Level:</span>
            <span class="value"><?php echo $student['year_level']; ?>th Year</span>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th style="text-align: center;">Units</th>
                <th style="text-align: center;">Grade (%)</th>
                <th style="text-align: center;">TIFTCI Grade</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($grades && $grades->num_rows > 0): ?>
                <?php while ($g = $grades->fetch_assoc()): 
                    $tiftciGrade = convertToTIFTCIGrade($g['grade']);
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($g['subject_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($g['subject_name']); ?></td>
                    <td style="text-align: center;"><?php echo $g['units']; ?></td>
                    <td style="text-align: center;"><?php echo number_format($g['grade'], 2); ?>%</td>
                    <td style="text-align: center;" class="grade-final"><?php echo $tiftciGrade; ?></td>
                    <td><?php echo getTIFTCIDescription($tiftciGrade); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center; color: #64748b;">No approved grades available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="signature">
        <div class="signature-line"></div>
        <p>Registrar</p>
    </div>
</div>

</body>
</html>