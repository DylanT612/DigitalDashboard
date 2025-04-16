<?php
require 'includes/functions.php';

header('Content-Type: application/json');

try {
    $events = fetchEvents($conn);
    $sharedEvents = fetchSharedEvents($conn);
    $data = [];

    foreach ($events as $event) {
        $created_by = fetchUsername($conn, $event['created_by']);
        $friends = fetchFriends($conn, $event['id']);
        $data[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'created_by' => $event['created_by'],
            'created_by_username' => $created_by,
            'user_created' => true,
            'friends' => $friends
        ];
    }

    foreach ($sharedEvents as $event) {
        $created_by = fetchUsername($conn, $event['created_by']);
        $friends = fetchFriends($conn, $event['id']);
        $data[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'created_by' => $event['created_by'],
            'created_by_username' => $created_by,
            'user_created' => false,
            'friends' => $friends
        ];
    }

    echo json_encode($data);
    exit;
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
