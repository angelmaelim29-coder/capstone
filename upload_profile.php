<?php
session_start();
if (!isset($_SESSION['user_id'])) { die("Unauthorized"); }

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'trentians_gradehub';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$redirectUrl = '';

// Determine kung saan ibabalik ang user pagkatapos mag-upload
if ($role === 'Admin') { $redirectUrl = '../index.php'; }
elseif ($role === 'Registrar') { $redirectUrl = '../pages/registrar/dashboard.php'; }
elseif ($role === 'Faculty') { $redirectUrl = '../pages/faculty/dashboard.php'; }
elseif ($role === 'Student') { $redirectUrl = '../pages/student/dashboard.php'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: $redirectUrl?error=Upload failed");
        exit();
    }

    // Validate file type (allowed: jpg, jpeg, png)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        header("Location: $redirectUrl?error=Invalid file type. Only JPG and PNG allowed.");
        exit();
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        header("Location: $redirectUrl?error=File is too large. Max 2MB.");
        exit();
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = "profile_" . $user_id . "_" . time() . "." . $ext;
    $uploadDir = '../assets/images/profiles/';
    $uploadPath = $uploadDir . $newFilename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $newFilename, $user_id);
        $stmt->execute();
        
        header("Location: $redirectUrl?success=Profile picture updated!");
        exit();
    } else {
        header("Location: $redirectUrl?error=Failed to save file.");
        exit();
    }
}
$conn->close();
?>