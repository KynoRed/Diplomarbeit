<?php
header("Access-Control-Allow-Origin: *"); // Erlaubt den Zugriff von außen
header("Content-Type: application/json");

// Teste, ob überhaupt etwas ankommt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Kein POST-Request"]);
    exit;
}
// Gehe einen Ordner hoch, um die Config zu finden
require_once '../includes/config.php'; 

$conn = getDBConnection();

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Unvollständige Daten"]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'patient';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Registrierung erfolgreich"]);
} else {
    echo json_encode(["status" => "error", "message" => "E-Mail bereits vergeben"]);
}

$stmt->close();
$conn->close();
?>