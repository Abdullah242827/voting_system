<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
if ($stmt->fetch()) {
    echo json_encode(['success' => true, 'username' => $username]);
} else {
    echo json_encode(['success' => false]);
}
$stmt->close();
$conn->close();
?>
