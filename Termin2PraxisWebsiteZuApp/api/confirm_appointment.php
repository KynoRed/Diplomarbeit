<?php
require_once '../includes/config.php';
requireRole('arzt');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    
    if (empty($appointment_id)) {
        echo json_encode(['success' => false, 'message' => 'Termin-ID fehlt']);
        exit();
    }
    
    $conn = getDBConnection();
    $arzt_id = $_SESSION['user_id'];
    
    // Status auf 'bestätigt' ändern und Arzt-Info speichern
    $stmt = $conn->prepare("UPDATE appointments SET status = 'bestätigt', confirmed_by = ?, confirmed_at = NOW() WHERE id = ? AND status = 'angefragt'");
    $stmt->bind_param("ii", $arzt_id, $appointment_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Termin erfolgreich bestätigt']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Termin konnte nicht bestätigt werden']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
}
?>
