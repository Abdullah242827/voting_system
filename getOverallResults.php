<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Fetch elections and candidates with vote counts
$sql = "SELECT e.election_id, e.title, e.description, e.start_date, e.end_date, e.status,
               c.candidate_id, c.name as candidate_name, c.description as candidate_description,
               COUNT(v.id) as votes_count
        FROM Elections e
        LEFT JOIN Candidates c ON e.election_id = c.election_id
        LEFT JOIN Votes v ON c.candidate_id = v.candidate_id
        GROUP BY e.election_id, c.candidate_id
        ORDER BY e.election_id, c.candidate_id";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([]);
    exit;
}

$elections = [];
while ($row = $result->fetch_assoc()) {
    $election_id = $row['election_id'];
    if (!isset($elections[$election_id])) {
        $elections[$election_id] = [
            'election_id' => $election_id,
            'title' => $row['title'],
            'description' => $row['description'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => $row['status'],
            'candidates' => []
        ];
    }
    if ($row['candidate_id'] !== null) {
        $elections[$election_id]['candidates'][] = [
            'candidate_id' => $row['candidate_id'],
            'name' => $row['candidate_name'],
            'description' => $row['candidate_description'],
            'votes' => intval($row['votes_count'])
        ];
    }
}

echo json_encode(array_values($elections));

$conn->close();
?>
