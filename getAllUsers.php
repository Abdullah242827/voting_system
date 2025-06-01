<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$result = $conn->query("SELECT username, role, created_at FROM Users ORDER BY created_at DESC");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'username' => $row['username'],
        'role' => $row['role'],
        'created_at' => $row['created_at']
    ];
}
echo json_encode(['success' => true, 'users' => $users]);
$conn->close();
?>
