<?php
session_start();
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

// Get variable
$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];

// Special case for friend requesting yourself
if ($sender_id == $receiver_id) {
    die("You cannot send a friend request to yourself.");
}
// See if request was already sent
$stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo "Request already sent.";
} else {
    // Otherwise create a new friend request
    $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $sender_id, $receiver_id);
    if ($stmt->execute()) {
        echo "Friend request sent!";
    } else {
        echo "Error sending request.";
    }
}
?>
