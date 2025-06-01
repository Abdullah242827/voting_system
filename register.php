<?php  
header("Content-Type: application/json");  
ob_start();
ini_set('display_errors', 1);  
error_reporting(E_ALL);  

require_once 'db.php'; // Use shared db.php

$data = json_decode(file_get_contents("php://input"), true);  

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON or empty request."]);
    exit;
}

$username = trim($data['username'] ?? '');  
$password = trim($data['password'] ?? '');  
$role = $data['role'] ?? 'voter';  

if (!$username || !$password) {  
    echo json_encode(["success" => false, "message" => "Missing username or password."]);  
    exit;  
}  

$passwordHash = password_hash($password, PASSWORD_DEFAULT);  

$stmt = $conn->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)");  

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("sss", $username, $passwordHash, $role);  

if ($stmt->execute()) {  
    echo json_encode(["success" => true]);  
} else {  
    echo json_encode(["success" => false, "message" => $stmt->error]);  
}  

$stmt->close();  
$conn->close();  
ob_end_flush();
?>
