<?php
header("Content-Type: application/json");
require_once("../includes/config.php");

// Datenbankverbindung holen
$conn = getDBConnection();

// Daten aus dem Flutter-Request (POST) ziehen
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 1. Check: Sind die Felder leer?
if(empty($email) || empty($password)){
    echo json_encode([
        "status" => "error",
        "message" => "Bitte füllen Sie alle Felder aus."
    ]);
    exit();
}

// 2. Datenbank abfragen (Achte auf die Tabellennamen deiner Website!)
// Deine Website nutzt laut Code oben die Tabelle 'users'
$stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1){
    $user = $result->fetch_assoc();

    // 3. Passwort-Check 
    // WICHTIG: Wenn du auf der Website 'password_hash' nutzt, 
    // musst du hier 'password_verify($password, $user['password'])' nehmen!
    // Wenn die Passwörter im Klartext sind (nur für Tests!), dann:
    if($password === $user['password']){
        echo json_encode([
            "status" => "success",
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "role" => $user['role']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Ungültiges Passwort."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Benutzer nicht gefunden."
    ]);
}

$stmt->close();
$conn->close();
?>