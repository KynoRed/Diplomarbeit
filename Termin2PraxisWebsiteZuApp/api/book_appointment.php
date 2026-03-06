<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Prüfen, ob Benutzer eingeloggt ist
if (!isLoggedIn() || !hasRole('patient')) {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    echo json_encode([
        'success' => false, 
        'message' => 'Bitte melden Sie sich an, um einen Termin zu buchen.',
        'redirect' => 'login.php?redirect=index.php&book_appointment=' . $appointment_id
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    $user_id = $_SESSION['user_id'];
    
    if (empty($appointment_id)) {
        echo json_encode(['success' => false, 'message' => 'Termin-ID fehlt']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Prüfen ob die Praxis Buchungen akzeptiert
    $stmt_check = $conn->prepare("SELECT p.accepting_bookings FROM appointments a 
                                   JOIN praxen p ON a.praxis_id = p.id 
                                   WHERE a.id = ?");
    $stmt_check->bind_param("i", $appointment_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        $praxis_data = $result->fetch_assoc();
        if (!$praxis_data['accepting_bookings']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Diese Praxis nimmt derzeit keine neuen Terminbuchungen an. Bitte wählen Sie eine andere Praxis.'
            ]);
            $stmt_check->close();
            $conn->close();
            exit();
        }
    }
    $stmt_check->close();
    
    // Termin buchen (Status auf 'angefragt' ändern und user_id setzen)
    $stmt = $conn->prepare("UPDATE appointments SET status = 'angefragt', user_id = ? WHERE id = ? AND status = 'frei'");
    $stmt->bind_param("ii", $user_id, $appointment_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Termin erfolgreich gebucht. Warten auf Bestätigung des Arztes.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Termin konnte nicht gebucht werden. Möglicherweise ist er nicht mehr verfügbar.']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
}
?>
