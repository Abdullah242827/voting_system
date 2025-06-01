<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['election_id'], $data['candidate_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$election_id = intval($data['election_id']);
$candidate_id = intval($data['candidate_id']);

// Check if election is ongoing or should be ongoing
$sql = "SELECT status, start_date, end_date FROM Elections WHERE election_id = $election_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Election not found']);
    exit;
}
$row = $result->fetch_assoc();

// Auto-update status if within date range
$now = date('Y-m-d H:i:s');
if ($row['status'] !== 'ongoing') {
    if ($now >= $row['start_date'] && $now <= $row['end_date']) {
        $conn->query("UPDATE Elections SET status = 'ongoing' WHERE election_id = $election_id");
        $row['status'] = 'ongoing';
    }
}

if ($row['status'] !== 'ongoing') {
    echo json_encode(['success' => false, 'message' => 'Election is not ongoing', 'debug' => $row]);
    exit;
}

// Check if user has already voted in this election
$sql = "SELECT vote_id FROM Votes WHERE user_id = $user_id AND election_id = $election_id";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already voted in this election']);
    exit;
}

// Check if candidate belongs to the election
$stmt_check = $conn->prepare("SELECT candidate_id FROM Candidates WHERE candidate_id = ? AND election_id = ?");
if (!$stmt_check) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed (candidate check): ' . $conn->error]);
    $conn->close();
    exit;
}
$stmt_check->bind_param("ii", $candidate_id, $election_id);
$stmt_check->execute();
$stmt_check->store_result();
if ($stmt_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid candidate for this election']);
    $stmt_check->close();
    $conn->close();
    exit;
}
$stmt_check->close();

// Insert vote
$vote_time = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO Votes (user_id, election_id, candidate_id, vote_time) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed (vote insert): ' . $conn->error]);
    $conn->close();
    exit;
}
$stmt->bind_param("iiis", $user_id, $election_id, $candidate_id, $vote_time);
if ($stmt->execute()) {
    // Log admin action (optional)
    $stmt_log = $conn->prepare("INSERT INTO AdminLogs (admin_id, action) VALUES (?, ?)");
    if ($stmt_log) {
        $admin_id = $user_id;
        $action = "User $user_id voted for candidate $candidate_id in election $election_id";
        $stmt_log->bind_param("is", $admin_id, $action);
        $stmt_log->execute();
        $stmt_log->close();
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error recording vote: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
