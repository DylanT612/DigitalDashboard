<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

// Ensure PDO throws exceptions instead of silent errors
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function fetchEvents($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM events WHERE created_by = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Fetch Events Error: ' . $e->getMessage()]);
        exit;
    }
}

function fetchSharedEvents($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM events JOIN shared_events ON events.id = shared_events.id_event WHERE shared_events.id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Fetch Shared Events Error: ' . $e->getMessage()]);
        exit;
    }
}

function fetchFriends($conn, $event_id) {
    $stmt = $conn->prepare("
        SELECT se.id_user AS id, u.username 
        FROM shared_events se
        JOIN users u ON se.id_user = u.id
        WHERE se.id_event = ?
    ");
    $stmt->execute([$event_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchUsername($conn, $id) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['username'] : "Unknown";
}
?>

