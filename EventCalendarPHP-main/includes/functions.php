<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include the database connection
require 'db.php';

// Function to fetch all events from the database
function fetchEvents($conn) {
    // Prepare and execute the SQL statement to select all events
    $stmt = $conn->prepare("SELECT * FROM events WHERE created_by = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Return the fetched events as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSharedEvents($conn) {
    // Prepare and execute the SQL statement to select all events
    $stmt = $conn->prepare("SELECT * FROM events JOIN shared_events ON events.id = shared_events.id_event WHERE shared_events.id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Return the fetched events as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchFriends($conn, $id) {
    // Prepare and execute the SQL statement to select all events
    $stmt = $conn->prepare("SELECT users.id_user, users.username FROM users JOIN shared_events ON users.id_user = shared_events.id_user WHERE shared_events.id_event = ?");
    $stmt->execute([$id]);

    // Return the fetched events as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
