<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = '';
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
$offset = ($page - 1) * $perPage;

// Count total voters for pagination info
$countResult = $conn->query("SELECT COUNT(*) as total FROM Users WHERE role='voter'" . ($search !== '' ? " AND username LIKE '%$search%'" : ""));
$totalVoters = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;

// Allow sorting by username or registration date, and allow direction toggle
$allowedSort = ['username', 'created_at'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'username';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';

// Main query with LIMIT for pagination and sorting
$sql = "SELECT username, created_at FROM Users WHERE role='voter'";
if ($search !== '') {
    $sql .= " AND username LIKE '%$search%'";
}
$sql .= " ORDER BY $sort $order LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);
$voters = [];
while ($row = $result->fetch_assoc()) {
    $voters[] = [
        'username' => $row['username'],
        'created_at' => $row['created_at']
    ];
}
echo json_encode([
    'success' => true,
    'voters' => $voters,
    'total' => $totalVoters,
    'page' => $page,
    'perPage' => $perPage,
    'sort' => $sort,
    'order' => $order
]);
$conn->close();
?>
