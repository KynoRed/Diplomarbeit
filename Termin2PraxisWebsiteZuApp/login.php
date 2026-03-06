<?php
require_once 'includes/config.php';

// Hilfsfunktion für JSON-Antworten (für Flutter)
function sendJsonResponse($status, $message, $extra = []) {
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit();
}

// Falls die Anfrage von der Flutter-App kommt (Content-Type ist application/x-www-form-urlencoded oder json)
$isAppRequest = (isset($_POST['api_request']) && $_POST['api_request'] == 'true');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // WICHTIG: password_verify nutzen, da wir in der App passwort_hash verwenden!
            if (password_verify($password, $user['password'])) {
                
                // Session für Website-Nutzer
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                // Falls App-Anfrage: JSON senden und Skript beenden
                if ($isAppRequest) {
                    sendJsonResponse("success", "Login erfolgreich", [
                        "user" => [
                            "id" => $user['id'],
                            "name" => $user['name'],
                            "role" => $user['role']
                        ]
                    ]);
                }
                
                // ... (Hier bleibt euer restlicher Weiterleitungs-Code für die Website) ...
                header("Location: index.php");
                exit();
            } else {
                $error = 'Passwort falsch';
            }
        } else {
            $error = 'Benutzer nicht gefunden';
        }
        
        if ($isAppRequest && isset($error)) {
            sendJsonResponse("error", $error);
        }
    }
}

// Ab hier folgt euer HTML-Code für die Weboberfläche (wie bisher)
?>