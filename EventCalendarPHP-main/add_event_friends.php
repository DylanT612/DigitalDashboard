<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

// ✅ Make sure required fields exist
if (!isset($_POST['currentEventId']) || !isset($_POST['friends'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$eventId = $_POST['currentEventId'];
$friends = $_POST['friends'];

try {
    $stmt = $conn->prepare("INSERT INTO shared_events (id_event, id_user) VALUES (?, ?)");

    foreach ($friends as $friendId) {
        $stmt->execute([$eventId, $friendId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>