<?php
header('Content-Type: application/json');

require 'includes/db.php';

if (isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM event_friends WHERE event_id = ?");
        $stmt->execute([$event_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No event_id provided']);
}
?>
