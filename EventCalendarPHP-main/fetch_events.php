<?php
// Include the functions file to fetch events from the database
require 'includes/functions.php';

// Fetch all events from the database
$events = fetchEvents($conn);
$sharedEvents = fetchSharedEvents($conn);
$data = [];

// Prepare the event data for JSON encoding
foreach ($events as $event) {
    $created_by = fetchUsername($conn, $event['created_by']);
    $friends = fetchFriends($conn, $event['id']);
    $data[] = [
        'id' => $event['id'], // Event ID
        'title' => $event['title'], // Event title
        'start' => $event['start_datetime'], // Event start date and time
        'end' => $event['end_datetime'], // Event end date and time
        'created_by' => $event['created_by'], // Event creator id
        'created_by_username' => $created_by, // Event creator username
        'user_created' => true, // Event end date and time
        'friends' => $friends // Event friends
    ];
}

foreach ($sharedEvents as $event) {
    $created_by = fetchUsername($conn, $event['created_by']);
    $friends = fetchFriends($conn, $event['id']);
    $data[] = [
        'id' => $event['id'], // Event ID
        'title' => $event['title'], // Event title
        'start' => $event['start_datetime'], // Event start date and time
        'end' => $event['end_datetime'], // Event end date and time
        'created_by' => $event['created_by'], // Event creator id
        'created_by_username' => $created_by, // Event creator username
        'user_created' => false, // Event end date and time
        'friends' => $friends // Event friends
    ];
}

function fetchUsername($conn, $id) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['username'];
}

// Output the event data as a JSON string
echo json_encode($data);
?>
