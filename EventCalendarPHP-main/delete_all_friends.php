<?php
session_start();
require 'includes/db.php';

if (isset($_POST['event_id'])) {
    $stmt = $conn->prepare("DELETE FROM shared_events WHERE id_event = ?");
    $stmt->execute([$_POST['event_id']]);
}
?>