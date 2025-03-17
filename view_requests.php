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
// Get pending request for your user id
$stmt = $conn->prepare("
    SELECT fr.id, u.username FROM friend_requests fr 
    JOIN users u ON fr.sender_id = u.id 
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Display pending requests
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
echo json_encode($requests);
exit();
?>

