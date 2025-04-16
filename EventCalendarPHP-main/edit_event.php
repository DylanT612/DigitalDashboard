<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $title = $_POST['title'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    $created = $_SESSION['user_id'];

    if (isset($_POST['created_by'])) {
        $created = $_POST['created_by'];
    }

    try {
        $stmt = $conn->prepare("UPDATE events SET title = ?, start_datetime = ?, end_datetime = ?, created_by = ? WHERE id = ?");
        $stmt->execute([$title, $start, $end, $created, $id]);

        // ✅ Send a success response back
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        // ❌ On failure, return error message
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ❌ If not a proper POST request, fail
echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>
