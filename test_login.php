<?php
// test_login.php - Quick test file

require_once 'includes/config.php';
require_once 'includes/db_connection.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>Database Connection Test</h1>";

if ($conn) {
    echo "<p style='color:green;'>✅ Database connected successfully!</p>";
    
    // Test query
    $result = $conn->query("SELECT * FROM users");
    if ($result) {
        echo "<p>✅ Users table exists. Found " . $result->num_rows . " users.</p>";
        
        echo "<h3>Users:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row['username'] . " - " . $row['first_name'] . " " . $row['last_name'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Database connection failed!</p>";
}
?>