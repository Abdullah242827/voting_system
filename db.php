<?php
// Database connection settings
$host = 'localhost';
$dbname = 'voting_system'; // fixed to match your schema
$user = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set charset to utf8
$conn->set_charset("utf8");
?>
