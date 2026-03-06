<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Für Patienten: Vergangene Termine löschen
    if (isLoggedIn() && hasRole('patient')) {
        $user_id = $_SESSION['user_id'];
        $conn = getDBConnection();
        
        try {
            // Alle vergangenen Termine löschen
            if (isset($_POST['delete_all_past']) && $_POST['delete_all_past'] === 'true') {
                $sql = "DELETE FROM appointments WHERE user_id = ? AND date < CURDATE() AND status IN ('angefragt', 'bestätigt', 'abgelehnt', 'storniert')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $deleted_count = $stmt->affected_rows;
                    $stmt->close();
                    $conn->close();
                    echo json_encode([
                        'success' => true, 
                        'message' => "Es wurden $deleted_count vergangene Termine gelöscht."
                    ]);
                    exit;
                } else {
                    throw new Exception('Fehler beim Löschen der Termine');
                }
            }
            
            // Einzelnen vergangenen Termin löschen
            if (isset($_POST['appointment_id']) && !empty($_POST['appointment_id'])) {
                $appointment_id = intval($_POST['appointment_id']);
                
                // Prüfen, ob der Termin dem Benutzer gehört und vergangen ist
                $sql = "SELECT * FROM appointments WHERE id = ? AND user_id = ? AND date < CURDATE()";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $appointment_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $stmt->close();
                    $conn->close();
                    echo json_encode(['success' => false, 'message' => 'Termin nicht gefunden oder kann nicht gelöscht werden']);
                    exit;
                }
                
                $stmt->close();
                
                // Termin löschen
                $sql = "DELETE FROM appointments WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $appointment_id, $user_id);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    echo json_encode(['success' => true, 'message' => 'Termin wurde erfolgreich gelöscht']);
                    exit;
                } else {
                    throw new Exception('Fehler beim Löschen des Termins');
                }
            }
            
        } catch (Exception $e) {
            if (isset($stmt)) $stmt->close();
            if (isset($conn)) $conn->close();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    // Für Ärzte: Termine ablehnen/stornieren
    if (!hasRole('arzt')) {
        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
        exit;
    }
    
    $appointment_id = $_POST['appointment_id'] ?? 0;
    $action = $_POST['action'] ?? 'delete'; // 'delete' oder 'reject'
    
    if (empty($appointment_id)) {
        echo json_encode(['success' => false, 'message' => 'Termin-ID fehlt']);
        exit();
    }
    
    $conn = getDBConnection();
    
    if ($action === 'reject') {
        // Termin ablehnen - Status auf 'abgelehnt' setzen, damit Patient Benachrichtigung sieht
        $stmt = $conn->prepare("UPDATE appointments SET status = 'abgelehnt' WHERE id = ? AND status IN ('angefragt', 'bestätigt')");
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Termin wurde abgelehnt. Patient wird benachrichtigt.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Termin konnte nicht abgelehnt werden.']);
        }
        $stmt->close();
    } else {
        // Termin stornieren - Status ändern, damit Patient Benachrichtigung erhält
        // Zuerst prüfen, ob der Termin existiert
        $check_stmt = $conn->prepare("SELECT id, status FROM appointments WHERE id = ?");
        $check_stmt->bind_param("i", $appointment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Termin wurde nicht gefunden.']);
            $check_stmt->close();
            $conn->close();
            exit();
        }
        
        $appointment = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // Jetzt stornieren - Status auf 'storniert' setzen, damit Patient Benachrichtigung sieht
        $stmt = $conn->prepare("UPDATE appointments SET status = 'storniert' WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Termin wurde storniert. Patient wird benachrichtigt.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Termin konnte nicht storniert werden.']);
        }
        $stmt->close();
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
}
?>
