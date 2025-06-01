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

if (!isset($data['title'], $data['description'], $data['start_date'], $data['end_date'], $data['candidates']) || !is_array($data['candidates'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$title = $conn->real_escape_string($data['title']);
$description = $conn->real_escape_string($data['description']);

// Force start_date to now and status to 'ongoing'
$start_date = date('Y-m-d H:i:s');
$end_date = $conn->real_escape_string($data['end_date']);
$candidates = $data['candidates'];
$status = 'ongoing';

// Insert election with forced status and start_date
$sql = "INSERT INTO Elections (title, description, start_date, end_date, status) VALUES ('$title', '$description', '$start_date', '$end_date', '$status')";
if ($conn->query($sql) === TRUE) {
    $election_id = $conn->insert_id;

    // Insert candidates
    $stmt = $conn->prepare("INSERT INTO Candidates (election_id, name, description) VALUES (?, ?, ?)");
    foreach ($candidates as $candidate) {
        $name = $candidate['name'];
        $desc = $candidate['description'];
        $stmt->bind_param("iss", $election_id, $name, $desc);
        $stmt->execute();
    }
    $stmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$conn->close();
?>
