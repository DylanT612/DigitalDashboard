<?php
session_start();
require 'includes/db.php';

if (isset($_POST['friends'])) {
    foreach ($_POST['friends'] as $friend) {
        $stmt = $conn->prepare("INSERT INTO shared_events (id_event, id_user) VALUES (?, ?)");
        if ($stmt->execute([$_SESSION['currentEventId'], $friend])) {
            error_log("Successfully added friend with ID: $friend to event ID: " . $_SESSION['currentEventId']);
        } else {
            error_log("Failed to add friend with ID: $friend to event ID: " . $_SESSION['currentEventId']);
        }
    }
} else {
    error_log("No friends data found in POST request.");
}
?>