<?php
session_start();
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');
$role = trim($data['role'] ?? '');

// Validate
if (!$username || !$password || !$role) {
    echo json_encode(["success" => false, "message" => "Missing input fields"]);
    exit;
}

// Check user
$stmt = $conn->prepare("SELECT user_id, password_hash, role FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$stmt->bind_result($userIdFromDb, $passwordHashFromDb, $roleFromDb);
$stmt->fetch();

if (password_verify($password, $passwordHashFromDb) && $role === $roleFromDb) {
    $_SESSION['role'] = $roleFromDb;
    $_SESSION['user_id'] = $userIdFromDb;
    echo json_encode(["success" => true, "role" => $roleFromDb]); // ✅ Include role in response
} else {
    echo json_encode(["success" => false, "message" => "Invalid credentials or role"]);
}

$stmt->close();
$conn->close();
?>
