<?php
require_once '../includes/config.php';

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isLoggedIn() || !hasRole('patient')) {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

// POST-Daten empfangen
$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Termin-ID']);
    exit;
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Überprüfen, ob der Termin dem Benutzer gehört
$sql_check = "SELECT * FROM appointments WHERE id = ? AND user_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $appointment_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Termin nicht gefunden oder keine Berechtigung']);
    $stmt_check->close();
    $conn->close();
    exit;
}

$appointment = $result_check->fetch_assoc();
$stmt_check->close();

// Nur abgelehnte, stornierte und bestätigte Termine können gelöscht werden
if (!in_array($appointment['status'], ['abgelehnt', 'storniert', 'bestätigt'])) {
    echo json_encode(['success' => false, 'message' => 'Dieser Termin kann nicht gelöscht werden']);
    $conn->close();
    exit;
}

// Termin löschen
$sql_delete = "DELETE FROM appointments WHERE id = ? AND user_id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("ii", $appointment_id, $user_id);

if ($stmt_delete->execute()) {
    echo json_encode(['success' => true, 'message' => 'Benachrichtigung erfolgreich gelöscht']);
} else {
    echo json_encode(['success' => false, 'message' => 'Fehler beim Löschen der Benachrichtigung']);
}

$stmt_delete->close();
$conn->close();
?>
