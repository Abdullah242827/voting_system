<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

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
