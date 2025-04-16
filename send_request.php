<?php
session_start();
header('Content-Type: application/json');

// Database connection details
$host = '';
$user = '';
$pass = ''; 
$dbname = '';

// Database connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check for any database connection error
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get variable
$sender_id = $_SESSION['user_id'] ?? null;
$receiver_id = $_POST['receiver_id'] ?? null;

// Ensure both are present
if (!$sender_id || !$receiver_id) {
    echo json_encode(['success' => false, 'error' => 'Missing sender or receiver ID']);
    exit;
}

// Special case: can't friend yourself
if ($sender_id == $receiver_id) {
    echo json_encode(['success' => false, 'error' => 'You cannot send a friend request to yourself']);
    exit;
}

// Check if request already exists
$stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Request already sent']);
} else {
    // Create a new friend request
    $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $sender_id, $receiver_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error sending request']);
    }
}
?>
