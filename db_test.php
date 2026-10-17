<?php
// db_test.php - Test database connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

$host = 'localhost';
$user = if0_43020735;
$pass = 'NEzkh9Hxl3';
$dbname = 'grade_portal';

echo "<h3>Connecting to: $host / $dbname</h3>";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo "<p style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>MySQL is running in XAMPP</li>";
    echo "<li>Database '<strong>$dbname</strong>' exists</li>";
    echo "</ul>";
} else {
    echo "<p style='color:green;'>✅ Connected successfully!</p>";
    
    // Show tables
    $result = $conn->query("SHOW TABLES");
    if ($result && $result->num_rows > 0) {
        echo "<h3>Tables in database:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>⚠️ No tables found. Run the SQL script.</p>";
    }
    
    $conn->close();
}
?>