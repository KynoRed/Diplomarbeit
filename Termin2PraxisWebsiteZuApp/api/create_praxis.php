<?php
/**
 * API Endpoint: Neue Praxis erstellen
 * Erstellt eine neue Praxis für den eingeloggten Arzt
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

// Daten aus dem Formular holen
$name = trim($_POST['name'] ?? '');
$beschreibung = trim($_POST['beschreibung'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$plz = trim($_POST['plz'] ?? '');
$stadt = trim($_POST['stadt'] ?? '');
$telefon = trim($_POST['telefon'] ?? '');
$email = trim($_POST['email'] ?? '');
$spezialgebiet = trim($_POST['spezialgebiet'] ?? '');
$kategorie = trim($_POST['kategorie'] ?? '');
$versicherungsart = trim($_POST['versicherungsart'] ?? 'Beide');
$opening_time = trim($_POST['opening_time'] ?? '08:00:00');
$closing_time = trim($_POST['closing_time'] ?? '18:00:00');
$reachability_start = trim($_POST['reachability_start'] ?? '07:00:00');
$reachability_end = trim($_POST['reachability_end'] ?? '20:00:00');

// Validierung - Pflichtfelder
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Der Praxisname ist erforderlich']);
    exit;
}

if (strlen($name) > 200) {
    echo json_encode(['success' => false, 'message' => 'Der Praxisname darf maximal 200 Zeichen lang sein']);
    exit;
}

if (empty($spezialgebiet)) {
    echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie ein Spezialgebiet aus']);
    exit;
}

if (empty($adresse)) {
    echo json_encode(['success' => false, 'message' => 'Die Adresse (Straße und Hausnummer) ist erforderlich']);
    exit;
}

if (empty($plz)) {
    echo json_encode(['success' => false, 'message' => 'Die Postleitzahl ist erforderlich']);
    exit;
}

if (empty($stadt)) {
    echo json_encode(['success' => false, 'message' => 'Die Stadt ist erforderlich']);
    exit;
}

if (empty($telefon) && empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie mindestens eine Telefonnummer oder E-Mail-Adresse an']);
    exit;
}

// Bild verarbeiten
$bild_url = '';

// Option 1: Bild-URL wurde angegeben
if (!empty($_POST['bild_url'])) {
    $bild_url = trim($_POST['bild_url']);
    // Validiere URL
    if (!filter_var($bild_url, FILTER_VALIDATE_URL)) {
        $bild_url = ''; // Ungültige URL ignorieren
    }
}

// Option 2: Datei wurde hochgeladen (hat Priorität über URL)
if (isset($_FILES['praxis_bild']) && $_FILES['praxis_bild']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/praxen/';
    
    // Upload-Verzeichnis erstellen falls nicht vorhanden
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['praxis_bild'];
    $fileSize = $file['size'];
    $fileType = $file['type'];
    $tmpName = $file['tmp_name'];
    
    // Dateigröße prüfen (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Die Bilddatei ist zu groß. Maximale Größe: 5MB']);
        exit;
    }
    
    // Dateityp prüfen
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Nur JPG, PNG, GIF und WebP Bilder sind erlaubt']);
        exit;
    }
    
    // Dateiendung ermitteln
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $extension = 'jpg'; // Fallback
    }
    
    // Eindeutigen Dateinamen generieren
    $newFileName = 'praxis_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($tmpName, $uploadPath)) {
        $bild_url = 'uploads/praxen/' . $newFileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Hochladen des Bildes']);
        exit;
    }
}

// Geocoding durchführen um GPS-Koordinaten zu erhalten
$latitude = null;
$longitude = null;
$coords = geocodeAddress($adresse, $plz, $stadt);
if ($coords !== null) {
    $latitude = $coords['lat'];
    $longitude = $coords['lon'];
}

try {
    $conn = getDBConnection();
    
    // Praxis in die Datenbank einfügen (mit latitude/longitude)
    $sql = "INSERT INTO praxen (name, beschreibung, adresse, plz, stadt, telefon, email, spezialgebiet, kategorie, bild_url, owner_id, versicherungsart, opening_time, closing_time, reachability_start, reachability_end, latitude, longitude) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssisssssdd", 
        $name, 
        $beschreibung, 
        $adresse, 
        $plz, 
        $stadt, 
        $telefon, 
        $email, 
        $spezialgebiet, 
        $kategorie, 
        $bild_url, 
        $user_id,
        $versicherungsart,
        $opening_time,
        $closing_time,
        $reachability_start,
        $reachability_end,
        $latitude,
        $longitude
    );
    
    if ($stmt->execute()) {
        $praxis_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Praxis erfolgreich erstellt', 
            'praxis_id' => $praxis_id
        ]);
    } else {
        throw new Exception('Fehler beim Einfügen: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>
