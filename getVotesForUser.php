<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username'])) {
    echo json_encode(['success' => false, 'message' => 'Username required']);
    exit;
}

$username = $conn->real_escape_string($data['username']);

// Get user id
$sql = "SELECT user_id FROM Users WHERE username = '$username'";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$row = $result->fetch_assoc();
$user_id = $row['user_id'];

$sql = "SELECT v.vote_id, v.vote_time, e.election_id, e.title as election_title, c.candidate_id, c.name as candidate_name
        FROM Votes v
        JOIN Elections e ON v.election_id = e.election_id
        JOIN Candidates c ON v.candidate_id = c.candidate_id
        WHERE v.user_id = ?
        ORDER BY v.vote_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$votes = [];
while ($row = $result->fetch_assoc()) {
    $votes[] = [
        'vote_id' => $row['vote_id'],
        'vote_time' => $row['vote_time'],
        'election' => [
            'election_id' => $row['election_id'],
            'title' => $row['election_title']
        ],
        'candidate' => [
            'candidate_id' => $row['candidate_id'],
            'name' => $row['candidate_name']
        ]
    ];
}

echo json_encode(['success' => true, 'votes' => $votes]);

$stmt->close();
$conn->close();
?>
