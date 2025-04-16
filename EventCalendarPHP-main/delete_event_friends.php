<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

// ✅ Check if required data is sent
if (!isset($_POST['event_id']) || !isset($_POST['friends'])) {
    echo json_encode(['success' => false, 'error' => 'Missing event ID or friends list']);
    exit;
}

$eventId = $_POST['event_id'];
$friends = $_POST['friends'];

try {
    $stmt = $conn->prepare("DELETE FROM event_friends WHERE id_event = ? AND id_user = ?");
    
    foreach ($friends as $friendId) {
        $stmt->execute([$eventId, $friendId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
