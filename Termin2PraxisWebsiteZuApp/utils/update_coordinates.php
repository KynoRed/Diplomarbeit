<?php
/**
 * Hilfsskript: GPS-Koordinaten für Praxen ohne Koordinaten nachtragen
 * Dieses Skript durchsucht alle Praxen in der Datenbank und geocodiert
 * diejenigen, die noch keine latitude/longitude haben.
 */

require_once '../includes/config.php';

// Nur als Administrator/Arzt ausführbar
requireLogin();
if (!hasRole('arzt') && !hasRole('praxisbesitzer')) {
    die("Keine Berechtigung");
}

$conn = getDBConnection();

// Alle Praxen ohne GPS-Koordinaten finden
$sql = "SELECT id, name, adresse, plz, stadt FROM praxen 
        WHERE (latitude IS NULL OR longitude IS NULL) 
        AND adresse IS NOT NULL AND plz IS NOT NULL AND stadt IS NOT NULL";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<h2>✅ Alle Praxen haben bereits GPS-Koordinaten</h2>";
    echo "<p>Es gibt keine Praxen ohne Koordinaten.</p>";
    echo "<a href='../index.php' class='btn btn-primary'>Zurück zur Startseite</a>";
    exit;
}

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>GPS-Koordinaten aktualisieren - Termin2Praxis</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h1 class='mb-4'>🗺️ GPS-Koordinaten aktualisieren</h1>
        <p class='lead'>Folgende Praxen haben keine GPS-Koordinaten und werden jetzt geocodiert:</p>
        <div class='card'><div class='card-body'>";

$updated_count = 0;
$failed_count = 0;
$failed_praxen = [];

while ($row = $result->fetch_assoc()) {
    $praxis_id = $row['id'];
    $name = htmlspecialchars($row['name']);
    $adresse = $row['adresse'];
    $plz = $row['plz'];
    $stadt = $row['stadt'];
    
    echo "<div class='mb-3'>";
    echo "<strong>{$name}</strong><br>";
    echo "<small class='text-muted'>{$adresse}, {$plz} {$stadt}</small><br>";
    
    // Geocoding durchführen
    $coords = geocodeAddress($adresse, $plz, $stadt);
    
    if ($coords !== null) {
        // Koordinaten in Datenbank speichern
        $update_sql = "UPDATE praxen SET latitude = ?, longitude = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ddi", $coords['lat'], $coords['lon'], $praxis_id);
        
        if ($stmt->execute()) {
            echo "<span class='text-success'>✅ Erfolgreich: Lat {$coords['lat']}, Lon {$coords['lon']}</span>";
            $updated_count++;
        } else {
            echo "<span class='text-danger'>❌ Fehler beim Speichern in Datenbank</span>";
            $failed_count++;
            $failed_praxen[] = $name;
        }
        
        $stmt->close();
    } else {
        echo "<span class='text-warning'>⚠️ Geocoding fehlgeschlagen - keine Koordinaten gefunden</span>";
        $failed_count++;
        $failed_praxen[] = $name;
    }
    
    echo "</div>";
    
    // Kurze Pause zwischen API-Anfragen (Nominatim-Limit: max 1 Request/Sekunde)
    sleep(1);
}

echo "</div></div>";

echo "<div class='alert alert-info mt-4'>";
echo "<h4>📊 Zusammenfassung:</h4>";
echo "<ul>";
echo "<li><strong>{$updated_count}</strong> Praxen erfolgreich aktualisiert</li>";
if ($failed_count > 0) {
    echo "<li><strong>{$failed_count}</strong> Praxen fehlgeschlagen:</li>";
    echo "<ul>";
    foreach ($failed_praxen as $failed_name) {
        echo "<li>" . htmlspecialchars($failed_name) . "</li>";
    }
    echo "</ul>";
}
echo "</ul>";
echo "</div>";

echo "<a href='../index.php' class='btn btn-primary'>Zurück zur Startseite</a>";
echo "<a href='../dashboards/dashboard_praxisbesitzer.php' class='btn btn-secondary ms-2'>Zum Praxisbesitzer Dashboard</a>";

echo "</div>
</body>
</html>";

$conn->close();
?>
