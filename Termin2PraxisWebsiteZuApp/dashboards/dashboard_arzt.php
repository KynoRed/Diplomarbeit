<?php
require_once '../includes/config.php';
requireRole('arzt');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Praxen des Arztes laden (mit accepting_bookings Status, Öffnungszeiten und Auslastung)
$sql = "SELECT id, name, accepting_bookings, opening_time, closing_time, occupancy_status FROM praxen WHERE owner_id = ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$praxen_result = $stmt->get_result();
$meine_praxen = [];
while ($row = $praxen_result->fetch_assoc()) {
    $meine_praxen[] = $row;
}
$stmt->close();

// Aktuelle Zeit für Öffnungszeiten-Check
$current_time = date('H:i:s');

// Aktive Praxis setzen/abrufen
$aktive_praxis_id = null;
$aktive_praxis_data = null;
if (isset($_POST['switch_praxis'])) {
    // Praxiswechsel durchführen
    $new_praxis_id = intval($_POST['praxis_id']);
    // Prüfen ob Praxis dem Arzt gehört
    $is_owner = false;
    foreach ($meine_praxen as $p) {
        if ($p['id'] == $new_praxis_id) {
            $is_owner = true;
            $aktive_praxis_data = $p;
            break;
        }
    }
    if ($is_owner) {
        $_SESSION['aktive_praxis_id'] = $new_praxis_id;
        $aktive_praxis_id = $new_praxis_id;
    }
} elseif (isset($_SESSION['aktive_praxis_id'])) {
    $aktive_praxis_id = $_SESSION['aktive_praxis_id'];
    // Aktive Praxis-Daten laden
    foreach ($meine_praxen as $p) {
        if ($p['id'] == $aktive_praxis_id) {
            $aktive_praxis_data = $p;
            break;
        }
    }
} elseif (!empty($meine_praxen)) {
    // Erste Praxis als Standard setzen
    $aktive_praxis_id = $meine_praxen[0]['id'];
    $aktive_praxis_data = $meine_praxen[0];
    $_SESSION['aktive_praxis_id'] = $aktive_praxis_id;
}

// Prüfen ob Praxis aktuell geöffnet (gelistet) ist
$is_praxis_open = false;
if ($aktive_praxis_data) {
    $opening_time = $aktive_praxis_data['opening_time'] ?? '08:00:00';
    $closing_time = $aktive_praxis_data['closing_time'] ?? '18:00:00';
    $is_praxis_open = ($current_time >= $opening_time && $current_time <= $closing_time);
}

// Alle Termine laden (gruppiert nach Status) - gefiltert nach aktiver Praxis
$freie_termine = [];
$angefragte_termine = [];
$bestaetigte_termine = [];
$bestaetigte_termine_vergangen = [];

if ($aktive_praxis_id) {
    $sql = "SELECT a.*, u.name as patient_name, u.email as patient_email, 
            arzt.name as confirmed_by_name, a.confirmed_at
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id 
            LEFT JOIN users arzt ON a.confirmed_by = arzt.id
            WHERE a.praxis_id = ?
            ORDER BY a.date DESC, a.time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $aktive_praxis_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Kein Filter wenn keine Praxis vorhanden
    $sql = "SELECT a.*, u.name as patient_name, u.email as patient_email, 
            arzt.name as confirmed_by_name, a.confirmed_at
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id 
            LEFT JOIN users arzt ON a.confirmed_by = arzt.id
            ORDER BY a.date DESC, a.time DESC";
    $result = $conn->query($sql);
}

$heute = date('Y-m-d');

