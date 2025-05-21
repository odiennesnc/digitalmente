<?php
// Database configuration
$db_host = "localhost";
$db_user = "myodnit_digital";     // Replace with your actual database username
$db_pass = "G@b@2808";         // Replace with your actual database password
$db_name = "myodnit_digitalmente";

error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

?>