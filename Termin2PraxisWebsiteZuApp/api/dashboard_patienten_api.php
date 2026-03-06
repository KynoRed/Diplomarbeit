<?php
header("Content-Type: application/json");
require_once("../includes/config.php");

$conn = getDBConnection();

$user_id = $_POST['user_id'] ?? '';

if(empty($user_id)){
    echo json_encode(["status" => "error", "message" => "User ID fehlt"]);
    exit();
}

// NUR die Termine des Patienten laden
$sql_meine = "SELECT a.*, p.name as praxis_name, p.stadt 
              FROM appointments a 
              LEFT JOIN praxen p ON a.praxis_id = p.id 
              WHERE a.user_id = ? AND a.status IN ('angefragt', 'bestätigt') 
              ORDER BY a.date, a.time";

$stmt = $conn->prepare($sql_meine);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meine_termine = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "status" => "success",
    "meine_termine" => $meine_termine
]);

$stmt->close();
$conn->close();
?>