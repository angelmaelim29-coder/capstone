<?php
// pages/admin/add_student.php - Independent Add Student Page

session_start();
require_once '../../includes/config.php';
require_once '../../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../login.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname =  'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$error = '';

// Get courses
$courses = $conn->query("SELECT * FROM courses ORDER BY course_code");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $course_id = intval($_POST['course_id'] ?? 0);
    $year_level = intval($_POST['year_level'] ?? 1);
    
    // Validate
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($student_number) || empty($course_id)) {
        $error = 'All fields are required';
    } else {
        // Check if username exists
        $check = $conn->query("SELECT user_id FROM users WHERE username = '$username' OR email = '$email'");
        if ($check && $check->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Insert user
            $hashedPassword = md5($password);
            $sql = "INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, is_active) 
                    VALUES ('$username', '$email', '$hashedPassword', 1, '$first_name', '$last_name', 1)";
            
            if ($conn->query($sql)) {
                $userId = $conn->insert_id;
                
                // Insert student
                $sql2 = "INSERT INTO students (user_id, student_number, course_id, year_level, enrollment_status) 
                         VALUES ($userId, '$student_number', $course_id, $year_level, 'Active')";
                
                if ($conn->query($sql2)) {
                    $message = '✅ Student added successfully!';
                } else {
                    $conn->query("DELETE FROM users WHERE user_id = $userId");
                    $error = 'Failed to create student record: ' . $conn->error;
                }
            } else {
                $error = 'Failed to create user';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Student - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 600px; margin: 50px auto; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .card-header { background: #1b3a5c; color: white; border-radius: 12px 12px 0 0; padding: 15px 20px; }
        .btn-primary { background: #1b3a5c; border: none; }
        .btn-primary:hover { background: #0d1b2a; }
        .form-label { font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-person-plus"></i> Add New Student</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Student Number *</label>
                        <input type="text" name="student_number" class="form-control" placeholder="2024-00X" required>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Course *</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">Select Course...</option>
                            <?php while ($c = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $c['course_id']; ?>">
                                    <?php echo $c['course_code'] . ' - ' . $c['course_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Year Level</label>
                        <select name="year_level" class="form-select">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100">Add Student</button>
                        <a href="../../index.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>