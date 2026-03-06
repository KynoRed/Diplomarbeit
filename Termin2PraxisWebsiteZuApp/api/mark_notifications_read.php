<?php
require_once '../includes/config.php';
requireRole('patient');

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Alle Benachrichtigungen (bestätigt, abgelehnt, storniert) als gelesen markieren
$sql = "UPDATE appointments SET is_read = TRUE WHERE user_id = ? AND status IN ('bestätigt', 'abgelehnt', 'storniert') AND is_read = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Benachrichtigungen als gelesen markiert'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Markieren der Benachrichtigungen'
    ]);
}

$stmt->close();
$conn->close();
