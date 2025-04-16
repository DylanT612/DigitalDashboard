<?php
session_start();
header('Content-Type: text/html'); // ✅ We're now returning HTML

$host = 'localhost';
$user = 'root';
$pass = 'mysql'; 
$dbname = 'dashboardDB';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT fr.id, u.username FROM friend_requests fr 
    JOIN users u ON fr.sender_id = u.id 
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Output HTML directly
if ($result->num_rows === 0) {
    echo "<p>No pending requests.</p>";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "<div style='margin-bottom:10px;'>
                <strong>{$row['username']}</strong>
                <button onclick=\"handleRequest({$row['id']}, 'accept')\">Accept</button>
                <button onclick=\"handleRequest({$row['id']}, 'decline')\">Decline</button>
              </div>";
    }
}
?>
