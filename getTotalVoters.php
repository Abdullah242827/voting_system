<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM Users WHERE role='voter'");
$row = $result ? $result->fetch_assoc() : null;
$count = $row ? intval($row['cnt']) : 0;
echo json_encode(['success' => true, 'count' => $count]);
$conn->close();
?>
