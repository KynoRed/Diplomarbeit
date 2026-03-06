<?php
require_once 'includes/config.php';
requireRole('arzt');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$praxis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Prüfen, ob die Praxis dem Arzt gehört
$sql = "SELECT * FROM praxen WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $praxis_id, $user_id);
$stmt->execute();
$praxis = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$praxis) {
    header('Location: dashboards/dashboard_praxisbesitzer.php');
    exit;
}

$success = '';
$error = '';

// Upload-Verzeichnis erstellen falls nicht vorhanden
$upload_dir = 'uploads/praxen/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $opening_time = trim($_POST['opening_time'] ?? '08:00');
    $closing_time = trim($_POST['closing_time'] ?? '18:00');
    $reachability_start = trim($_POST['reachability_start'] ?? '07:00');
    $reachability_end = trim($_POST['reachability_end'] ?? '20:00');
    $bild_url = trim($_POST['bild_url'] ?? $praxis['bild_url'] ?? '');
    
    // Bild-Upload verarbeiten
    if (isset($_FILES['praxis_bild']) && $_FILES['praxis_bild']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['praxis_bild']['type'];
        $file_size = $_FILES['praxis_bild']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Nur JPG, PNG, GIF und WebP Bilder sind erlaubt.';
        } elseif ($file_size > $max_size) {
            $error = 'Das Bild darf maximal 5MB groß sein.';
        } else {
            // Dateiname generieren
            $extension = pathinfo($_FILES['praxis_bild']['name'], PATHINFO_EXTENSION);
            $filename = 'praxis_' . $praxis_id . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['praxis_bild']['tmp_name'], $upload_path)) {
                $bild_url = $upload_path;
            } else {
                $error = 'Fehler beim Hochladen des Bildes.';
            }
        }
    }
    
    if (empty($name)) {
        $error = 'Der Name der Praxis ist erforderlich.';
    } elseif (empty($spezialgebiet)) {
        $error = 'Bitte wählen Sie ein Spezialgebiet aus.';
    } elseif (empty($adresse)) {
        $error = 'Die Adresse (Straße und Hausnummer) ist erforderlich.';
    } elseif (empty($plz)) {
        $error = 'Die Postleitzahl ist erforderlich.';
    } elseif (empty($stadt)) {
        $error = 'Die Stadt ist erforderlich.';
    } elseif (empty($telefon) && empty($email)) {
        $error = 'Bitte geben Sie mindestens eine Telefonnummer oder E-Mail-Adresse an.';
    }
    
    if (empty($error)) {
        // Geocoding durchführen wenn Adresse geändert wurde
        $latitude = $praxis['latitude'];
        $longitude = $praxis['longitude'];
        
        // Prüfen ob Adressdaten geändert wurden
        $address_changed = (
            $adresse !== $praxis['adresse'] || 
            $plz !== $praxis['plz'] || 
            $stadt !== $praxis['stadt']
        );
        
        if ($address_changed) {
            $coords = geocodeAddress($adresse, $plz, $stadt);
            if ($coords !== null) {
                $latitude = $coords['lat'];
                $longitude = $coords['lon'];
            }
        }
        
        $update_sql = "UPDATE praxen SET 
            name = ?, 
            beschreibung = ?, 
            adresse = ?, 
            plz = ?, 
            stadt = ?, 
            telefon = ?, 
            email = ?, 
            spezialgebiet = ?, 
            kategorie = ?,
            bild_url = ?,
            versicherungsart = ?,
            opening_time = ?,
            closing_time = ?,
            reachability_start = ?,
            reachability_end = ?,
            latitude = ?,
            longitude = ?
            WHERE id = ? AND owner_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssssssssssssddii", $name, $beschreibung, $adresse, $plz, $stadt, $telefon, $email, $spezialgebiet, $kategorie, $bild_url, $versicherungsart, $opening_time, $closing_time, $reachability_start, $reachability_end, $latitude, $longitude, $praxis_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Praxisdaten erfolgreich aktualisiert!';
            // Praxisdaten neu laden
            $sql = "SELECT * FROM praxen WHERE id = ? AND owner_id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("ii", $praxis_id, $user_id);
            $stmt2->execute();
            $praxis = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $error = 'Fehler beim Aktualisieren der Praxisdaten: ' . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($praxis['name']); ?> bearbeiten - Termin2Praxis</title>
    <link rel="icon" type="image/svg+xml" href="assets/T2P_transparent_2.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboards/dashboard_praxisbesitzer.php">
                <img src="assets/T2P_transparent_2.svg" alt="T2P Logo" style="height: 45px; margin-right: 10px;">
                <span>← Zurück zu Meine Praxen</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link me-3" href="dashboards/dashboard_arzt.php">Terminverwaltung</a>
                <span class="navbar-text me-3">
                    Willkommen, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Abmelden</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Praxis bearbeiten</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Erfolg!</strong> <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Fehler!</strong> <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Grundinformationen</h5>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Praxisname <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($praxis['name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="beschreibung" class="form-label">Beschreibung</label>
                                    <textarea class="form-control" id="beschreibung" name="beschreibung" rows="4" 
                                              placeholder="Beschreiben Sie Ihre Praxis..."><?php echo htmlspecialchars($praxis['beschreibung'] ?? ''); ?></textarea>
                                    <small class="text-muted">Eine aussagekräftige Beschreibung hilft Patienten, Ihre Praxis besser kennenzulernen.</small>
                                </div>

                                <!-- Praxisbild Upload -->
                                <div class="mb-3">
                                    <label class="form-label">Praxisbild</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <?php 
                                            $current_bild = $praxis['bild_url'] ?? '';
                                            $hat_eigenes_bild = !empty($current_bild) && strpos($current_bild, 'via.placeholder.com') === false;
                                            if (!$hat_eigenes_bild) {
                                                $current_bild = 'assets/fotoFolgt.png';
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($current_bild); ?>" 
                                                 class="img-fluid rounded mb-2" 
                                                 alt="Aktuelles Praxisbild"
                                                 id="bildVorschau"
                                                 style="max-height: 150px; object-fit: cover;">
                                            <?php if ($hat_eigenes_bild): ?>
                                                <p class="text-success small"><i class="bi bi-check-circle"></i> Eigenes Bild vorhanden</p>
                                            <?php else: ?>
                                                <p class="text-muted small"><i class="bi bi-info-circle"></i> Standard-Bild</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-8">
                                            <label for="praxis_bild" class="form-label">Neues Bild hochladen</label>
                                            <input type="file" class="form-control" id="praxis_bild" name="praxis_bild" 
                                                   accept="image/jpeg,image/png,image/gif,image/webp">
                                            <small class="text-muted">Max. 5MB. Erlaubte Formate: JPG, PNG, GIF, WebP</small>
                                            
                                            <div class="mt-2">
                                                <label for="bild_url" class="form-label">Oder Bild-URL eingeben</label>
                                                <input type="url" class="form-control" id="bild_url" name="bild_url" 
                                                       value="<?php echo $hat_eigenes_bild ? htmlspecialchars($praxis['bild_url']) : ''; ?>"
                                                       placeholder="https://beispiel.de/bild.jpg">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Kategorisierung</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="spezialgebiet" class="form-label">Spezialgebiet <span class="text-danger">*</span></label>
                                        <select class="form-select" id="spezialgebiet" name="spezialgebiet" required>
                                            <option value="">Bitte wählen...</option>
                                            <?php
                                            $spezialgebiete = ['Allgemeinmedizin', 'Innere Medizin', 'Orthopädie', 'Kardiologie', 'Dermatologie', 'Neurologie', 'Pädiatrie', 'Gynäkologie', 'Urologie', 'HNO', 'Augenheilkunde', 'Psychiatrie', 'Zahnmedizin', 'Chirurgie', 'Radiologie', 'Anästhesie', 'Onkologie', 'Physiotherapie'];
                                            foreach ($spezialgebiete as $sg) {
                                                $selected = ($praxis['spezialgebiet'] ?? '') === $sg ? 'selected' : '';
                                                echo "<option value=\"$sg\" $selected>$sg</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="kategorie" class="form-label">Kategorie</label>
                                        <select class="form-select" id="kategorie" name="kategorie">
                                            <option value="">Bitte wählen</option>
                                            <?php
                                            $kategorien = ['Allgemeine & hausärztliche Versorgung', 'Organe & innere Erkrankungen', 'Chirurgische Fächer', 'Kopf, Sinne & Nerven', 'Frauen, Männer & Kinder', 'Haut, Allergien & Immunsystem', 'Krebs & schwere Erkrankungen', 'Diagnostik & Technik', 'Prävention & spezielle Versorgung'];
                                            foreach ($kategorien as $kat) {
                                                $selected = ($praxis['kategorie'] ?? '') === $kat ? 'selected' : '';
                                                echo "<option value=\"$kat\" $selected>$kat</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="versicherungsart" class="form-label">Akzeptierte Versicherungsarten</label>
                                        <select class="form-select" id="versicherungsart" name="versicherungsart">
                                            <option value="Beide" <?php echo ($praxis['versicherungsart'] ?? 'Beide') === 'Beide' ? 'selected' : ''; ?>>Gesetzlich & Privat</option>
                                            <option value="Gesetzlich" <?php echo ($praxis['versicherungsart'] ?? '') === 'Gesetzlich' ? 'selected' : ''; ?>>Nur Gesetzlich</option>
                                            <option value="Privat" <?php echo ($praxis['versicherungsart'] ?? '') === 'Privat' ? 'selected' : ''; ?>>Nur Privat</option>
                                        </select>
                                        <small class="text-muted">Geben Sie an, welche Versicherungsarten Sie akzeptieren</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="opening_time" class="form-label">Öffnungszeit</label>
                                        <input type="time" class="form-control" id="opening_time" name="opening_time" 
                                               value="<?php echo htmlspecialchars($praxis['opening_time'] ?? '08:00'); ?>">
                                        <small class="text-muted">Ab wann ist Ihre Praxis für Patienten geöffnet?</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="closing_time" class="form-label">Schließzeit</label>
                                        <input type="time" class="form-control" id="closing_time" name="closing_time" 
                                               value="<?php echo htmlspecialchars($praxis['closing_time'] ?? '18:00'); ?>">
                                        <small class="text-muted">Bis wann ist Ihre Praxis für Patienten geöffnet?</small>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> <strong>Hinweis:</strong> Ihre Praxis wird nur während der Öffnungszeiten in der Praxisliste angezeigt. Patienten mit bestätigten Terminen sehen Ihre Praxis weiterhin.
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3 mt-4"><i class="bi bi-telephone"></i> Erreichbarkeitszeiten (optional)</h6>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="use_same_times" onchange="toggleReachabilityFields()">
                                    <label class="form-check-label" for="use_same_times">
                                        Erreichbarkeitszeiten entsprechen den Öffnungszeiten
                                    </label>
                                </div>
                                <div id="reachability_fields">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="reachability_start" class="form-label">Erreichbar ab</label>
                                            <input type="time" class="form-control" id="reachability_start" name="reachability_start" 
                                                   value="<?php echo htmlspecialchars($praxis['reachability_start'] ?? '07:00'); ?>">
                                            <small class="text-muted">Ab wann sind Sie telefonisch/per E-Mail erreichbar?</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="reachability_end" class="form-label">Erreichbar bis</label>
                                            <input type="time" class="form-control" id="reachability_end" name="reachability_end" 
                                                   value="<?php echo htmlspecialchars($praxis['reachability_end'] ?? '20:00'); ?>">
                                            <small class="text-muted">Bis wann sind Sie telefonisch/per E-Mail erreichbar?</small>
                                        </div>
                                    </div>
                                    <div class="alert alert-light border">
                                        <i class="bi bi-lightbulb"></i> <strong>Beispiel:</strong> Öffnungszeiten 08:00-18:00 Uhr, aber telefonisch bereits ab 07:00 Uhr und bis 20:00 Uhr erreichbar.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Kontaktdaten</h5>
                                
                                <div class="mb-3 position-relative">
                                    <label for="adresse" class="form-label">Straße und Hausnummer <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="adresse" name="adresse" required
                                           value="<?php echo htmlspecialchars($praxis['adresse'] ?? ''); ?>"
                                           placeholder="Hauptstraße 123"
                                           autocomplete="off">
                                    <div id="address-suggestions" class="list-group position-absolute w-100" style="z-index: 1000; display: none; max-height: 300px; overflow-y: auto;"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="plz" class="form-label">PLZ <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="plz" name="plz" required
                                               value="<?php echo htmlspecialchars($praxis['plz'] ?? ''); ?>"
                                               placeholder="12345">
                                    </div>

                                    <div class="col-md-8 mb-3">
                                        <label for="stadt" class="form-label">Stadt <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="stadt" name="stadt" required
                                               value="<?php echo htmlspecialchars($praxis['stadt'] ?? ''); ?>"
                                               placeholder="Berlin">
                                    </div>
                                </div>

                                <div class="alert alert-info small py-2 mb-3">
                                    <i class="bi bi-info-circle"></i> Mindestens Telefon oder E-Mail muss angegeben werden, damit Patienten Sie kontaktieren können.
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefon" class="form-label">Telefon <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" 
                                               value="<?php echo htmlspecialchars($praxis['telefon'] ?? ''); ?>"
                                               placeholder="+49 30 12345678">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">E-Mail <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($praxis['email'] ?? ''); ?>"
                                               placeholder="info@praxis.de">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="dashboards/dashboard_praxisbesitzer.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Abbrechen
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Änderungen speichern
                                </button>
                            </div>
                        </form>
                        
                        <!-- Praxis löschen Bereich -->
                        <hr class="my-4">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Gefahrenzone</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    Wenn Sie diese Praxis nicht mehr betreiben, können Sie sie hier löschen. 
                                    <strong class="text-danger">Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden!
                                    Alle Termine dieser Praxis werden ebenfalls gelöscht.
                                </p>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePraxisModal">
                                    <i class="bi bi-trash"></i> Praxis löschen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vorschau Card -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Vorschau (so sehen Patienten Ihre Praxis)</h5>
                    </div>
                    <div class="card-body">
                        <div class="card">
                            <?php 
                            $vorschau_bild = $praxis['bild_url'] ?? '';
                            if (empty($vorschau_bild) || strpos($vorschau_bild, 'via.placeholder.com') !== false) {
                                $vorschau_bild = 'assets/fotoFolgt.png';
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($vorschau_bild); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($praxis['name']); ?>"
                                 id="vorschauBild"
                                 style="max-height: 250px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($praxis['name']); ?></h5>
                                
                                <?php if (!empty($praxis['spezialgebiet'])): ?>
                                    <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($praxis['spezialgebiet']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($praxis['kategorie'])): ?>
                                    <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($praxis['kategorie']); ?></span>
                                <?php endif; ?>
                                
                                <p class="card-text"><?php echo htmlspecialchars($praxis['beschreibung'] ?? 'Keine Beschreibung vorhanden'); ?></p>
                                
                                <hr>
                                
                                <p class="mb-1"><strong>Adresse:</strong> 
                                    <?php 
                                    $adresse_teile = [];
                                    if (!empty($praxis['adresse'])) $adresse_teile[] = $praxis['adresse'];
                                    if (!empty($praxis['plz']) || !empty($praxis['stadt'])) {
                                        $ort = trim(($praxis['plz'] ?? '') . ' ' . ($praxis['stadt'] ?? ''));
                                        if ($ort) $adresse_teile[] = $ort;
                                    }
                                    echo !empty($adresse_teile) ? htmlspecialchars(implode(', ', $adresse_teile)) : 'Keine Adresse angegeben';
                                    ?>
                                </p>
                                
                                <?php if (!empty($praxis['telefon'])): ?>
                                    <p class="mb-1"><strong>Telefon:</strong> <?php echo htmlspecialchars($praxis['telefon']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($praxis['email'])): ?>
                                    <p class="mb-1"><strong>E-Mail:</strong> <?php echo htmlspecialchars($praxis['email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        #address-suggestions {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 5px;
        }
        #address-suggestions .list-group-item {
            cursor: pointer;
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }
        #address-suggestions .list-group-item:last-child {
            border-bottom: none;
        }
        #address-suggestions .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bildvorschau beim Hochladen
            document.getElementById('praxis_bild').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('bildVorschau').src = e.target.result;
                        document.getElementById('vorschauBild').src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // URL-Vorschau
            document.getElementById('bild_url').addEventListener('input', function(e) {
                const url = e.target.value;
                if (url) {
                    document.getElementById('bildVorschau').src = url;
                    document.getElementById('vorschauBild').src = url;
                }
            });
            
            // Formular-Validierung für Telefon/E-Mail
            document.querySelector('form').addEventListener('submit', function(e) {
                const telefon = document.getElementById('telefon').value.trim();
                const email = document.getElementById('email').value.trim();
                
                if (!telefon && !email) {
                    e.preventDefault();
                    alert('Bitte geben Sie mindestens eine Telefonnummer oder E-Mail-Adresse an, damit Patienten Sie kontaktieren können.');
                    document.getElementById('telefon').focus();
                }
            });
            
            // Adress-Autovervollständigung
            let addressTimeout = null;
            const adresseInput = document.getElementById('adresse');
            const plzInput = document.getElementById('plz');
            const stadtInput = document.getElementById('stadt');
            const suggestionsDiv = document.getElementById('address-suggestions');
            
            console.log('Adress-Autovervollständigung initialisiert');
            
            adresseInput.addEventListener('input', function() {
                const query = this.value.trim();
                console.log('Input erkannt:', query);
                
                // Mindestens 3 Zeichen erforderlich
                if (query.length < 3) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }
                
                // Debouncing - warte 500ms nach dem letzten Tastendruck
                clearTimeout(addressTimeout);
                addressTimeout = setTimeout(() => {
                    console.log('Suche Adresse:', query);
                    searchAddress(query);
                }, 500);
            });
            
            function searchAddress(query) {
                // Verwende PHP-Proxy statt direkter API-Anfrage (vermeidet CORS-Probleme)
                const url = `api/geocode_search.php?q=${encodeURIComponent(query)}`;
                
                console.log('API-Anfrage über Proxy:', url);
                
                fetch(url)
                .then(response => {
                    console.log('API-Response Status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API-Daten empfangen:', data);
                    if (data && data.length > 0) {
                        showSuggestions(data);
                    } else {
                        console.log('Keine Ergebnisse gefunden');
                        suggestionsDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Adresssuche fehlgeschlagen:', error);
                    suggestionsDiv.style.display = 'none';
                });
            }
            
            function showSuggestions(results) {
                suggestionsDiv.innerHTML = '';
                let validResults = 0;
                
                results.forEach(result => {
                    const address = result.address;
                    if (!address) return;
                    
                    const street = address.road || address.pedestrian || address.street || '';
                    const houseNumber = address.house_number || '';
                    const postcode = address.postcode || '';
                    const city = address.city || address.town || address.village || address.municipality || '';
                    const country = address.country || '';
                    
                    // Überspringen wenn keine Straße vorhanden
                    if (!street && !city) return;
                    
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    
                    // Adresse formatieren
                    let displayStreet = street;
                    if (houseNumber) {
                        displayStreet = `${street} ${houseNumber}`;
                    }
                    
                    // Länderflagge bestimmen
                    let countryFlag = '🇩🇪';  // Deutschland Standard
                    let countryName = 'Deutschland';
                    if (country === 'Österreich' || country === 'Austria') {
                        countryFlag = '🇦🇹';
                        countryName = 'Österreich';
                    } else if (country === 'Schweiz' || country === 'Switzerland' || country === 'Suisse') {
                        countryFlag = '🇨🇭';
                        countryName = 'Schweiz';
                    }
                    
                    item.innerHTML = `
                        <div class="d-flex align-items-center py-2">
                            <i class="bi bi-geo-alt-fill text-primary me-3" style="font-size: 1.2rem;"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${displayStreet || city}</div>
                                <small class="text-muted">${postcode} ${city} <span style="opacity: 0.7;">${countryFlag} ${countryName}</span></small>
                            </div>
                        </div>
                    `;
                    
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Adresse ausgewählt:', displayStreet, postcode, city, country);
                        
                        // Felder automatisch ausfüllen
                        if (displayStreet) {
                            adresseInput.value = displayStreet.trim();
                        }
                        if (postcode) {
                            plzInput.value = postcode;
                        }
                        if (city) {
                            stadtInput.value = city;
                        }
                        
                        suggestionsDiv.style.display = 'none';
                        
                        // Visuelles Feedback - grüner Rahmen
                        [adresseInput, plzInput, stadtInput].forEach(input => {
                            if (input.value) {
                                input.classList.add('border-success');
                                input.classList.add('border-3');
                                setTimeout(() => {
                                    input.classList.remove('border-success');
                                    input.classList.remove('border-3');
                                }, 2500);
                            }
                        });
                    });
                    
                    suggestionsDiv.appendChild(item);
                    validResults++;
                });
                
                console.log('Zeige', validResults, 'Vorschläge');
                
                if (validResults > 0) {
                    suggestionsDiv.style.display = 'block';
                } else {
                    suggestionsDiv.style.display = 'none';
                }
            }
            
            // Schließe Vorschläge beim Klick außerhalb
            document.addEventListener('click', function(e) {
                if (!adresseInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });
        });
    </script>
    
    <!-- Modal für Praxis löschen -->
    <div class="modal fade" id="deletePraxisModal" tabindex="-1" aria-labelledby="deletePraxisModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deletePraxisModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Praxis löschen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>Warnung!</strong> Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                    <p>Wenn Sie diese Praxis löschen:</p>
                    <ul>
                        <li>Werden alle Termine dieser Praxis gelöscht</li>
                        <li>Können Patienten keine Termine mehr buchen</li>
                        <li>Gehen alle Praxisdaten verloren</li>
                    </ul>
                    <hr>
                    <p class="mb-2">Um zu bestätigen, geben Sie den Praxisnamen ein:</p>
                    <p class="fw-bold text-primary mb-2" id="praxisNameDisplay"><?php echo htmlspecialchars($praxis['name']); ?></p>
                    <input type="text" class="form-control" id="confirmDeleteInput" placeholder="Praxisname eingeben">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash"></i> Endgültig löschen
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script für Praxis löschen (nach dem Modal!) -->
    <script>
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const confirmInput = document.getElementById('confirmDeleteInput').value.trim();
            const praxisName = document.getElementById('praxisNameDisplay').textContent.trim();
            
            console.log('Eingabe:', confirmInput);
            console.log('Erwartet:', praxisName);
            
            if (confirmInput !== praxisName) {
                alert('Der eingegebene Name stimmt nicht überein.\n\nErwartet: "' + praxisName + '"\nEingegeben: "' + confirmInput + '"');
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Wird gelöscht...';
            
            fetch('api/delete_praxis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    praxis_id: <?php echo $praxis_id; ?>
                })
            })
            .then(response => {
                console.log('Response Status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response Data:', data);
                if (data.success) {
                    alert('Die Praxis wurde erfolgreich gelöscht.');
                    window.location.href = 'dashboards/dashboard_praxisbesitzer.php';
                } else {
                    alert('Fehler: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-trash"></i> Endgültig löschen';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Ein Fehler ist aufgetreten: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash"></i> Endgültig löschen';
            });
        });
    </script>
    
    <script>
        // Erreichbarkeitszeiten optional machen
        function toggleReachabilityFields() {
            const checkbox = document.getElementById('use_same_times');
            const fieldsDiv = document.getElementById('reachability_fields');
            const reachabilityStart = document.getElementById('reachability_start');
            const reachabilityEnd = document.getElementById('reachability_end');
            const openingTime = document.getElementById('opening_time');
            const closingTime = document.getElementById('closing_time');
            
            if (checkbox.checked) {
                // Verstecke die Felder
                fieldsDiv.style.display = 'none';
                // Synchronisiere Werte mit Öffnungszeiten
                reachabilityStart.value = openingTime.value;
                reachabilityEnd.value = closingTime.value;
            } else {
                // Zeige die Felder
                fieldsDiv.style.display = 'block';
            }
        }
        
        // Beim Laden prüfen ob Erreichbarkeitszeiten = Öffnungszeiten
        document.addEventListener('DOMContentLoaded', function() {
            const openingTime = document.getElementById('opening_time').value;
            const closingTime = document.getElementById('closing_time').value;
            const reachabilityStart = document.getElementById('reachability_start').value;
            const reachabilityEnd = document.getElementById('reachability_end').value;
            
            // Wenn gleich, Checkbox aktivieren
            if (openingTime === reachabilityStart && closingTime === reachabilityEnd) {
                document.getElementById('use_same_times').checked = true;
                toggleReachabilityFields();
            }
            
            // Öffnungszeiten live synchronisieren wenn Checkbox aktiv
            document.getElementById('opening_time').addEventListener('change', function() {
                if (document.getElementById('use_same_times').checked) {
                    document.getElementById('reachability_start').value = this.value;
                }
            });
            
            document.getElementById('closing_time').addEventListener('change', function() {
                if (document.getElementById('use_same_times').checked) {
                    document.getElementById('reachability_end').value = this.value;
                }
            });
        });
    </script>
</body>
</html>
