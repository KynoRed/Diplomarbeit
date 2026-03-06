<?php
/**
 * API Endpoint: Praxis löschen
 * Löscht eine Praxis des eingeloggten Arztes
 */

require_once '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// Prüfen ob POST-Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// Session und Rolle prüfen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'arzt') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$user_id = $_SESSION['user_id'];

// JSON-Daten lesen
$input = json_decode(file_get_contents('php://input'), true);
$praxis_id = intval($input['praxis_id'] ?? 0);

if ($praxis_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Praxis-ID']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Prüfen, ob die Praxis dem Arzt gehört
    $check_sql = "SELECT id, name FROM praxen WHERE id = ? AND owner_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $praxis_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Praxis nicht gefunden oder Sie sind nicht der Besitzer']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    
    $praxis = $result->fetch_assoc();
    $check_stmt->close();
    
    // Termine der Praxis löschen (Foreign Key Constraint würde sonst fehlschlagen)
    $delete_appointments_sql = "DELETE FROM appointments WHERE praxis_id = ?";
    $delete_appointments_stmt = $conn->prepare($delete_appointments_sql);
    $delete_appointments_stmt->bind_param("i", $praxis_id);
    $delete_appointments_stmt->execute();
    $deleted_appointments = $delete_appointments_stmt->affected_rows;
    $delete_appointments_stmt->close();
    
    // Praxis löschen
    $delete_sql = "DELETE FROM praxen WHERE id = ? AND owner_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $praxis_id, $user_id);
    
    if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Praxis erfolgreich gelöscht',
            'deleted_appointments' => $deleted_appointments
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Löschen der Praxis']);
    }
    
    $delete_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>
