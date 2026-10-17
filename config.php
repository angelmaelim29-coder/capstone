<?php
// includes/config.php

// Database configuration - CORRECT NAME
define('DB_HOST', 'localhost');
define('DB_USER', if0_43020735);
define('DB_PASS', 'NEzkh9Hxl3');
define('DB_NAME', 'trentians_gradehub');

// Application configuration
define('APP_NAME', 'TIFTCI Grade Portal');
define('CURRENT_SEMESTER', '1st Semester');
define('CURRENT_SCHOOL_YEAR', '2025-2026');
define('PASSING_GRADE', 3.0);
define('DEFAULT_ACCESS_DURATION', 7);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>