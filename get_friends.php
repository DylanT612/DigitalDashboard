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
    die('Database connection failed: ' . $conn->connect_error);
    }
    
// Get user_id
$user_id = $_SESSION['user_id'];
// Fetch all friends of the current user
$query = "SELECT user1_id FROM friends WHERE user2_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
// Fetch all friends of the user_id
$friends = [];
while ($row = $result->fetch_assoc()) {
    $friends[] = $row['user1_id'];
}
echo json_encode($friends);
?>