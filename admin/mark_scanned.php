<?php
session_start();
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'] ?? null;

    if (!$ticket_id) {
        echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
        exit();
    }

    try {
        // Update the ticket as scanned
        $stmt = $conn->prepare("UPDATE tickets SET is_scanned = 1, scanned_at = NOW() WHERE id = :ticket_id");
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->execute();

        // Fetch the updated ticket data
        $stmt = $conn->prepare("SELECT scanned_at FROM tickets WHERE id = :ticket_id");
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->execute();
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'scanned_at' => $ticket['scanned_at']]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}