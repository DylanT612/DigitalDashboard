<?php
session_start();
require 'includes/db.php';

if (isset($_POST['title'])) {
    $title = $_POST['title'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    $created = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO events (title, start_datetime, end_datetime, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $start, $end, $created]);
    
    $_SESSION['currentEventId'] = $conn->lastInsertId();
}
?>
