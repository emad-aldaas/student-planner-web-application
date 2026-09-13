<?php
$servername = "localhost"; // host
$username = "root"; // MySQL user
$password = ""; // MySQL password
$dbname = "student_planner"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>