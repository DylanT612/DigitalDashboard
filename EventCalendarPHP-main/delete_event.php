<?php
header('Content-Type: application/json'); // ✅ Always set this

require 'includes/db.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]); // ✅ Return success response
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
}
?>

