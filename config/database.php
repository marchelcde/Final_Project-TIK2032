<?php
// config/database.php
// Location: FINAL_PROJECT-TIK2032/config/database.php

// Database configuration
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'kenola20'); // <--- CHANGE THIS to your actual MySQL root password
define('DB_NAME', 'e-lapor'); // Make sure this is also your actual database name
// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop script execution and display error
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8 for proper handling of various characters
$conn->set_charset("utf8mb4");

// You can uncomment the line below for debugging purposes during initial setup
// echo "DEBUG: Database connected successfully from database.php!<br>";
?>
