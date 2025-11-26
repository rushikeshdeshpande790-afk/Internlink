<?php
$servername = "localhost";
$username = "root";  // Default username for MySQL in XAMPP
$password = "";      // Default is empty for XAMPP
$dbname = "internlink_db"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
