<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

// Prüfen ob Benutzer eingeloggt ist und Patient-Rolle hat
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie müssen angemeldet sein, um einen Termin anzufragen.'
    ]);
    exit;
}

if (!hasRole('patient')) {
    echo json_encode([
        'success' => false,
        'message' => 'Nur Patienten können Termine anfragen.'
    ]);
    exit;
}

// POST-Daten validieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Anfragemethode.'
    ]);
    exit;
}

$praxis_id = isset($_POST['praxis_id']) ? intval($_POST['praxis_id']) : 0;
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$time = isset($_POST['time']) ? trim($_POST['time']) : '';
$duration = isset($_POST['duration']) && !empty($_POST['duration']) ? intval($_POST['duration']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
$user_id = $_SESSION['user_id'];

// Validierung
if ($praxis_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Praxis-ID.'
    ]);
    exit;
}

if (empty($date) || empty($time)) {
    echo json_encode([
        'success' => false,
        'message' => 'Datum und Uhrzeit sind erforderlich.'
    ]);
    exit;
}

if (empty($description)) {
    echo json_encode([
        'success' => false,
        'message' => 'Bitte geben Sie den Grund für den Termin an.'
    ]);
    exit;
}

// Datum validieren (muss in der Zukunft liegen)
$today = date('Y-m-d');
if ($date < $today) {
    echo json_encode([
        'success' => false,
        'message' => 'Das Datum darf nicht in der Vergangenheit liegen.'
    ]);
    exit;
}

$conn = getDBConnection();

// Prüfen ob Praxis existiert
$sql = "SELECT id, accepting_bookings FROM praxen WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $praxis_id);
$stmt->execute();
$result = $stmt->get_result();
$praxis = $result->fetch_assoc();
$stmt->close();

if (!$praxis) {
    echo json_encode([
        'success' => false,
        'message' => 'Praxis nicht gefunden.'
    ]);
    $conn->close();
    exit;
}

// Prüfen ob Praxis Buchungen akzeptiert
if (!$praxis['accepting_bookings']) {
    echo json_encode([
        'success' => false,
        'message' => 'Diese Praxis nimmt derzeit keine Terminanfragen entgegen.'
    ]);
    $conn->close();
    exit;
}

// Prüfen ob Patient bereits einen Termin zur gleichen Zeit bei dieser Praxis angefragt hat
$sql = "SELECT id FROM appointments 
        WHERE user_id = ? 
        AND praxis_id = ? 
        AND date = ? 
        AND time = ? 
        AND status IN ('angefragt', 'bestätigt')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $user_id, $praxis_id, $date, $time);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie haben bereits einen Termin zu dieser Zeit bei dieser Praxis angefragt oder bestätigt.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Notizen mit Beschreibung kombinieren falls vorhanden
$combined_description = $description;
if (!empty($notes)) {
    $combined_description .= ' | Anmerkungen: ' . $notes;
}

// Terminanfrage erstellen
$sql = "INSERT INTO appointments (praxis_id, user_id, date, time, duration, description, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'angefragt', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissis", $praxis_id, $user_id, $date, $time, $duration, $combined_description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Ihre Terminanfrage wurde erfolgreich gesendet! Der Arzt wird Ihre Anfrage prüfen und Sie benachrichtigen.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Senden der Terminanfrage. Bitte versuchen Sie es später erneut.'
    ]);
}

$stmt->close();
$conn->close();
