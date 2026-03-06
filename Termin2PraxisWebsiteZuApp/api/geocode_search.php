<?php
/**
 * API Proxy für Nominatim Geocoding
 * Vermeidet CORS-Probleme durch serverseitige Anfragen
 */

header('Content-Type: application/json; charset=utf-8');

// Nur GET-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET-Anfragen erlaubt']);
    exit;
}

// Query-Parameter holen
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// Wenn Query weniger als 3 Zeichen hat, leer zurückgeben
if (strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

// KEIN automatisches Hinzufügen von "Deutschland" mehr
// Nutzer können selbst Land eingeben oder PLZ nutzen

// Nominatim API URL - Suche in Deutschland, Österreich und Schweiz
$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'q' => $query,
    'format' => 'json',
    'limit' => 15,  // Mehr Ergebnisse für bessere Auswahl
    'countrycodes' => 'de,at,ch',  // Deutschland, Österreich, Schweiz
    'addressdetails' => 1
]);

// Context für die Anfrage (User-Agent erforderlich für Nominatim)
$context = stream_context_create([
    'http' => [
        'header' => "User-Agent: Termin2Praxis/1.0\r\n",
        'timeout' => 5
    ]
]);

try {
    // API-Anfrage durchführen
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("Geocode API-Anfrage fehlgeschlagen für: " . $query);
        echo json_encode([]);
        exit;
    }
    
    // Response direkt durchreichen
    echo $response;
    
} catch (Exception $e) {
    error_log("Geocode API Fehler: " . $e->getMessage());
    echo json_encode([]);
}
?>
