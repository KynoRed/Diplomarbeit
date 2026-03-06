<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Erlaubt Zugriff von der App

$host = "localhost";
$user = "root";
$pass = "";
$db   = "termin2praxis";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Verbindung fehlgeschlagen"]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Passwort sicher verschlüsseln
    $role = 'patient'; // Standardrolle für Registrierung über die App

    // Prepared Statement gegen SQL-Injection
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User erfolgreich erstellt"]);
    } else {
        echo json_encode(["status" => "error", "message" => "E-Mail existiert eventuell schon"]);
    }

    $stmt->close();
}
$conn->close();
?>