<?php
// reset_passwords.php - Run this once to fix passwords

require_once 'includes/config.php';
require_once 'includes/db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>Resetting Passwords</h1>";

// Update passwords using md5 (simple hashing that works everywhere)
$passwords = [
    'admin' => 'admin123',
    'student1' => 'student123',
    'faculty1' => 'faculty123',
    'registrar1' => 'registrar123',
    'accounting1' => 'accounting123'
];

foreach ($passwords as $username => $password) {
    $hash = md5($password); // Simple md5 hash
    $sql = "UPDATE users SET password_hash = '$hash' WHERE username = '$username'";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>✅ Password reset for <strong>$username</strong> (password: $password)</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed for $username: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Now try logging in:</strong> <a href='login.php'>Login</a></p>";
?>