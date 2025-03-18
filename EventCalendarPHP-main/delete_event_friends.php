<?php
session_start();
require 'includes/db.php';

if (isset($_POST['friends'])) {
    foreach ($_POST['friends'] as $friend) {
        $stmt = $conn->prepare("DELETE FROM shared_events WHERE id_event = ? AND id_user = ?");
        $stmt->execute([$_SESSION['currentEventId'], $friend]);
    }
}
?>