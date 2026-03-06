<?php
require_once '../includes/config.php';
requireRole('arzt');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $duration = $_POST['duration'] ?? null;
    $description = $_POST['description'] ?? null;
    $multipleSlots = isset($_POST['multipleSlots']) && $_POST['multipleSlots'] === 'true';
    $slotCount = isset($_POST['slotCount']) ? intval($_POST['slotCount']) : 1;
    $slotInterval = isset($_POST['slotInterval']) ? intval($_POST['slotInterval']) : 15;
    $praxis_id = isset($_POST['praxis_id']) ? intval($_POST['praxis_id']) : null;
    
    if (empty($date) || empty($time)) {
        echo json_encode(['success' => false, 'message' => 'Datum und Uhrzeit sind erforderlich']);
        exit();
    }
    
    if (!$praxis_id) {
        echo json_encode(['success' => false, 'message' => 'Keine Praxis ausgewählt. Bitte wählen Sie eine Praxis aus.']);
        exit();
    }
    
    // Duration validieren (falls angegeben)
    if ($duration !== null && $duration !== '') {
        $duration = intval($duration);
        if ($duration <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ungültige Termindauer']);
            exit();
        }
    } else {
        $duration = null;
    }
    
    // Description bereinigen (falls angegeben)
    if ($description !== null && $description !== '') {
        $description = trim($description);
        if (strlen($description) > 255) {
            $description = substr($description, 0, 255);
        }
    } else {
        $description = null;
    }
    
    // Mehrfach-Termine validieren
    if ($multipleSlots) {
        if ($slotCount < 2 || $slotCount > 20) {
            echo json_encode(['success' => false, 'message' => 'Anzahl Termine muss zwischen 2 und 20 liegen']);
            exit();
        }
        if ($slotInterval < 5 || $slotInterval > 120) {
            echo json_encode(['success' => false, 'message' => 'Abstand muss zwischen 5 und 120 Minuten liegen']);
            exit();
        }
    } else {
        $slotCount = 1;
    }
    
    $conn = getDBConnection();
    $createdCount = 0;
    $errors = [];
    
    // Termine erstellen
    for ($i = 0; $i < $slotCount; $i++) {
        // Zeit berechnen für diesen Slot
        $currentTime = new DateTime($date . ' ' . $time);
        $currentTime->modify('+' . ($i * $slotInterval) . ' minutes');
        $currentDate = $currentTime->format('Y-m-d');
        $currentTimeStr = $currentTime->format('H:i:s');
        
        // Prüfen ob Termin bereits existiert
        $stmt = $conn->prepare("SELECT id FROM appointments WHERE date = ? AND time = ?");
        $stmt->bind_param("ss", $currentDate, $currentTimeStr);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Termin am " . $currentTime->format('d.m.Y H:i') . " Uhr existiert bereits";
            $stmt->close();
            continue;
        }
        
        $stmt->close();
        
        // Termin erstellen
        if ($duration !== null && $description !== null) {
            $stmt = $conn->prepare("INSERT INTO appointments (date, time, duration, description, status, praxis_id) VALUES (?, ?, ?, ?, 'frei', ?)");
            $stmt->bind_param("ssisi", $currentDate, $currentTimeStr, $duration, $description, $praxis_id);
        } elseif ($duration !== null) {
            $stmt = $conn->prepare("INSERT INTO appointments (date, time, duration, status, praxis_id) VALUES (?, ?, ?, 'frei', ?)");
            $stmt->bind_param("ssii", $currentDate, $currentTimeStr, $duration, $praxis_id);
        } elseif ($description !== null) {
            $stmt = $conn->prepare("INSERT INTO appointments (date, time, description, status, praxis_id) VALUES (?, ?, ?, 'frei', ?)");
            $stmt->bind_param("sssi", $currentDate, $currentTimeStr, $description, $praxis_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (date, time, status, praxis_id) VALUES (?, ?, 'frei', ?)");
            $stmt->bind_param("ssi", $currentDate, $currentTimeStr, $praxis_id);
        }
        
        if ($stmt->execute()) {
            $createdCount++;
        } else {
            $errors[] = "Fehler beim Erstellen des Termins am " . $currentTime->format('d.m.Y H:i') . " Uhr";
        }
        
        $stmt->close();
    }
    
    $conn->close();
    
    // Rückmeldung
    if ($createdCount > 0) {
        $message = $createdCount === 1 
            ? 'Termin erfolgreich erstellt' 
            : "$createdCount Termine erfolgreich erstellt";
        
        if (count($errors) > 0) {
            $message .= '. Hinweise: ' . implode(', ', $errors);
        }
        
        echo json_encode(['success' => true, 'message' => $message, 'count' => $createdCount]);
    } else {
        $message = count($errors) > 0 
            ? implode(', ', $errors) 
            : 'Fehler beim Erstellen der Termine';
        echo json_encode(['success' => false, 'message' => $message]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
}
?>
