<?php
require_once '../includes/config.php';
requireRole('arzt');

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $praxis_id = isset($_POST['praxis_id']) ? intval($_POST['praxis_id']) : 0;
    $accepting = isset($_POST['accepting']) ? intval($_POST['accepting']) : 0;
    
    if ($praxis_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ungültige Praxis-ID']);
        exit;
    }
    
    // Prüfen ob die Praxis dem Arzt gehört
    $sql = "SELECT id FROM praxen WHERE id = ? AND owner_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $praxis_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sie haben keine Berechtigung für diese Praxis']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    
    // Buchungsstatus aktualisieren
    $sql = "UPDATE praxen SET accepting_bookings = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $accepting, $praxis_id);
    
    if ($stmt->execute()) {
        $message = $accepting ? 'Terminbuchungen wurden aktiviert' : 'Terminbuchungen wurden deaktiviert';
        echo json_encode(['success' => true, 'message' => $message, 'accepting' => $accepting]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Aktualisieren des Status']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
}

$conn->close();
?>