while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'frei') {
        $freie_termine[] = $row;
    } elseif ($row['status'] === 'angefragt') {
        $angefragte_termine[] = $row;
    } elseif ($row['status'] === 'bestätigt') {
        // Vergangene und zukünftige Termine trennen
        if ($row['date'] < $heute) {
            $bestaetigte_termine_vergangen[] = $row;
        } else {
            $bestaetigte_termine[] = $row;
        }
    }
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arzt Dashboard - Termin2Praxis</title>
    <link rel="icon" type="image/svg+xml" href="../assets/T2P_transparent_2.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/T2P_transparent_2.svg" alt="T2P Logo" style="height: 45px; margin-right: 10px;">
                <span>Termin2Praxis - Arzt</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link me-3" href="dashboard_praxisbesitzer.php">
                    <i class="bi bi-building"></i> Meine Praxen verwalten
                </a>
                <span class="navbar-text me-3">
                    Willkommen, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php">Abmelden</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Praxis-Auswahl -->
        <?php if (!empty($meine_praxen)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" class="row align-items-end" id="praxisForm">
                    <div class="col-md-6">
                        <label for="praxis_select" class="form-label fw-bold">
                            <i class="bi bi-building"></i> Aktive Praxis (Termine werden dieser Praxis zugeordnet):
                        </label>
                        <select class="form-select" id="praxis_select" name="praxis_id" onchange="this.form.submit()">
                            <?php 
                            $aktive_praxis_accepting = 1;
                            $aktive_praxis_occupancy = 'low';
                            foreach ($meine_praxen as $praxis): 
                                if ($praxis['id'] == $aktive_praxis_id) {
                                    $aktive_praxis_accepting = $praxis['accepting_bookings'];
                                    $aktive_praxis_occupancy = $praxis['occupancy_status'] ?? 'low';
                                }
                            ?>
                                <option value="<?php echo $praxis['id']; ?>" 
                                    data-accepting="<?php echo $praxis['accepting_bookings']; ?>"
                                    data-occupancy="<?php echo $praxis['occupancy_status'] ?? 'low'; ?>"
                                    <?php echo ($praxis['id'] == $aktive_praxis_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($praxis['name']); ?>
                                    <?php if (!$praxis['accepting_bookings']): ?> (Buchungen deaktiviert)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="switch_praxis" value="1">
                    </div>
                    <div class="col-md-3">
                        <?php if ($aktive_praxis_id): ?>
                        <button type="button" class="btn w-100" id="toggleBookingsBtn"
                                onclick="toggleBookings(<?php echo $aktive_praxis_id; ?>, <?php echo $aktive_praxis_accepting; ?>)"
                                style="background-color: <?php echo $aktive_praxis_accepting ? '#dc3545' : '#28a745'; ?>; color: white;">
                            <i class="bi bi-<?php echo $aktive_praxis_accepting ? 'pause-circle' : 'play-circle'; ?>"></i>
                            <?php echo $aktive_praxis_accepting ? 'Buchungen stoppen' : 'Buchungen aktivieren'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <a href="dashboard_praxisbesitzer.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Neue Praxis erstellen
                        </a>
                    </div>
                </form>
                
                <!-- Auslastungsstatus -->
                <?php if ($aktive_praxis_id): ?>
                <div class="mt-4 pt-3 border-top">
                    <label class="form-label fw-bold">
                        <i class="bi bi-speedometer2"></i> Aktuelle Auslastung der Praxis:
                    </label>
                    <div class="d-flex gap-2">
                        <button type="button" 
                                class="btn flex-fill <?php echo $aktive_praxis_occupancy === 'low' ? 'btn-success' : 'btn-outline-success'; ?>" 
                                onclick="updateOccupancyStatus(<?php echo $aktive_praxis_id; ?>, 'low')">
                            <i class="bi bi-emoji-smile"></i> Nicht überlaufen
                        </button>
                        <button type="button" 
                                class="btn flex-fill <?php echo $aktive_praxis_occupancy === 'medium' ? 'btn-warning' : 'btn-outline-warning'; ?>" 
                                onclick="updateOccupancyStatus(<?php echo $aktive_praxis_id; ?>, 'medium')">
                            <i class="bi bi-emoji-neutral"></i> Mittel überlaufen
                        </button>
                        <button type="button" 
                                class="btn flex-fill <?php echo $aktive_praxis_occupancy === 'high' ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                                onclick="updateOccupancyStatus(<?php echo $aktive_praxis_id; ?>, 'high')">
                            <i class="bi bi-emoji-frown"></i> Stark überlaufen
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="bi bi-info-circle"></i> Patienten sehen diese Information beim Durchsuchen der Praxen.
                    </small>
                </div>
                <?php endif; ?>
                
                <?php if ($aktive_praxis_id && !$aktive_praxis_accepting): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>Hinweis:</strong> Buchungen sind derzeit für diese Praxis deaktiviert. Patienten können keine neuen Termine buchen.
                </div>
                <?php endif; ?>
                
                <?php if ($aktive_praxis_id && !$is_praxis_open): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-clock-history"></i> 
                    <strong>Praxis außerhalb der Öffnungszeiten</strong><br>
                    Ihre Praxis ist aktuell geschlossen, bleibt aber für Patienten sichtbar.<br>
                    <small>Öffnungszeiten: <?php echo date('H:i', strtotime($opening_time)); ?> - <?php echo date('H:i', strtotime($closing_time)); ?> Uhr | Aktuelle Zeit: <?php echo date('H:i', strtotime($current_time)); ?> Uhr</small><br>
                    <small class="text-muted">Hinweis: Patienten können geschlossene Praxen optional ausblenden.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info" role="alert">
            <h5><i class="bi bi-info-circle"></i> Keine Praxis vorhanden</h5>
            <p>Sie müssen zuerst eine Praxis erstellen, um Termine anbieten zu können.</p>
            <a href="dashboard_praxisbesitzer.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Jetzt Praxis erstellen
            </a>
        </div>
        <?php endif; ?>
        <!-- Neuen Termin erstellen -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5>Neuen freien Termin erstellen</h5>
            </div>
            <div class="card-body">
                <form id="createAppointmentForm" onsubmit="return false;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Datum</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">Uhrzeit</label>
                            <input type="time" class="form-control" id="time" name="time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Termindauer (optional)</label>
                            <select class="form-select" id="duration" name="duration">
                                <option value="">Keine Angabe</option>
                                <option value="15">15 Minuten</option>
                                <option value="30">30 Minuten</option>
                                <option value="45">45 Minuten</option>
                                <option value="60">60 Minuten</option>
                                <option value="90">90 Minuten</option>
                                <option value="120">120 Minuten</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">Art des Termins (optional)</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="z.B. Kontrolltermin, Erstgespräch, Beratung...">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="multipleSlots" name="multipleSlots">
                                <label class="form-check-label" for="multipleSlots">
                                    Mehrere Termine im Abstand erstellen
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="multipleOptions" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="slotCount" class="form-label">Anzahl Termine</label>
                            <input type="number" class="form-control" id="slotCount" name="slotCount" 
                                   min="2" max="20" value="5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="slotInterval" class="form-label">Abstand in Minuten</label>
                            <input type="number" class="form-control" id="slotInterval" name="slotInterval" 
                                   min="5" max="120" value="15">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Termin(e) erstellen</button>
                </form>
                <div id="createMessage" class="mt-3"></div>
            </div>
        </div>

        <!-- Angefragte Termine -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5>Angefragte Termine (<?php echo count($angefragte_termine); ?>)</h5>
            </div>
            <div class="card-body">
                <div id="angefragteTermine">
                    <?php if (empty($angefragte_termine)): ?>
                        <p class="text-muted">Keine angefragten Termine vorhanden.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Datum</th>
                                        <th>Uhrzeit</th>
                                        <th>Dauer</th>
                                        <th>Art</th>
                                        <th>Patient</th>
                                        <th>E-Mail</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($angefragte_termine as $termin): ?>
                                        <tr id="termin-<?php echo $termin['id']; ?>">
                                            <td><?php echo date('d.m.Y', strtotime($termin['date'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</td>
                                            <td><?php echo $termin['duration'] ? $termin['duration'] . ' Min.' : '-'; ?></td>
                                            <td><?php echo $termin['description'] ? htmlspecialchars($termin['description']) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($termin['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($termin['patient_email']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-success me-2" onclick="confirmAppointment(<?php echo $termin['id']; ?>)">
                                                    Bestätigen
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectAppointment(<?php echo $termin['id']; ?>)">
                                                    Ablehnen
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bestätigte Termine -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">⭐ AKTUELLE TERMINE ⭐ (<?php echo count($bestaetigte_termine); ?>)</h5>
                <?php if (count($bestaetigte_termine_vergangen) > 0): ?>
                    <button class="btn btn-outline-light btn-sm" onclick="toggleVergangeneTermine()" id="toggleBtn">
                        + <?php echo count($bestaetigte_termine_vergangen); ?> vergangene Termine anzeigen
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($bestaetigte_termine)): ?>
                    <p class="text-muted">Keine aktuellen Termine vorhanden.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Uhrzeit</th>
                                    <th>Dauer</th>
                                    <th>Art</th>
                                    <th>Patient</th>
                                    <th>E-Mail</th>
                                    <th>Bestätigt von</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bestaetigte_termine as $termin): ?>
                                    <tr id="termin-<?php echo $termin['id']; ?>">
                                        <td><?php echo date('d.m.Y', strtotime($termin['date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</td>
                                        <td><?php echo $termin['duration'] ? $termin['duration'] . ' Min.' : '-'; ?></td>
                                        <td><?php echo $termin['description'] ? htmlspecialchars($termin['description']) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($termin['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($termin['patient_email']); ?></td>
                                        <td>
                                            <?php if ($termin['confirmed_by_name']): ?>
                                                <span class="badge bg-success">Bestätigt von <?php echo htmlspecialchars($termin['confirmed_by_name']); ?></span><br>
                                                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($termin['confirmed_at'])); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Keine Info</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" onclick="deleteAppointment(<?php echo $termin['id']; ?>)">
                                                Löschen
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Vergangene Termine (versteckt) -->
                <?php if (!empty($bestaetigte_termine_vergangen)): ?>
                    <div id="vergangeneTermine" style="display: none;">
                        <hr>
                        <h6 class="text-muted mb-3">
                            📦 Vergangene Termine (<?php echo count($bestaetigte_termine_vergangen); ?>)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Datum</th>
                                        <th>Uhrzeit</th>
                                        <th>Dauer</th>
                                        <th>Art</th>
                                        <th>Patient</th>
                                        <th>E-Mail</th>
                                        <th>Bestätigt von</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bestaetigte_termine_vergangen as $termin): ?>
                                        <tr id="termin-<?php echo $termin['id']; ?>" class="text-muted">
                                            <td><?php echo date('d.m.Y', strtotime($termin['date'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</td>
                                            <td><?php echo $termin['duration'] ? $termin['duration'] . ' Min.' : '-'; ?></td>
                                            <td><?php echo $termin['description'] ? htmlspecialchars($termin['description']) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($termin['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($termin['patient_email']); ?></td>
                                            <td>
                                                <?php if ($termin['confirmed_by_name']): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($termin['confirmed_by_name']); ?></span><br>
                                                    <small class="text-muted"><?php echo date('d.m.Y', strtotime($termin['confirmed_at'])); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAppointment(<?php echo $termin['id']; ?>)">
                                                    Löschen
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Freie Termine -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 id="freieTermineHeader">Freie Termine (<?php echo count($freie_termine); ?>)</h5>
            </div>
            <div class="card-body" id="freieTermineBody">
                <?php if (empty($freie_termine)): ?>
                    <p class="text-muted" id="keineFreienTermine">Keine freien Termine vorhanden.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="freieTermineTable">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Uhrzeit</th>
                                    <th>Dauer</th>
                                    <th>Art</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="freieTermineTbody">
                                <?php foreach ($freie_termine as $termin): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($termin['date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</td>
                                        <td><?php echo $termin['duration'] ? $termin['duration'] . ' Min.' : '-'; ?></td>
                                        <td><?php echo $termin['description'] ? htmlspecialchars($termin['description']) : '-'; ?></td>
                                        <td><span class="badge bg-secondary">Frei</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aktive Praxis-ID für JavaScript verfügbar machen
        const aktivePraxisId = <?php echo $aktive_praxis_id ? $aktive_praxis_id : 'null'; ?>;
    </script>
    <script src="../js/arzt.js?v=<?php echo time(); ?>"></script>
</body>
</html>
