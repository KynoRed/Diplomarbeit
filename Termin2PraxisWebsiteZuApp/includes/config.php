<?php
// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'termin2praxis');

// Datenbankverbindung erstellen
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Verbindung fehlgeschlagen: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Session starten
session_start();

// Hilfsfunktion: Prüfen ob Benutzer eingeloggt ist
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hilfsfunktion: Prüfen ob Benutzer eine bestimmte Rolle hat
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Hilfsfunktion: Weiterleitung wenn nicht eingeloggt
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Hilfsfunktion: Weiterleitung wenn falsche Rolle
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Geocoding: Adresse in GPS-Koordinaten umwandeln
 * Verwendet Nominatim (OpenStreetMap) API - kostenlos und ohne API-Key
 * 
 * @param string $adresse Straße und Hausnummer
 * @param string $plz Postleitzahl
 * @param string $stadt Stadt
 * @return array|null ['lat' => float, 'lon' => float] oder null bei Fehler
 */
function geocodeAddress($adresse, $plz, $stadt) {
    // Mindestens PLZ und Stadt müssen vorhanden sein
    if (empty(trim($plz)) || empty(trim($stadt))) {
        error_log("Geocoding: PLZ oder Stadt fehlt - PLZ: '$plz', Stadt: '$stadt'");
        return null;
    }
    
    // User-Agent setzen (erforderlich für Nominatim)
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Termin2Praxis/1.0\r\n",
            'timeout' => 10 // 10 Sekunden Timeout
        ]
    ]);
    
    // Österreich oder Deutschland bestimmen (anhand PLZ)
    $country = 'Deutschland';
    if (strlen($plz) == 4 || (strlen($plz) == 5 && intval($plz) >= 6000 && intval($plz) <= 9999)) {
        $country = 'Österreich';
    }
    
    $attempts = [];
    
    // Versuch 1: Vollständige Adresse
    if (!empty(trim($adresse))) {
        $attempts[] = trim($adresse . ', ' . $plz . ' ' . $stadt . ', ' . $country);
        $attempts[] = trim($adresse . ' ' . $plz . ' ' . $stadt . ' ' . $country);
    }
    
    // Versuch 2: Ohne Hausnummer (falls in der Adresse enthalten)
    if (!empty(trim($adresse))) {
        $street_only = preg_replace('/\d+.*$/', '', $adresse); // Entferne Nummern am Ende
        if (trim($street_only) != trim($adresse)) {
            $attempts[] = trim($street_only . ', ' . $plz . ' ' . $stadt . ', ' . $country);
        }
    }
    
    // Versuch 3: Nur PLZ und Stadt
    $attempts[] = trim($plz . ' ' . $stadt . ', ' . $country);
    $attempts[] = trim($plz . ' ' . $stadt);
    
    // Versuche alle Varianten
    foreach ($attempts as $index => $full_address) {
        $encoded_address = urlencode($full_address);
        $url = "https://nominatim.openstreetmap.org/search?q={$encoded_address}&format=json&limit=1&countrycodes=de,at,ch&addressdetails=1";
        
        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                // Prüfen ob Ergebnis vorhanden
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    $coords = [
                        'lat' => floatval($data[0]['lat']),
                        'lon' => floatval($data[0]['lon'])
                    ];
                    
                    // Erfolgreiche Geocodierung loggen
                    error_log("Geocoding erfolgreich (Versuch " . ($index + 1) . "): '$full_address' -> Lat: {$coords['lat']}, Lon: {$coords['lon']}");
                    
                    return $coords;
                }
            }
            
            // Kurze Pause zwischen Anfragen (Nominatim Rate Limit)
            usleep(250000); // 250ms Pause
            
        } catch (Exception $e) {
            error_log("Geocoding Fehler bei Versuch " . ($index + 1) . ": " . $e->getMessage() . " für: " . $full_address);
            continue;
        }
    }
    
    // Alle Versuche fehlgeschlagen
    error_log("Geocoding: Alle Versuche fehlgeschlagen für Adresse: '$adresse', PLZ: '$plz', Stadt: '$stadt'");
    return null;
}
?>
