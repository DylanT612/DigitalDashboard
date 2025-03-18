<?php
session_start();
// Include the database connection
require 'includes/db.php';

// Check if the event ID and other event details are set in the POST request
if (isset($_POST['id'])) {
    $id = $_POST['id']; // Get the event ID
    $title = $_POST['title']; // Get the event title
    $start = $_POST['start']; // Get the event start date and time
    $end = $_POST['end']; // Get the event end date and time
    $created = $_SESSION['user_id'];
    if(isset($_POST['created_by'])) {
        $created = $_POST['created_by'];
    }
    // Prepare and execute the SQL statement to update the event
    $stmt = $conn->prepare("UPDATE events SET title = ?, start_datetime = ?, end_datetime = ?, created_by = ? WHERE id = ?");
    $stmt->execute([$title, $start, $end, $created, $id]);
    $_SESSION['currentEventId'] = $id;
}
?>
