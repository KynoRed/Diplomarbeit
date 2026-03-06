<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Nur für eingeloggte Ärzte
if (!isLoggedIn() || !hasRole('arzt')) {
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// POST-Daten validieren
if (!isset($_POST['praxis_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Parameter']);
    exit;
}

$praxis_id = intval($_POST['praxis_id']);
$status = $_POST['status'];

// Valide Status-Werte
$valid_statuses = ['low', 'medium', 'high'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Ungültiger Status']);
    exit;
}

// Prüfen ob Praxis dem Arzt gehört
$sql = "SELECT id FROM praxen WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $praxis_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Praxis nicht gefunden oder keine Berechtigung']);
    exit;
}
$stmt->close();

// Status aktualisieren
$sql = "UPDATE praxen SET occupancy_status = ? WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $status, $praxis_id, $user_id);

if ($stmt->execute()) {
    // Status-Namen für Anzeige
    $status_names = [
        'low' => 'Nicht überlaufen',
        'medium' => 'Mittel überlaufen',
        'high' => 'Stark überlaufen'
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Auslastungsstatus erfolgreich aktualisiert',
        'status' => $status,
        'status_name' => $status_names[$status]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Fehler beim Aktualisieren: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
