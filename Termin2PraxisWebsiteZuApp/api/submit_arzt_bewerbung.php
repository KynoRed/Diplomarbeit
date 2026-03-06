<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// Formulardaten validieren
$required_fields = ['praxisName', 'arztName', 'fachgebiet', 'kategorie', 'adresse', 'telefon', 'email'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Bitte füllen Sie alle Pflichtfelder aus: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

// E-Mail validieren
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein']);
    exit;
}

// Daten sanitizen
$praxisName = htmlspecialchars(trim($_POST['praxisName']));
$arztName = htmlspecialchars(trim($_POST['arztName']));
$fachgebiet = htmlspecialchars(trim($_POST['fachgebiet']));
$kategorie = htmlspecialchars(trim($_POST['kategorie']));
$adresse = htmlspecialchars(trim($_POST['adresse']));
$telefon = htmlspecialchars(trim($_POST['telefon']));
$email = htmlspecialchars(trim($_POST['email']));
$website = !empty($_POST['website']) ? htmlspecialchars(trim($_POST['website'])) : 'Nicht angegeben';
$nachricht = !empty($_POST['nachricht']) ? htmlspecialchars(trim($_POST['nachricht'])) : 'Keine zusätzlichen Informationen';

// E-Mail-Konfiguration
$admin_email = 'oliver.rhomberg@gmail.com';
$subject = 'Neue Praxis-Bewerbung: ' . $praxisName;

// E-Mail-Inhalt erstellen
$email_body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
        .label { font-weight: bold; color: #667eea; }
        .value { margin-left: 10px; }
        .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🏥 Neue Praxis-Bewerbung</h1>
        <p>Eine neue Arztpraxis möchte sich bei Termin2Praxis anmelden</p>
    </div>
    
    <div class='content'>
        <div class='info-box'>
            <p><span class='label'>Praxisname:</span><span class='value'>$praxisName</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Arzt/Ansprechpartner:</span><span class='value'>$arztName</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Fachgebiet:</span><span class='value'>$fachgebiet</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Kategorie:</span><span class='value'>$kategorie</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Adresse:</span><span class='value'>$adresse</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Telefon:</span><span class='value'>$telefon</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>E-Mail:</span><span class='value'><a href='mailto:$email'>$email</a></span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Website:</span><span class='value'>$website</span></p>
        </div>
        
        <div class='info-box'>
            <p><span class='label'>Zusätzliche Informationen:</span></p>
            <p style='margin-left: 10px; white-space: pre-wrap;'>$nachricht</p>
        </div>
        
        <div style='margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;'>
            <p><strong>⚠️ Nächste Schritte:</strong></p>
            <ol>
                <li>Prüfe die Angaben und kontaktiere die Praxis zur Verifizierung</li>
                <li>Rufe die angegebene Telefonnummer an, um die Identität zu bestätigen</li>
                <li>Fordere ggf. Nachweise an (Approbation, Praxislizenz, etc.)</li>
                <li>Richte nach erfolgreicher Prüfung einen Account ein</li>
            </ol>
        </div>
    </div>
    
    <div class='footer'>
        <p>Diese E-Mail wurde automatisch vom Termin2Praxis-System generiert.</p>
        <p>Bewerbung eingegangen am: " . date('d.m.Y H:i:s') . " Uhr</p>
    </div>
</body>
</html>
";

// E-Mail-Header
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Termin2Praxis Bewerbungssystem <noreply@termin2praxis.de>\r\n";
$headers .= "Reply-To: $email\r\n";

// E-Mail senden
$mail_sent = mail($admin_email, $subject, $email_body, $headers);

if ($mail_sent) {
    // Bewerbung auch in Datenbank speichern
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO arzt_bewerbungen (praxis_name, arzt_name, fachgebiet, kategorie, adresse, telefon, email, website, nachricht, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssssss", $praxisName, $arztName, $fachgebiet, $kategorie, $adresse, $telefon, $email, $website, $nachricht);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Vielen Dank für Ihre Bewerbung! Wir werden Ihre Angaben prüfen und uns zeitnah bei Ihnen melden.'
        ]);
    } else {
        // E-Mail wurde gesendet, aber DB-Eintrag fehlgeschlagen
        echo json_encode([
            'success' => true, 
            'message' => 'Vielen Dank für Ihre Bewerbung! Wir haben Ihre Anfrage erhalten.'
        ]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Beim Senden der Bewerbung ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.'
    ]);
}
?>
