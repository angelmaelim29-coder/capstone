<?php
// api/register_student.php - Student Number becomes Username

session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php?error=Invalid request');
    exit();
}

// Collect form data
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$student_number = trim($_POST['student_number'] ?? '');
$course_id = intval($_POST['course_id'] ?? 0);
$year_level = intval($_POST['year_level'] ?? 1);

// 🆕 THE STUDENT NUMBER BECOMES THE USERNAME
$username = $student_number;

$preserve = http_build_query([
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'student_number' => $student_number,
    'course_id' => $course_id,
    'year_level' => $year_level
]);

// ============================================
// VALIDATION
// ============================================
if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || 
    empty($student_number) || $course_id <= 0) {
    header("Location: ../register.php?error=All required fields must be filled.&$preserve");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: ../register.php?error=Passwords do not match.&$preserve");
    exit();
}

if (strlen($password) < 6) {
    header("Location: ../register.php?error=Password must be at least 6 characters.&$preserve");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../register.php?error=Please enter a valid email address.&$preserve");
    exit();
}

// Check duplicates — using student_number as username
$checkUser = $conn->query("SELECT user_id FROM users WHERE username = '" . $conn->real_escape_string($username) . "'");
if ($checkUser && $checkUser->num_rows > 0) {
    header("Location: ../register.php?error=This Student Number is already registered.&$preserve");
    exit();
}

$checkEmail = $conn->query("SELECT user_id FROM users WHERE email = '" . $conn->real_escape_string($email) . "'");
if ($checkEmail && $checkEmail->num_rows > 0) {
    header("Location: ../register.php?error=Email is already registered. Try logging in instead.&$preserve");
    exit();
}

$checkStudentNo = $conn->query("SELECT student_id FROM students WHERE student_number = '" . $conn->real_escape_string($student_number) . "'");
if ($checkStudentNo && $checkStudentNo->num_rows > 0) {
    header("Location: ../register.php?error=Student number is already registered. Contact the registrar if this is an error.&$preserve");
    exit();
}

// ============================================
// 🔒 VERIFICATION CHECK
// ============================================
$verifyCheck = $conn->query("
    SELECT * FROM verified_students 
    WHERE student_number = '" . $conn->real_escape_string($student_number) . "' 
    AND is_used = FALSE
");

$account_status = 'Pending';
$verified_student = null;
$auto_approved = false;

if ($verifyCheck && $verifyCheck->num_rows > 0) {
    $verified_student = $verifyCheck->fetch_assoc();
    $account_status = 'Active';
    $auto_approved = true;
}

// ============================================
// CREATE THE ACCOUNT
// ============================================
$hashedPassword = md5($password);
$role_id = 1; // Student

// 🆕 Username = Student Number
$insertUser = $conn->query("
    INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, is_active, account_status)
    VALUES (
        '" . $conn->real_escape_string($username) . "',
        '" . $conn->real_escape_string($email) . "',
        '$hashedPassword',
        $role_id,
        '" . $conn->real_escape_string($first_name) . "',
        '" . $conn->real_escape_string($last_name) . "',
        1,
        '$account_status'
    )
");

if (!$insertUser) {
    header("Location: ../register.php?error=Registration failed. Please try again.&$preserve");
    exit();
}

$new_user_id = $conn->insert_id;

$insertStudent = $conn->query("
    INSERT INTO students (user_id, student_number, course_id, year_level, enrollment_status)
    VALUES (
        $new_user_id,
        '" . $conn->real_escape_string($student_number) . "',
        $course_id,
        $year_level,
        'Active'
    )
");

if (!$insertStudent) {
    $conn->query("DELETE FROM users WHERE user_id = $new_user_id");
    header("Location: ../register.php?error=Failed to create student record.&$preserve");
    exit();
}

if ($verified_student) {
    $conn->query("UPDATE verified_students SET is_used = TRUE WHERE id = " . $verified_student['id']);
}

// Notify admins if pending
if ($account_status === 'Pending') {
    $admins = $conn->query("SELECT user_id FROM users WHERE role_id = 5");
    if ($admins) {
        while ($admin = $admins->fetch_assoc()) {
            $conn->query("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (
                    " . $admin['user_id'] . ",
                    '🔔 New Student Registration',
                    'New student pending approval: " . $conn->real_escape_string($first_name . ' ' . $last_name) . " (Student No: $student_number).',
                    'General'
                )
            ");
        }
    }
}

if ($auto_approved) {
    header("Location: ../login.php?success=✅ Welcome! Your Student Number has been verified. You can now log in using your Student Number ($student_number).");
} else {
    header("Location: ../login.php?success=⏳ Registration submitted! Your account is pending admin approval. Once approved, log in using your Student Number ($student_number).");
}
$conn->close();
exit();
?>