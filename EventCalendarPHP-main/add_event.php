<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if (isset($_POST['title'], $_POST['start'], $_POST['end']) && isset($_SESSION['user_id'])) {
    $title = $_POST['title'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    $created = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO events (title, start_datetime, end_datetime, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $start, $end, $created]);
        $eventId = $conn->lastInsertId();
        echo json_encode(['success' => true, 'event_id' => $eventId]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>