<?php
session_start();
header('Content-Type: application/json');

// Check required POST data
if (!isset($_POST['action']) || !isset($_POST['request_id'])) {
    echo json_encode(["error" => "Missing POST data"]);
    exit;
}

// Database connection details
$host = '';
$user = '';
$pass = ''; 
$dbname = '';

// Database connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check for any database connection error
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Get variables
$user_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'];
// Accept or decline invitation
$action = $_POST['action'];
$stmt = $conn->prepare("SELECT sender_id, receiver_id FROM friend_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
file_put_contents("debug_request.txt", print_r([
    'request_id' => $request_id,
    'request' => $request,
    'session_user_id' => $user_id
], true));
// For a mistatch or error
if (!$request || $request['receiver_id'] != $user_id) {
    die(json_encode(["error" => "Invalid request."]));
}
// If they accept the friend request
if ($action === 'accept') {
    $stmt = $conn->prepare("INSERT INTO friends (user1_id, user2_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $request['sender_id'], $request['receiver_id']);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
// If they deny the friend request
} else {
    $stmt = $conn->prepare("UPDATE friend_requests SET status = 'declined' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
}
echo json_encode(["success" => true]);
?>
