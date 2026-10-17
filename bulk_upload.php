<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') { die("Unauthorized"); }

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'trentians_gradehub';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $section_id = intval($_POST['section_id']);
    $subject_id = intval($_POST['subject_id']);
    $file = $_FILES['file']['tmp_name'];

    if ($_FILES['file']['size'] > 0) {
        $handle = fopen($file, "r");
        
        // Skip the first 8 rows (headers and instructions)
        for ($i = 0; $i < 8; $i++) {
            fgetcsv($handle);
        }

        $successCount = 0;
        // Read data rows
        while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
            // $data[0] = Student Number, $data[2] = Prelim, $data[3] = Midterm, etc.
            if (empty($data[0])) continue;

            $student_number = trim($data[0]);
            $prelim = trim($data[2]);
            $midterm = trim($data[3]);
            $prefinal = trim($data[4]);
            $final = trim($data[5]);
            $comment = trim($data[6] ?? '');

            // Get user_id and enrollment_id
            $q = $conn->query("
                SELECT e.enrollment_id FROM enrollments e 
                JOIN students s ON e.user_id = s.user_id 
                WHERE s.student_number = '$student_number' 
                AND e.section_id = $section_id AND e.subject_id = $subject_id
            ");
            
            if ($q && $q->num_rows > 0) {
                $enr = $q->fetch_assoc();
                $enrollment_id = $enr['enrollment_id'];

                // Check if grade exists
                $check = $conn->query("SELECT grade_id FROM grades WHERE enrollment_id = $enrollment_id");
                if ($check->num_rows > 0) {
                    // Update
                    $conn->query("UPDATE grades SET prelim_grade='$prelim', midterm_grade='$midterm', prefinal_grade='$prefinal', final_grade='$final', teacher_comment='$comment', status='Draft' WHERE enrollment_id=$enrollment_id");
                } else {
                    // Insert
                    $conn->query("INSERT INTO grades (enrollment_id, prelim_grade, midterm_grade, prefinal_grade, final_grade, teacher_comment, status) VALUES ($enrollment_id, '$prelim', '$midterm', '$prefinal', '$final', '$comment', 'Draft')");
                }
                $successCount++;
            }
        }
        fclose($handle);
        header("Location: ../../pages/faculty/dashboard.php?success=$successCount students' grades uploaded!");
        exit();
    }
}
header("Location: ../../pages/faculty/dashboard.php?error=Upload failed");
exit();
?>