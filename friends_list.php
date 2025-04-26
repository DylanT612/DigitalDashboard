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
    
$user_id = $_SESSION['user_id'];

// Display the users friends 
$stmt = $conn->prepare("
    SELECT DISTINCT u.username FROM friends f 
    JOIN users u ON ((f.user1_id = ? AND u.id = f.user2_id) OR
    (f.user2_id = ? AND u.id = f.user1_id))


");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
// Display each user
$friends = [];
while ($row = $result->fetch_assoc()) {
    $friends[] = $row;
}
echo json_encode($friends);
exit();
?>