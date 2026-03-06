<?php
require_once 'includes/config.php';

// Praxis ID aus URL holen
$praxis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($praxis_id <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Praxis-Informationen laden (inklusive accepting_bookings Status)
$sql = "SELECT * FROM praxen WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $praxis_id);
$stmt->execute();
$praxis = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$praxis) {
    header('Location: index.php');
    exit;
}

// Freie Termine für diese Praxis laden
$sql = "SELECT * FROM appointments WHERE praxis_id = ? AND status = 'frei' AND date >= CURDATE() ORDER BY date, time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $praxis_id);
$stmt->execute();
$freie_termine = $stmt->get_result();
$stmt->close();

// Wenn eingeloggt, "Meine Termine" für diese Praxis anzeigen
$meine_termine = null;
$vergangene_termine = null;
$notification_count = 0;
if (isLoggedIn() && hasRole('patient')) {
    $user_id = $_SESSION['user_id'];
    
    // Nur aktuelle/zukünftige Termine
    $sql = "SELECT * FROM appointments WHERE user_id = ? AND praxis_id = ? AND status IN ('angefragt', 'bestätigt', 'abgelehnt', 'storniert') AND date >= CURDATE() ORDER BY date, time";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $praxis_id);
    $stmt->execute();
    $meine_termine = $stmt->get_result();
    $stmt->close();
    
    // Vergangene Termine
    $sql_past = "SELECT * FROM appointments WHERE user_id = ? AND praxis_id = ? AND status IN ('angefragt', 'bestätigt', 'abgelehnt', 'storniert') AND date < CURDATE() ORDER BY date DESC, time DESC";
    $stmt_past = $conn->prepare($sql_past);
    $stmt_past->bind_param("ii", $user_id, $praxis_id);
    $stmt_past->execute();
    $vergangene_termine = $stmt_past->get_result();
    $stmt_past->close();
    
    // Benachrichtigungen zählen (nur ungelesene für diese Praxis)
    $sql_notifications = "SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND praxis_id = ? AND status IN ('bestätigt', 'abgelehnt', 'storniert') AND is_read = FALSE";
    $stmt_notif = $conn->prepare($sql_notifications);
    $stmt_notif->bind_param("ii", $user_id, $praxis_id);
    $stmt_notif->execute();
    $result_notif = $stmt_notif->get_result();
    $notification_count = $result_notif->fetch_assoc()['count'];
    $stmt_notif->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($praxis['name']); ?> - Termin2Praxis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .praxis-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .praxis-info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">← Zurück zur Übersicht</a>
            <div class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole('patient')): ?>
                        <!-- Benachrichtigungs-Glocke -->
                        <div class="dropdown me-3">
                            <button class="btn btn-link nav-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 1.5rem; text-decoration: none; color: white;">
                                🔔
                                <?php if ($notification_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $notification_count; ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 350px;">
                                <li><h6 class="dropdown-header">Benachrichtigungen</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-muted"><small>Benachrichtigungen für diese Praxis</small></a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <span class="navbar-text me-3">
                        Willkommen, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <?php if (hasRole('arzt')): ?>
                        <a class="btn btn-outline-light btn-sm me-2" href="dashboards/dashboard_arzt.php">Arzt Dashboard</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-light btn-sm" href="logout.php">Abmelden</a>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-sm" href="login.php">Anmelden</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Praxis Header -->
    <div class="praxis-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <?php if (!empty($praxis['bild_url']) && $praxis['bild_url'] !== 'https://via.placeholder.com/400x300?text=Arztpraxis'): ?>
                        <img src="<?php echo htmlspecialchars($praxis['bild_url']); ?>" 
                             alt="<?php echo htmlspecialchars($praxis['name']); ?>" 
                             class="img-fluid rounded shadow"
                             style="max-height: 200px; width: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light rounded shadow d-flex align-items-center justify-content-center" style="height: 200px;">
                            <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" viewBox="0 0 16 16">
                                    <path d="M8 9.05a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                    <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2Zm10.798 11c-.453-1.27-1.76-3-4.798-3-3.037 0-4.345 1.73-4.798 3H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1.202Z"/>
                                </svg>
                                <p class="mb-0 mt-2 text-muted small fw-bold">Foto folgt</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($praxis['name']); ?></h1>
                    <p class="lead mb-2"><?php echo htmlspecialchars($praxis['beschreibung']); ?></p>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark me-2">
                            <i class="bi bi-geo-alt"></i> 
                            <?php 
                                echo htmlspecialchars($praxis['adresse']);
                                if (!empty($praxis['plz']) || !empty($praxis['stadt'])) {
                                    echo ', ' . htmlspecialchars($praxis['plz']) . ' ' . htmlspecialchars($praxis['stadt']);
                                }
                            ?>
                        </span>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($praxis['adresse']); ?>" 
                           target="_blank" 
                           class="badge bg-primary text-white me-2 text-decoration-none"
                           style="cursor: pointer;">
                            <i class="bi bi-map"></i> Route berechnen
                        </a>
                        <span class="badge bg-light text-dark me-2">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($praxis['telefon']); ?>
                        </span>
                        <span class="badge bg-light text-dark me-2">
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($praxis['email']); ?>
                        </span>
                        <?php if (!empty($praxis['opening_time']) && !empty($praxis['closing_time'])): ?>
                        <span class="badge bg-primary text-white me-2">
                            <i class="bi bi-clock"></i> Öffnungszeiten: 
                            <?php echo date('H:i', strtotime($praxis['opening_time'])); ?> - 
                            <?php echo date('H:i', strtotime($praxis['closing_time'])); ?> Uhr
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($praxis['reachability_start']) && !empty($praxis['reachability_end'])): ?>
                        <span class="badge bg-info text-white me-2">
                            <i class="bi bi-telephone-fill"></i> Erreichbar: 
                            <?php echo date('H:i', strtotime($praxis['reachability_start'])); ?> - 
                            <?php echo date('H:i', strtotime($praxis['reachability_end'])); ?> Uhr
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($praxis['versicherungsart'])): ?>
                        <span class="badge <?php 
                            if ($praxis['versicherungsart'] === 'Gesetzlich') {
                                echo 'bg-info';
                            } elseif ($praxis['versicherungsart'] === 'Privat') {
                                echo 'bg-warning text-dark';
                            } else {
                                echo 'bg-success';
                            }
                        ?>">
                            <i class="bi bi-shield-fill-check"></i> 
                            <?php 
                                if ($praxis['versicherungsart'] === 'Beide') {
                                    echo 'Gesetzlich & Privat';
                                } else {
                                    echo htmlspecialchars($praxis['versicherungsart']);
                                }
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <!-- Standort-Karte -->
        <div class="card mb-5 shadow-lg">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Standort & Anfahrt</h4>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <div class="col-md-8">
                        <!-- Eingebettete Google Maps Karte -->
                        <iframe 
                            width="100%" 
                            height="400" 
                            frameborder="0" 
                            style="border:0" 
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo urlencode($praxis['adresse']); ?>&zoom=15"
                            allowfullscreen>
                        </iframe>
                    </div>
                    <div class="col-md-4 p-4 bg-light">
                        <h5 class="fw-bold mb-3">Adresse</h5>
                        <p class="mb-4">
                            <i class="bi bi-geo-alt-fill text-primary"></i> 
                            <?php 
                                echo htmlspecialchars($praxis['adresse']);
                                if (!empty($praxis['plz']) || !empty($praxis['stadt'])) {
                                    echo '<br>' . htmlspecialchars($praxis['plz']) . ' ' . htmlspecialchars($praxis['stadt']);
                                }
                            ?>
                        </p>
                        
                        <h5 class="fw-bold mb-3">Navigation</h5>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($praxis['adresse']); ?>" 
                           target="_blank" 
                           class="btn btn-success btn-lg w-100 mb-3">
                            <i class="bi bi-map"></i> Route mit Google Maps
                        </a>
                        
                        <a href="http://maps.apple.com/?daddr=<?php echo urlencode($praxis['adresse']); ?>" 
                           target="_blank" 
                           class="btn btn-outline-secondary w-100">
                            <i class="bi bi-apple"></i> Route mit Apple Maps
                        </a>
                        
                        <hr class="my-4">
                        
                        <h5 class="fw-bold mb-3">Kontakt</h5>
                        <p class="mb-2">
                            <i class="bi bi-telephone-fill text-success"></i> 
                            <a href="tel:<?php echo htmlspecialchars($praxis['telefon']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($praxis['telefon']); ?>
                            </a>
                        </p>
                        <p class="mb-0">
                            <i class="bi bi-envelope-fill text-primary"></i> 
                            <a href="mailto:<?php echo htmlspecialchars($praxis['email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($praxis['email']); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($meine_termine && $meine_termine->num_rows > 0): ?>
            <!-- Meine Termine (nur für eingeloggte Patienten) -->
            <div class="card mb-5 shadow" id="meineTermine">
                <div class="card-header bg-info text-white py-3">
                    <h4 class="mb-0">✓ Meine aktuellen Termine bei <?php echo htmlspecialchars($praxis['name']); ?></h4>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Uhrzeit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($termin = $meine_termine->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($termin['date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</td>
                                        <td>
                                            <?php if ($termin['status'] === 'angefragt'): ?>
                                                <span class="badge bg-warning text-dark">Angefragt</span>
                                            <?php elseif ($termin['status'] === 'abgelehnt'): ?>
                                                <span class="badge bg-danger">Abgelehnt</span>
                                            <?php elseif ($termin['status'] === 'storniert'): ?>
                                                <span class="badge bg-secondary">Storniert</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Bestätigt</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Verfügbare Termine -->
        <div class="card mb-5 shadow-lg">
            <div class="card-header bg-success text-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">📅 Verfügbare Termine</h3>
                        <p class="mb-0 mt-2">Wählen Sie einen passenden Termin aus oder fragen Sie einen individuellen Termin an</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (isset($praxis['accepting_bookings']) && !$praxis['accepting_bookings']): ?>
                    <div class="alert alert-warning text-center">
                        <h5><i class="bi bi-pause-circle"></i> Terminbuchung gestoppt</h5>
                        <p class="mb-0">Die Praxis nimmt derzeit keine neuen Terminanfragen entgegen.</p>
                    </div>
                <?php elseif ($freie_termine->num_rows === 0): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ffc107;"></i>
                        </div>
                        <h5 class="mb-3">Derzeit sind keine freien Termine verfügbar</h5>
                        <p class="text-muted mb-4">Kein Problem! Teilen Sie uns Ihren Wunschtermin mit.</p>
                        <?php if (isLoggedIn() && hasRole('patient') && isset($praxis['accepting_bookings']) && $praxis['accepting_bookings']): ?>
                        <button class="btn btn-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i class="bi bi-calendar-plus"></i> Wunschtermin jetzt anfragen
                        </button>
                        <?php elseif (!isLoggedIn()): ?>
                        <a href="login.php" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-box-arrow-in-right"></i> Anmelden und Termin anfragen
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php while ($termin = $freie_termine->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-body text-center p-4">
                                        <div class="display-6 mb-3">📅</div>
                                        <h5 class="card-title fw-bold mb-3">
                                            <?php echo date('d.m.Y', strtotime($termin['date'])); ?>
                                        </h5>
                                        <p class="card-text fs-4 fw-bold text-primary mb-4">
                                            <?php echo date('H:i', strtotime($termin['time'])); ?> Uhr
                                        </p>
                                        <?php if ($termin['duration']): ?>
                                        <p class="text-muted mb-3">
                                            <i class="bi bi-clock"></i> ca. <?php echo $termin['duration']; ?> Min.
                                        </p>
                                        <?php endif; ?>
                                        <?php if ($termin['description']): ?>
                                        <p class="text-muted small mb-3">
                                            <?php echo htmlspecialchars($termin['description']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if (isLoggedIn() && hasRole('patient')): ?>
                                        <button class="btn btn-success btn-lg w-100" onclick="bookAppointment(<?php echo $termin['id']; ?>)">
                                            <i class="bi bi-check-circle"></i> Termin buchen
                                        </button>
                                        <?php else: ?>
                                        <a href="login.php" class="btn btn-success btn-lg w-100">
                                            <i class="bi bi-box-arrow-in-right"></i> Anmelden zum Buchen
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php if (isLoggedIn() && hasRole('patient') && isset($praxis['accepting_bookings']) && $praxis['accepting_bookings']): ?>
                    <div class="text-center mt-5 pt-4 border-top">
                        <h5 class="mb-3">Keiner dieser Termine passt?</h5>
                        <p class="text-muted mb-3">Fragen Sie einen individuellen Termin zu Ihrer Wunschzeit an!</p>
                        <button class="btn btn-outline-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i class="bi bi-calendar-plus"></i> Individuellen Termin anfragen
                        </button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal für Terminanfrage -->
    <?php if (isLoggedIn() && hasRole('patient') && isset($praxis['accepting_bookings']) && $praxis['accepting_bookings']): ?>
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title" id="requestModalLabel">
                        <i class="bi bi-calendar-plus"></i> Individuellen Termin anfragen
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Hinweis:</strong> Sie haben keinen passenden Termin gefunden? Teilen Sie uns Ihren Wunschtermin mit! Der Arzt wird Ihre Anfrage prüfen und Sie benachrichtigen.
                    </div>
                    <form id="requestAppointmentForm">
                        <input type="hidden" name="praxis_id" value="<?php echo $praxis_id; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="request_date" class="form-label fw-bold">
                                    <i class="bi bi-calendar"></i> Wunschdatum <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="request_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">Wählen Sie Ihr gewünschtes Datum</small>
                            </div>
                            <div class="col-md-6">
                                <label for="request_time" class="form-label fw-bold">
                                    <i class="bi bi-clock"></i> Wunschuhrzeit <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control" id="request_time" name="time" required>
                                <small class="text-muted">Wählen Sie Ihre gewünschte Uhrzeit</small>
                            </div>
                            <div class="col-12">
                                <label for="request_description" class="form-label fw-bold">
                                    <i class="bi bi-journal-medical"></i> Grund / Terminart <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="request_description" name="description" 
                                       placeholder="z.B. Starke Kopfschmerzen, Kontrolltermin, Erstgespräch..." required>
                                <small class="text-muted">Beschreiben Sie kurz Ihr Anliegen</small>
                            </div>
                            <div class="col-12">
                                <label for="request_notes" class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text"></i> Zusätzliche Informationen (optional)
                                </label>
                                <textarea class="form-control" id="request_notes" name="notes" rows="4" 
                                          placeholder="z.B. Symptome, bisherige Behandlungen, Dringlichkeit..."></textarea>
                                <small class="text-muted">Je mehr Informationen Sie angeben, desto besser kann der Arzt Ihre Anfrage einschätzen</small>
                            </div>
                        </div>
                        <div id="requestMessage" class="mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Abbrechen
                    </button>
                    <button type="submit" form="requestAppointmentForm" class="btn btn-primary btn-lg">
                        <i class="bi bi-send"></i> Terminanfrage senden
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Termin buchen
        function bookAppointment(appointmentId) {
            if (!confirm('Möchten Sie diesen Termin buchen?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            
            fetch('api/book_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                alert('Fehler beim Buchen des Termins');
                console.error('Error:', error);
            });
        }

        // Terminanfrage senden
        const requestForm = document.getElementById('requestAppointmentForm');
        if (requestForm) {
            requestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const messageDiv = document.getElementById('requestMessage');
                const formData = new FormData(this);
                
                // Button deaktivieren während der Anfrage
                const submitBtn = document.querySelector('button[form="requestAppointmentForm"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Wird gesendet...';
                
                fetch('api/request_appointment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="bi bi-check-circle"></i> <strong>Erfolg!</strong> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                        requestForm.reset();
                        // Modal schließen und Seite neu laden nach 1.5 Sekunden
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
                            if (modal) modal.hide();
                            location.reload();
                        }, 1500);
                    } else {
                        messageDiv.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle"></i> <strong>Fehler!</strong> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Fehler!</strong> Es ist ein Fehler beim Senden der Anfrage aufgetreten.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Button wieder aktivieren
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }
    </script>
</body>
</html>
