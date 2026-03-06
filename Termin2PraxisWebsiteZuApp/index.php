<?php
require_once 'includes/config.php';

$conn = getDBConnection();

// Filter-Parameter
$filter_kategorie = isset($_GET['kategorie']) ? $_GET['kategorie'] : '';
$filter_spezialgebiet = isset($_GET['spezialgebiet']) ? $_GET['spezialgebiet'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$only_open = isset($_GET['only_open']) && $_GET['only_open'] === '1';

// Standort-Parameter für Entfernungsberechnung
$user_lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$user_lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$use_location = ($user_lat !== null && $user_lng !== null);

// IDs der Praxen mit bestätigten Terminen des Patienten (für Ausnahme-Logik)
$praxen_mit_terminen = [];
if (isLoggedIn() && hasRole('patient')) {
    $user_id = $_SESSION['user_id'];
    $sql_termine = "SELECT DISTINCT praxis_id FROM appointments WHERE user_id = ? AND status = 'bestätigt' AND date >= CURDATE()";
    $stmt_termine = $conn->prepare($sql_termine);
    $stmt_termine->bind_param("i", $user_id);
    $stmt_termine->execute();
    $result_termine = $stmt_termine->get_result();
    while ($row = $result_termine->fetch_assoc()) {
        $praxen_mit_terminen[] = $row['praxis_id'];
    }
    $stmt_termine->close();
}

// Aktuelle Uhrzeit für Öffnungszeiten-Check
$current_time = date('H:i:s');

// SQL-Query mit Filtern aufbauen
if ($use_location) {
    // Haversine-Formel für Entfernungsberechnung (in km)
    // CASE wird verwendet, um NULL zurückzugeben wenn Koordinaten fehlen
    $sql = "SELECT *, 
            CASE 
                WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                    sin(radians(latitude))))
                ELSE NULL
            END AS distance 
            FROM praxen WHERE 1=1";
    $params = [$user_lat, $user_lng, $user_lat];
    $types = 'ddd';
} else {
    $sql = "SELECT * FROM praxen WHERE 1=1";
    $params = [];
    $types = '';
}

// Öffnungszeiten-Filter (optional)
if ($only_open) {
    $sql .= " AND TIME(?) BETWEEN opening_time AND closing_time";
    $params[] = $current_time;
    $types .= 's';
}

if (!empty($filter_kategorie)) {
    $sql .= " AND kategorie = ?";
    $params[] = $filter_kategorie;
    $types .= 's';
}

if (!empty($filter_spezialgebiet)) {
    $sql .= " AND spezialgebiet = ?";
    $params[] = $filter_spezialgebiet;
    $types .= 's';
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR beschreibung LIKE ? OR adresse LIKE ? OR spezialgebiet LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

// Sortierung
if ($use_location) {
    // Bei aktiviertem Standort immer nach Entfernung sortieren
    // NULL-Werte (Praxen ohne Koordinaten) kommen ans Ende
    $sql .= " ORDER BY distance IS NULL ASC, distance ASC";
} else {
    // Standard-Sortierung ohne Standort
    switch ($sort_by) {
        case 'name':
            $sql .= " ORDER BY name ASC";
            break;
        case 'kategorie':
            $sql .= " ORDER BY kategorie ASC, name ASC";
            break;
        case 'spezialgebiet':
            $sql .= " ORDER BY spezialgebiet ASC, name ASC";
            break;
        default:
            $sql .= " ORDER BY name ASC";
    }
}

// Gesamtanzahl der Praxen zählen (ohne LIMIT)
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_count = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_count = $conn->query($count_sql)->fetch_assoc()['total'];
}

// Nur die ersten 6 Praxen laden
$sql .= " LIMIT 6";

// Query ausführen
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $praxen = $stmt->get_result();
} else {
    $praxen = $conn->query($sql);
}

// Alle Kategorien für Filter laden
$kategorien_sql = "SELECT DISTINCT kategorie FROM praxen WHERE kategorie IS NOT NULL ORDER BY kategorie";
$kategorien_result = $conn->query($kategorien_sql);
$kategorien = [];
while ($row = $kategorien_result->fetch_assoc()) {
    $kategorien[] = $row['kategorie'];
}

// Alle Spezialgebiete für Filter laden
$spezialgebiete_sql = "SELECT DISTINCT spezialgebiet FROM praxen WHERE spezialgebiet IS NOT NULL ORDER BY spezialgebiet";
$spezialgebiete_result = $conn->query($spezialgebiete_sql);
$spezialgebiete = [];
while ($row = $spezialgebiete_result->fetch_assoc()) {
    $spezialgebiete[] = $row['spezialgebiet'];
}

// Benachrichtigungen zählen (global für alle Praxen)
$notification_count = 0;
$meine_termine = null;
if (isLoggedIn() && hasRole('patient')) {
    $user_id = $_SESSION['user_id'];
    
    // Benachrichtigungen zählen (nur ungelesene bestätigte + abgelehnte + stornierte Termine)
    $sql_notifications = "SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status IN ('bestätigt', 'abgelehnt', 'storniert') AND is_read = FALSE";
    $stmt_notif = $conn->prepare($sql_notifications);
    $stmt_notif->bind_param("i", $user_id);
    $stmt_notif->execute();
    $result_notif = $stmt_notif->get_result();
    $notification_count = $result_notif->fetch_assoc()['count'];
    $stmt_notif->close();
    
    // Alle aktuellen Termine des Patienten laden (über alle Praxen)
    $sql = "SELECT a.*, p.name as praxis_name FROM appointments a 
            LEFT JOIN praxen p ON a.praxis_id = p.id 
            WHERE a.user_id = ? AND a.status IN ('angefragt', 'bestätigt', 'abgelehnt', 'storniert') AND a.date >= CURDATE() 
            ORDER BY a.date, a.time";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $meine_termine = $stmt->get_result();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin2Praxis - Termine buchen</title>
    <link rel="icon" type="image/svg+xml" href="assets/T2P_transparent_2.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .praxis-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            height: 100%;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .praxis-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .praxis-card-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .praxis-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.95);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .occupancy-badge {
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.2s ease;
        }
        .occupancy-badge:hover {
            transform: scale(1.05);
        }
        .delete-notification-btn:hover {
            background-color: rgba(220, 53, 69, 0.1) !important;
            transform: scale(1.1);
        }
        .delete-notification-btn:active {
            transform: scale(0.95);
        }
        
        /* Chat-Interface Styles */
        .chat-search-container {
            max-width: 800px;
            margin: 0 auto 50px;
            padding: 40px 20px;
        }
        .chat-icon {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .chat-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
        }
        .chat-subtitle {
            font-size: 1.2rem;
        }
        .smart-search-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 30px;
            position: relative;
        }
        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-icon {
            position: absolute;
            left: 20px;
            color: #6c757d;
            font-size: 1.3rem;
            pointer-events: none;
        }
        .smart-search-input {
            width: 100%;
            padding: 18px 50px 18px 55px;
            border: 3px solid #e9ecef;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        .smart-search-input:focus {
            outline: none;
            border-color: #0d6efd;
            background: white;
            box-shadow: 0 0 0 4px rgba(13,110,253,0.1);
        }
        .clear-search-btn {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s;
            padding: 5px;
        }
        .clear-search-btn:hover {
            color: #dc3545;
            transform: rotate(90deg);
        }
        .suggestions-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            left: 30px;
            right: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            max-height: 500px;
            overflow-y: auto;
            z-index: 1000;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .suggestions-section {
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .suggestions-section:last-child {
            border-bottom: none;
        }
        .suggestions-header {
            padding: 10px 20px;
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .suggestion-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .suggestion-item:hover {
            background: #f8f9fa;
            padding-left: 30px;
        }
        .suggestion-item i {
            color: #0d6efd;
        }
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
        }
        .active-filter-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        .active-filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .remove-filter {
            background: rgba(255,255,255,0.3);
            border: none;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 1.2rem;
            line-height: 1;
        }
        .remove-filter:hover {
            background: rgba(255,255,255,0.5);
            transform: scale(1.1);
        }
        .search-help {
            margin-top: 15px;
        }
        .results-count {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .chat-title {
                font-size: 1.8rem;
            }
            .chat-subtitle {
                font-size: 1rem;
            }
            .smart-search-box {
                padding: 20px;
            }
            .smart-search-input {
                font-size: 1rem;
                padding: 15px 45px 15px 50px;
            }
            .suggestions-dropdown {
                left: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/T2P_transparent_2.svg" alt="T2P Logo" style="height: 45px; margin-right: 10px;">
                <span>Termin2Praxis</span>
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <!-- Button für Arztbewerbung -->
                <button class="btn btn-success btn-sm me-3" data-bs-toggle="modal" data-bs-target="#arztBewerbungModal">
                    <i class="bi bi-hospital"></i> Sie sind Arzt?
                </button>
                
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
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
                                <li><h6 class="dropdown-header">Benachrichtigungen</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php 
                                // Nur bestätigte, abgelehnte und stornierte Termine für Benachrichtigungen abrufen
                                $user_id_notif = $_SESSION['user_id'];
                                $conn_notif = getDBConnection();
                                $sql_all = "SELECT * FROM appointments WHERE user_id = ? AND status IN ('bestätigt', 'abgelehnt', 'storniert') ORDER BY is_read ASC, date DESC, time DESC LIMIT 10";
                                $stmt_all = $conn_notif->prepare($sql_all);
                                $stmt_all->bind_param("i", $user_id_notif);
                                $stmt_all->execute();
                                $all_termine = $stmt_all->get_result();
                                
                                if ($all_termine->num_rows === 0): 
                                ?>
                                    <li><a class="dropdown-item text-muted"><small>Noch keine Terminbestätigungen</small></a></li>
                                <?php else: ?>
                                    <?php 
                                    $count = 0;
                                    $total = $all_termine->num_rows;
                                    while ($notif = $all_termine->fetch_assoc()): 
                                        $count++;
                                    ?>
                                        <li>
                                            <a class="dropdown-item" href="#meineTermine">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-grow-1">
                                                        <strong><?php echo date('d.m.Y', strtotime($notif['date'])); ?></strong> um <?php echo date('H:i', strtotime($notif['time'])); ?> Uhr<br>
                                                        <?php if ($notif['status'] === 'bestätigt'): ?>
                                                            <span class="badge bg-success">✓ Bestätigt</span>
                                                            <small class="text-success">Ihr Termin wurde vom Arzt bestätigt</small>
                                                        <?php elseif ($notif['status'] === 'abgelehnt'): ?>
                                                            <span class="badge bg-danger">✗ Abgelehnt</span>
                                                            <small class="text-danger">Ihr Termin wurde leider abgelehnt</small>
                                                        <?php elseif ($notif['status'] === 'storniert'): ?>
                                                            <span class="badge bg-secondary">⛔ Storniert</span>
                                                            <small class="text-danger">Ihr Termin wurde vom Arzt storniert. Bitte buchen Sie einen neuen Termin.</small>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">⏳ Angefragt</span>
                                                            <small class="text-muted">Wartet auf Bestätigung</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                        <?php if ($count < $total): ?>
                                            <li><hr class="dropdown-divider"></li>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php 
                                endif;
                                $stmt_all->close();
                                $conn_notif->close();
                                ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center text-primary" href="#meineTermine">Alle Termine anzeigen</a></li>
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

    <div class="container mt-5">
        <!-- Hero Section -->
        <div class="text-center mb-5 py-5">
            <h1 class="display-3 fw-bold mb-4">Online ganz einfach Termin machen</h1>
            <p class="lead fs-3 mb-4">Ohne Anrufen - Direkt online buchen!</p>
            <p class="fs-5 text-muted mb-4">Wählen Sie eine Arztpraxis aus und buchen Sie Ihren Wunschtermin</p>
            <a href="#praxenUebersicht" class="btn btn-primary btn-lg px-5 py-3 fs-4">
                Arztpraxen ansehen
            </a>
        </div>

        <!-- Info für nicht eingeloggte Benutzer -->
        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info text-center mb-5">
                <i class="bi bi-info-circle"></i> 
                <strong>Hinweis:</strong> Zum Buchen eines Termins müssen Sie sich <a href="login.php" class="alert-link fw-bold">anmelden</a> oder registrieren.
            </div>
        <?php endif; ?>

        <?php if ($meine_termine && $meine_termine->num_rows > 0): ?>
            <!-- Meine Termine (nur für eingeloggte Patienten) -->
            <div class="card mb-5 shadow" id="meineTermine">
                <div class="card-header bg-info text-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">✓ Meine aktuellen Termine 
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $notification_count; ?> neue Updates</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Praxis</th>
                                    <th>Datum</th>
                                    <th>Uhrzeit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($termin = $meine_termine->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($termin['praxis_name']); ?></strong></td>
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

        <!-- Chat-Interface für intelligente Arztsuche -->
        <div class="chat-search-container">
            <div class="chat-hero text-center mb-4">
                <div class="chat-icon mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                        <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                    </svg>
                </div>
                <h2 class="chat-title mb-2">Finden Sie Ihren perfekten Arzt</h2>
                <p class="chat-subtitle text-muted">Mit wenigen Klicks zum passenden Facharzt in Ihrer Nähe</p>
            </div>

            <!-- Intelligentes Suchfeld -->
            <div class="smart-search-box">
                <div class="search-input-wrapper">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" 
                           id="smartSearch" 
                           class="smart-search-input" 
                           placeholder="Beschreiben Sie Ihr Anliegen, z.B. 'Hautarzt', 'Herz', 'Kinderarzt'..."
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           autocomplete="off">
                    <?php if (!empty($search_query) || !empty($filter_kategorie) || !empty($filter_spezialgebiet)): ?>
                        <button class="clear-search-btn" id="clearSearch" title="Suche zurücksetzen">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Vorschläge Dropdown -->
                <div class="suggestions-dropdown" id="suggestionsDropdown" style="display: none;">
                    <div class="suggestions-section">
                        <div class="suggestions-header">💡 Schnellzugriff</div>
                        <div class="suggestion-item" data-type="quick" data-value="">
                            <i class="bi bi-list-ul"></i> Alle Ärzte anzeigen
                        </div>
                    </div>
                    
                    <div class="suggestions-section" id="kategorieSection">
                        <div class="suggestions-header">📂 Kategorien</div>
                        <?php foreach ($kategorien as $kat): ?>
                            <div class="suggestion-item" data-type="kategorie" data-value="<?php echo htmlspecialchars($kat); ?>">
                                <i class="bi bi-folder2-open"></i> <?php echo htmlspecialchars($kat); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="suggestions-section" id="fachgebietSection">
                        <div class="suggestions-header">🏥 Fachgebiete</div>
                        <?php foreach ($spezialgebiete as $spez): ?>
                            <div class="suggestion-item" data-type="spezialgebiet" data-value="<?php echo htmlspecialchars($spez); ?>">
                                <i class="bi bi-hospital"></i> <?php echo htmlspecialchars($spez); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Aktive Filter Anzeige -->
                <?php if (!empty($filter_kategorie) || !empty($filter_spezialgebiet)): ?>
                    <div class="active-filters mt-3">
                        <span class="active-filter-label">Aktiver Filter:</span>
                        <?php if (!empty($filter_kategorie)): ?>
                            <span class="active-filter-badge">
                                <i class="bi bi-folder2-open"></i> <?php echo htmlspecialchars($filter_kategorie); ?>
                                <button class="remove-filter" onclick="window.location.href='index.php'">×</button>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($filter_spezialgebiet)): ?>
                            <span class="active-filter-badge">
                                <i class="bi bi-hospital"></i> <?php echo htmlspecialchars($filter_spezialgebiet); ?>
                                <button class="remove-filter" onclick="window.location.href='index.php'">×</button>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hilfetext -->
            <div class="search-help text-center mt-3">
                <small class="text-muted">
                    💬 Tipp: Klicken Sie in das Suchfeld für Vorschläge oder tippen Sie einfach los
                </small>
            </div>
        </div>

        <!-- Standort-Verwaltung -->
        <div class="mb-4" id="locationManager" style="display: none;">
            <div class="alert alert-success d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-geo-alt-fill me-2"></i>
                    <strong>Standort aktiviert:</strong> <span id="locationStatus">Praxen werden nach Entfernung sortiert</span>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-primary" id="updateLocationBtn">
                        <i class="bi bi-arrow-clockwise"></i> Aktualisieren
                    </button>
                </div>
            </div>
        </div>

        <!-- Arztpraxen Übersicht -->
        <div class="mb-5" id="praxenUebersicht">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="display-5 fw-bold mb-2">🏥 Unsere Arztpraxen</h2>
                    <p class="results-count">
                        <span id="totalPraxenCount"><?php echo $total_count; ?></span> 
                        <span id="praxenLabel"><?php echo $total_count === 1 ? 'Praxis' : 'Praxen'; ?></span> gefunden
                        <?php if ($praxen->num_rows < $total_count): ?>
                            <span class="text-muted" id="displayedCount"> (<span id="displayedNumber"><?php echo $praxen->num_rows; ?></span> angezeigt)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <button class="btn <?php echo $only_open ? 'btn-success' : 'btn-outline-secondary'; ?> btn-lg" id="toggleOpenFilter" title="Nur Praxen anzeigen, die aktuell geöffnet sind">
                        <i class="bi <?php echo $only_open ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i> 
                        <span class="d-none d-sm-inline">Nur geöffnete Praxen</span>
                        <span class="d-inline d-sm-none">Nur geöffnet</span>
                    </button>
                </div>
            </div>
            
            <?php if ($praxen->num_rows === 0): ?>
                <div class="alert alert-warning text-center">
                    <h5><i class="bi bi-exclamation-triangle"></i> Keine Arztpraxen gefunden</h5>
                    <p class="mb-0">
                        Es wurden keine Praxen gefunden, die Ihren Suchkriterien entsprechen.<br>
                        <small class="text-muted">
                            Mögliche Gründe: Keine Praxen in Ihrer Nähe verfügbar, Filter zu spezifisch, 
                            oder alle Praxen sind derzeit ausgebucht. Versuchen Sie, die Filter anzupassen oder die Suche zu erweitern.
                        </small>
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-4" id="praxenContainer">
                    <?php while ($praxis = $praxen->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card praxis-card shadow-sm" onclick="window.location.href='praxis_termine.php?id=<?php echo $praxis['id']; ?>'">
                                <div class="position-relative">
                                    <?php if (!empty($praxis['bild_url']) && $praxis['bild_url'] !== 'https://via.placeholder.com/400x300?text=Arztpraxis'): ?>
                                        <img src="<?php echo htmlspecialchars($praxis['bild_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($praxis['name']); ?>" 
                                             class="praxis-card-img">
                                    <?php else: ?>
                                        <div class="praxis-card-img d-flex align-items-center justify-content-center bg-light">
                                            <div class="text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" viewBox="0 0 16 16">
                                                    <path d="M8 9.05a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                                    <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2Zm10.798 11c-.453-1.27-1.76-3-4.798-3-3.037 0-4.345 1.73-4.798 3H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1.202Z"/>
                                                </svg>
                                                <p class="mb-0 mt-2 text-muted fw-bold">Foto folgt</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <span class="praxis-badge text-primary">
                                        <?php echo htmlspecialchars($praxis['spezialgebiet']); ?>
                                    </span>
                                    <?php if (isset($praxis['distance'])): ?>
                                        <div class="position-absolute top-0 start-0 m-3">
                                            <span class="badge bg-success">
                                                <i class="bi bi-geo-alt-fill"></i> <?= number_format($praxis['distance'], 1) ?> km
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Auslastungsstatus -->
                                    <?php 
                                    $occupancy_status = $praxis['occupancy_status'] ?? 'low';
                                    $status_config = [
                                        'low' => ['text' => 'Nicht überlaufen', 'bg' => 'success', 'icon' => 'emoji-smile'],
                                        'medium' => ['text' => 'Mittel überlaufen', 'bg' => 'warning', 'icon' => 'emoji-neutral'],
                                        'high' => ['text' => 'Stark überlaufen', 'bg' => 'danger', 'icon' => 'emoji-frown']
                                    ];
                                    $status = $status_config[$occupancy_status];
                                    ?>
                                    <div class="position-absolute bottom-0 end-0 m-3">
                                        <span class="badge bg-<?php echo $status['bg']; ?> text-white occupancy-badge" style="font-size: 0.9rem; padding: 8px 12px;">
                                            <i class="bi bi-<?php echo $status['icon']; ?>"></i> <?php echo $status['text']; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (isset($praxis['accepting_bookings']) && !$praxis['accepting_bookings']): ?>
                                    <span class="praxis-badge bg-danger text-white" style="top: 55px;">
                                        <i class="bi bi-pause-circle"></i> Buchungen gestoppt
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-4">
                                    <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($praxis['name']); ?></h5>
                                    <p class="card-text text-muted mb-3"><?php echo htmlspecialchars(substr($praxis['beschreibung'], 0, 120)) . '...'; ?></p>
                                    <div class="d-flex flex-column gap-2 small text-muted">
                                        <div>
                                            <i class="bi bi-geo-alt-fill"></i> 
                                            <?php 
                                                echo htmlspecialchars($praxis['adresse']);
                                                if (!empty($praxis['plz']) || !empty($praxis['stadt'])) {
                                                    echo ', ' . htmlspecialchars($praxis['plz']) . ' ' . htmlspecialchars($praxis['stadt']);
                                                }
                                            ?>
                                        </div>
                                        <div>
                                            <i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($praxis['telefon']); ?>
                                        </div>
                                        <?php if (!empty($praxis['opening_time']) && !empty($praxis['closing_time'])): ?>
                                        <div>
                                            <i class="bi bi-clock-fill"></i> 
                                            <strong>Öffnungszeiten:</strong> 
                                            <?php echo date('H:i', strtotime($praxis['opening_time'])); ?> - <?php echo date('H:i', strtotime($praxis['closing_time'])); ?> Uhr
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($praxis['reachability_start']) && !empty($praxis['reachability_end'])): ?>
                                        <div>
                                            <i class="bi bi-telephone-inbound-fill text-info"></i> 
                                            <strong>Erreichbar:</strong> 
                                            <?php echo date('H:i', strtotime($praxis['reachability_start'])); ?> - <?php echo date('H:i', strtotime($praxis['reachability_end'])); ?> Uhr
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($praxis['versicherungsart'])): ?>
                                        <div>
                                            <i class="bi bi-shield-fill-check"></i> 
                                            <strong>Versicherung:</strong> 
                                            <?php 
                                                if ($praxis['versicherungsart'] === 'Gesetzlich') {
                                                    echo '<span class="badge bg-info">Gesetzlich</span>';
                                                } elseif ($praxis['versicherungsart'] === 'Privat') {
                                                    echo '<span class="badge bg-warning text-dark">Privat</span>';
                                                } else {
                                                    echo '<span class="badge bg-success">Gesetzlich & Privat</span>';
                                                }
                                            ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($praxis['accepting_bookings']) && !$praxis['accepting_bookings']): ?>
                                    <div class="alert alert-warning mt-3 mb-0 py-2 small">
                                        <i class="bi bi-info-circle"></i> <strong>Hinweis:</strong> Diese Praxis nimmt derzeit keine neuen Termine an. Bitte wählen Sie eine andere Praxis.
                                    </div>
                                    <button class="btn btn-secondary w-100 mt-4" disabled>
                                        <i class="bi bi-pause-circle"></i> Keine Buchungen möglich
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-primary w-100 mt-4">
                                        Termine ansehen →
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Mehr laden Button -->
                <?php if ($praxen->num_rows < $total_count): ?>
                    <div class="text-center mt-5" id="loadMoreContainer">
                        <button class="btn btn-primary btn-lg px-5 py-3" id="loadMoreBtn">
                            <i class="bi bi-arrow-down-circle"></i> Mehr laden
                            <span class="ms-2 badge bg-light text-primary"><?php echo ($total_count - $praxen->num_rows); ?> weitere</span>
                        </button>
                        <div class="spinner-border text-primary mt-3" role="status" id="loadingSpinner" style="display: none;">
                            <span class="visually-hidden">Lädt...</span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Call-to-Action Sektion für Ärzte -->
        <div class="container my-5 py-5">
            <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body text-center text-white p-5">
                    <h2 class="display-4 fw-bold mb-3">
                        <i class="bi bi-hospital"></i> Sie sind Arzt oder Praxisinhaber?
                    </h2>
                    <p class="lead fs-4 mb-4">
                        Erweitern Sie Ihre Reichweite und vereinfachen Sie Ihre Terminvergabe!<br>
                        Werden Sie Teil von Termin2Praxis.
                    </p>
                    <button class="btn btn-light btn-lg px-5 py-3" data-bs-toggle="modal" data-bs-target="#arztBewerbungModal" style="border-radius: 50px; font-weight: 600;">
                        <i class="bi bi-clipboard-check"></i> Jetzt Praxis anmelden
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Arzt Bewerbungsformular Modal -->
    <div class="modal fade" id="arztBewerbungModal" tabindex="-1" aria-labelledby="arztBewerbungModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fs-3 fw-bold" id="arztBewerbungModalLabel">
                        <i class="bi bi-hospital"></i> Praxis bei Termin2Praxis anmelden
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i>
                        <strong>Hinweis:</strong> Nach Ihrer Bewerbung prüfen wir Ihre Angaben und richten Ihnen dann einen Account ein.
                    </div>
                    
                    <form id="arztBewerbungForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="praxisName" class="form-label fw-bold">Name der Praxis *</label>
                                <input type="text" class="form-control" id="praxisName" name="praxisName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="arztName" class="form-label fw-bold">Ihr Name *</label>
                                <input type="text" class="form-control" id="arztName" name="arztName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="fachgebiet" class="form-label fw-bold">Fachgebiet/Spezialgebiet *</label>
                                <select class="form-select" id="fachgebiet" name="fachgebiet" required>
                                    <option value="">Bitte wählen...</option>
                                    <option value="Allgemeinmedizin">Allgemeinmedizin</option>
                                    <option value="Kardiologie">Kardiologie</option>
                                    <option value="Dermatologie">Dermatologie</option>
                                    <option value="Orthopädie">Orthopädie</option>
                                    <option value="Pädiatrie">Pädiatrie (Kinderarzt)</option>
                                    <option value="Gynäkologie">Gynäkologie</option>
                                    <option value="HNO">HNO (Hals-Nasen-Ohren)</option>
                                    <option value="Neurologie">Neurologie</option>
                                    <option value="Psychiatrie">Psychiatrie</option>
                                    <option value="Radiologie">Radiologie</option>
                                    <option value="Urologie">Urologie</option>
                                    <option value="Augenheilkunde">Augenheilkunde</option>
                                    <option value="Zahnmedizin">Zahnmedizin</option>
                                    <option value="Sonstiges">Sonstiges</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="kategorie" class="form-label fw-bold">Kategorie *</label>
                                <select class="form-select" id="kategorie" name="kategorie" required>
                                    <option value="">Bitte wählen...</option>
                                    <option value="Hausarzt">Hausarzt</option>
                                    <option value="Facharzt">Facharzt</option>
                                    <option value="Zahnarzt">Zahnarzt</option>
                                    <option value="Klinik">Klinik</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="adresse" class="form-label fw-bold">Vollständige Adresse *</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="2" required placeholder="Straße, Hausnummer, PLZ, Ort"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="telefon" class="form-label fw-bold">Telefon *</label>
                                <input type="tel" class="form-control" id="telefon" name="telefon" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold">E-Mail *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-12">
                                <label for="website" class="form-label fw-bold">Website (optional)</label>
                                <input type="url" class="form-control" id="website" name="website" placeholder="https://">
                            </div>
                            <div class="col-12">
                                <label for="nachricht" class="form-label fw-bold">Zusätzliche Informationen (optional)</label>
                                <textarea class="form-control" id="nachricht" name="nachricht" rows="3" placeholder="Erzählen Sie uns mehr über Ihre Praxis..."></textarea>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-4 mb-0">
                            <small>
                                <i class="bi bi-shield-check"></i>
                                <strong>Datenschutz:</strong> Ihre Daten werden vertraulich behandelt und ausschließlich zur Prüfung Ihrer Bewerbung verwendet.
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top: 2px solid #e9ecef;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-primary" id="submitBewerbung" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="bi bi-send"></i> Bewerbung absenden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2026 Termin2Praxis. Alle Rechte vorbehalten.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="legal/impressum.php" class="text-white text-decoration-none me-3">Impressum</a>
                    <a href="legal/datenschutz.php" class="text-white text-decoration-none me-3">Datenschutz</a>
                    <a href="legal/agb.php" class="text-white text-decoration-none">AGB</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll-Position SOFORT wiederherstellen (vor allem anderen)
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        const savedScrollPosition = sessionStorage.getItem('scrollPosition');
        if (savedScrollPosition) {
            window.scrollTo(0, parseInt(savedScrollPosition));
            sessionStorage.removeItem('scrollPosition');
        }
        
        // Standortverwaltung
        const locationManager = {
            storageKey: 'userLocation',
            
            // Standort aus LocalStorage laden
            getStoredLocation() {
                const stored = localStorage.getItem(this.storageKey);
                return stored ? JSON.parse(stored) : null;
            },
            
            // Standort in LocalStorage speichern
            saveLocation(lat, lng) {
                localStorage.setItem(this.storageKey, JSON.stringify({ lat, lng, timestamp: Date.now() }));
            },
            
            // Standort aus LocalStorage löschen
            deleteLocation() {
                localStorage.removeItem(this.storageKey);
            },
            
            // Standort vom Browser abfragen
            requestLocation(callback) {
                if (!navigator.geolocation) {
                    alert('Ihr Browser unterstützt keine Standortabfrage.');
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        this.saveLocation(lat, lng);
                        if (callback) callback(lat, lng);
                    },
                    (error) => {
                        console.error('Standortabfrage fehlgeschlagen:', error);
                        if (error.code === error.PERMISSION_DENIED) {
                            alert('Standortzugriff wurde verweigert. Sie können die Praxen nicht nach Entfernung sortieren.');
                        } else {
                            alert('Standort konnte nicht ermittelt werden.');
                        }
                    }
                );
            },
            
            // URL mit Standortparametern aktualisieren
            updateURLWithLocation(lat, lng) {
                // Scroll-Position speichern
                sessionStorage.setItem('scrollPosition', window.scrollY);
                
                const url = new URL(window.location.href);
                url.searchParams.set('lat', lat);
                url.searchParams.set('lng', lng);
                window.location.href = url.toString();
            },
            
            // URL ohne Standortparameter
            removeLocationFromURL() {
                // Scroll-Position speichern
                sessionStorage.setItem('scrollPosition', window.scrollY);
                
                const url = new URL(window.location.href);
                url.searchParams.delete('lat');
                url.searchParams.delete('lng');
                window.location.href = url.toString();
            },
            
            // Initialisierung
            init() {
                const urlParams = new URLSearchParams(window.location.search);
                const hasLocationInURL = urlParams.has('lat') && urlParams.has('lng');
                const storedLocation = this.getStoredLocation();
                
                // Standort-Manager UI anzeigen wenn Standort aktiv
                if (hasLocationInURL) {
                    document.getElementById('locationManager').style.display = 'block';
                }
                
                // Bei Seitenaufruf ohne Standort: Abfrage direkt starten
                if (!hasLocationInURL && !storedLocation) {
                    this.requestLocation((lat, lng) => {
                        this.updateURLWithLocation(lat, lng);
                    });
                } else if (!hasLocationInURL && storedLocation) {
                    // Gespeicherten Standort verwenden
                    this.updateURLWithLocation(storedLocation.lat, storedLocation.lng);
                }
                
                // Event-Listener für Aktualisieren-Button
                const updateBtn = document.getElementById('updateLocationBtn');
                
                if (updateBtn) {
                    updateBtn.addEventListener('click', () => {
                        this.requestLocation((lat, lng) => {
                            this.updateURLWithLocation(lat, lng);
                        });
                    });
                }
            }
        };
        
        // Standortverwaltung initialisieren
        document.addEventListener('DOMContentLoaded', function() {
            locationManager.init();
        });
        
        // Toggle für "Nur geöffnete Praxen" Filter
        document.addEventListener('DOMContentLoaded', function() {
            const toggleOpenFilter = document.getElementById('toggleOpenFilter');
            if (toggleOpenFilter) {
                toggleOpenFilter.addEventListener('click', function() {
                    // Scroll-Position speichern
                    sessionStorage.setItem('scrollPosition', window.scrollY);
                    
                    const urlParams = new URLSearchParams(window.location.search);
                    
                    // Toggle zwischen aktiviert und deaktiviert
                    if (urlParams.get('only_open') === '1') {
                        urlParams.delete('only_open');
                    } else {
                        urlParams.set('only_open', '1');
                    }
                    
                    // Zur neuen URL navigieren (ohne Hash)
                    const newUrl = urlParams.toString() ? 'index.php?' + urlParams.toString() : 'index.php';
                    window.location.href = newUrl;
                });
            }
        });
        
        // Smart Search mit Vorschlägen
        document.addEventListener('DOMContentLoaded', function() {
            const smartSearch = document.getElementById('smartSearch');
            const suggestionsDropdown = document.getElementById('suggestionsDropdown');
            const clearSearchBtn = document.getElementById('clearSearch');
            
            // Vorschläge bei Fokus anzeigen
            if (smartSearch) {
                smartSearch.addEventListener('focus', function() {
                    suggestionsDropdown.style.display = 'block';
                    filterSuggestions(this.value);
                });

                // Live-Filter beim Tippen
                smartSearch.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    filterSuggestions(query);
                    suggestionsDropdown.style.display = 'block';
                });

                // Suche bei Enter
                smartSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        applySearch();
                    }
                });
            }

            // Vorschläge filtern
            function filterSuggestions(query) {
                const kategorieSection = document.getElementById('kategorieSection');
                const fachgebietSection = document.getElementById('fachgebietSection');
                
                if (!query) {
                    // Alle anzeigen
                    kategorieSection.style.display = 'block';
                    fachgebietSection.style.display = 'block';
                    return;
                }

                // Filter Kategorien
                const kategorieItems = kategorieSection.querySelectorAll('.suggestion-item');
                let hasVisibleKategorie = false;
                kategorieItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(query)) {
                        item.style.display = 'flex';
                        hasVisibleKategorie = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                kategorieSection.style.display = hasVisibleKategorie ? 'block' : 'none';

                // Filter Fachgebiete
                const fachgebietItems = fachgebietSection.querySelectorAll('.suggestion-item');
                let hasVisibleFachgebiet = false;
                fachgebietItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(query)) {
                        item.style.display = 'flex';
                        hasVisibleFachgebiet = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                fachgebietSection.style.display = hasVisibleFachgebiet ? 'block' : 'none';
            }

            // Außerhalb klicken schließt Dropdown
            document.addEventListener('click', function(e) {
                if (!smartSearch.contains(e.target) && !suggestionsDropdown.contains(e.target)) {
                    suggestionsDropdown.style.display = 'none';
                }
            });

            // Vorschlag-Items anklicken
            const suggestionItems = document.querySelectorAll('.suggestion-item');
            suggestionItems.forEach(item => {
                item.addEventListener('click', function() {
                    const type = this.dataset.type;
                    const value = this.dataset.value;
                    
                    const params = new URLSearchParams(window.location.search);
                    
                    if (type === 'kategorie' && value) {
                        params.set('kategorie', value);
                    } else if (type === 'spezialgebiet' && value) {
                        params.set('spezialgebiet', value);
                    }
                    
                    // Standortparameter beibehalten
                    if (params.has('lat') && params.has('lng')) {
                        // Standort bleibt in params
                    }
                    
                    const queryString = params.toString();
                    const url = queryString ? 'index.php?' + queryString + '#praxenUebersicht' : 'index.php#praxenUebersicht';
                    window.location.href = url;
                });
            });

            // Suche zurücksetzen
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    window.location.href = 'index.php';
                });
            }

            // Suche anwenden
            function applySearch() {
                const query = smartSearch.value.trim();
                if (query) {
                    const params = new URLSearchParams(window.location.search);
                    params.set('search', query);
                    
                    // Standortparameter beibehalten
                    const url = 'index.php?' + params.toString() + '#praxenUebersicht';
                    window.location.href = url;
                }
            }

            // Smooth Scroll zu Ergebnissen
            if (window.location.hash === '#praxenUebersicht') {
                setTimeout(function() {
                    document.getElementById('praxenUebersicht')?.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            }
        });

        // Benachrichtigungen als gelesen markieren
        <?php if (isLoggedIn() && hasRole('patient')): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationDropdown = document.getElementById('notificationDropdown');
            if (notificationDropdown) {
                notificationDropdown.addEventListener('click', function() {
                    fetch('api/mark_notifications_read.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            setTimeout(function() {
                                const badge = notificationDropdown.querySelector('.badge');
                                if (badge) {
                                    badge.style.transition = 'opacity 0.3s';
                                    badge.style.opacity = '0';
                                    setTimeout(function() {
                                        badge.style.display = 'none';
                                    }, 300);
                                }
                            }, 500);
                        }
                    })
                    .catch(error => {
                        console.error('Fehler beim Markieren der Benachrichtigungen:', error);
                    });
                });
            }
        });
        <?php endif; ?>

        // Mehr laden Funktionalität
        let currentOffset = 6;
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const praxenContainer = document.getElementById('praxenContainer');
        
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                loadMoreBtn.style.display = 'none';
                loadingSpinner.style.display = 'block';
                
                // Filter-Parameter sammeln
                const urlParams = new URLSearchParams(window.location.search);
                const params = new URLSearchParams();
                params.append('offset', currentOffset);
                
                if (urlParams.has('kategorie')) params.append('kategorie', urlParams.get('kategorie'));
                if (urlParams.has('spezialgebiet')) params.append('spezialgebiet', urlParams.get('spezialgebiet'));
                if (urlParams.has('lat')) params.append('lat', urlParams.get('lat'));
                if (urlParams.has('lng')) params.append('lng', urlParams.get('lng'));
                if (urlParams.has('search')) params.append('search', urlParams.get('search'));
                if (urlParams.has('sort')) params.append('sort', urlParams.get('sort'));
                if (urlParams.has('only_open')) params.append('only_open', urlParams.get('only_open'));
                
                fetch('api/load_more_praxen.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.praxen.length > 0) {
                            data.praxen.forEach(praxis => {
                                const col = document.createElement('div');
                                col.className = 'col-md-6 col-lg-4';
                                
                                // Bild-HTML basierend auf Verfügbarkeit
                                let imageHtml;
                                if (praxis.bild_url && praxis.bild_url !== 'https://via.placeholder.com/400x300?text=Arztpraxis') {
                                    imageHtml = `<img src="${praxis.bild_url}" alt="${praxis.name}" class="praxis-card-img">`;
                                } else {
                                    imageHtml = `
                                        <div class="praxis-card-img d-flex align-items-center justify-content-center bg-light">
                                            <div class="text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" viewBox="0 0 16 16">
                                                    <path d="M8 9.05a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                                    <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2Zm10.798 11c-.453-1.27-1.76-3-4.798-3-3.037 0-4.345 1.73-4.798 3H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-1.202Z"/>
                                                </svg>
                                                <p class="mb-0 mt-2 text-muted fw-bold">Foto folgt</p>
                                            </div>
                                        </div>`;
                                }
                                
                                // Auslastungsstatus-Konfiguration
                                const occupancyStatus = praxis.occupancy_status || 'low';
                                const statusConfig = {
                                    'low': { text: 'Nicht überlaufen', bg: 'success', icon: 'emoji-smile' },
                                    'medium': { text: 'Mittel überlaufen', bg: 'warning', icon: 'emoji-neutral' },
                                    'high': { text: 'Stark überlaufen', bg: 'danger', icon: 'emoji-frown' }
                                };
                                const status = statusConfig[occupancyStatus];
                                
                                col.innerHTML = `
                                    <div class="card praxis-card shadow-sm" onclick="window.location.href='praxis_termine.php?id=${praxis.id}'">
                                        <div class="position-relative">
                                            ${imageHtml}
                                            <span class="praxis-badge text-primary">
                                                ${praxis.spezialgebiet || praxis.kategorie}
                                            </span>
                                            ${praxis.distance ? `
                                            <div class="position-absolute top-0 start-0 m-3">
                                                <span class="badge bg-success">
                                                    <i class="bi bi-geo-alt-fill"></i> ${praxis.distance} km
                                                </span>
                                            </div>
                                            ` : ''}
                                            
                                            <div class="position-absolute bottom-0 end-0 m-3">
                                                <span class="badge bg-${status.bg} text-white occupancy-badge" style="font-size: 0.9rem; padding: 8px 12px;">
                                                    <i class="bi bi-${status.icon}"></i> ${status.text}
                                                </span>
                                            </div>
                                            
                                            ${!praxis.accepting_bookings ? `
                                            <span class="praxis-badge bg-danger text-white" style="top: 55px;">
                                                <i class="bi bi-pause-circle"></i> Buchungen gestoppt
                                            </span>
                                            ` : ''}
                                        </div>
                                        <div class="card-body p-4">
                                            <h5 class="card-title fw-bold mb-3">${praxis.name}</h5>
                                            <p class="card-text text-muted mb-3">${praxis.beschreibung.substring(0, 120)}...</p>
                                            <div class="d-flex flex-column gap-2 small text-muted">
                                                <div>
                                                    <i class="bi bi-geo-alt-fill"></i> 
                                                    ${praxis.adresse}${(praxis.plz || praxis.stadt) ? ', ' + (praxis.plz || '') + ' ' + (praxis.stadt || '') : ''}
                                                </div>
                                                <div><i class="bi bi-telephone-fill"></i> ${praxis.telefon}</div>
                                                ${(praxis.opening_time && praxis.closing_time) ? `
                                                <div>
                                                    <i class="bi bi-clock-fill"></i> 
                                                    <strong>Öffnungszeiten:</strong> 
                                                    ${praxis.opening_time.substring(0, 5)} - ${praxis.closing_time.substring(0, 5)} Uhr
                                                </div>` : ''}
                                                ${(praxis.reachability_start && praxis.reachability_end) ? `
                                                <div>
                                                    <i class="bi bi-telephone-inbound-fill text-info"></i> 
                                                    <strong>Erreichbar:</strong> 
                                                    ${praxis.reachability_start.substring(0, 5)} - ${praxis.reachability_end.substring(0, 5)} Uhr
                                                </div>` : ''}
                                                ${praxis.versicherungsart ? `
                                                <div>
                                                    <i class="bi bi-shield-fill-check"></i> 
                                                    <strong>Versicherung:</strong> 
                                                    ${praxis.versicherungsart === 'Gesetzlich' ? '<span class="badge bg-info">Gesetzlich</span>' : 
                                                      praxis.versicherungsart === 'Privat' ? '<span class="badge bg-warning text-dark">Privat</span>' : 
                                                      '<span class="badge bg-success">Gesetzlich & Privat</span>'}
                                                </div>` : ''}
                                            </div>
                                            ${!praxis.accepting_bookings ? `
                                            <div class="alert alert-warning mt-3 mb-0 py-2 small">
                                                <i class="bi bi-info-circle"></i> <strong>Hinweis:</strong> Diese Praxis nimmt derzeit keine neuen Termine an. Bitte wählen Sie eine andere Praxis.
                                            </div>
                                            <button class="btn btn-secondary w-100 mt-4" disabled>
                                                <i class="bi bi-pause-circle"></i> Keine Buchungen möglich
                                            </button>
                                            ` : `
                                            <button class="btn btn-primary w-100 mt-4">
                                                Termine ansehen →
                                            </button>
                                            `}
                                        </div>
                                    </div>
                                `;
                                praxenContainer.appendChild(col);
                            });
                            
                            currentOffset += data.praxen.length;
                            
                            // Anzeige-Zähler aktualisieren
                            const displayedNumber = document.getElementById('displayedNumber');
                            const displayedCount = document.getElementById('displayedCount');
                            if (displayedNumber) {
                                displayedNumber.textContent = currentOffset;
                            }
                            // Zeige den Zähler an, falls er vorher nicht da war
                            if (displayedCount && currentOffset < data.total) {
                                displayedCount.style.display = 'inline';
                                displayedCount.innerHTML = ` (<span id="displayedNumber">${currentOffset}</span> angezeigt)`;
                            } else if (displayedCount && currentOffset >= data.total) {
                                // Verstecke "angezeigt" wenn alle geladen sind
                                displayedCount.style.display = 'none';
                            }
                            
                            // Button wieder anzeigen oder verstecken, dabei Zentrierung beibehalten
                            const loadMoreContainer = document.getElementById('loadMoreContainer');
                            if (data.hasMore) {
                                loadMoreBtn.style.display = 'inline-block';
                                loadMoreBtn.querySelector('.badge').textContent = data.remaining + ' weitere';
                                loadMoreContainer.className = 'text-center mt-5';
                            } else {
                                loadMoreContainer.style.display = 'none';
                            }
                        } else {
                            document.getElementById('loadMoreContainer').style.display = 'none';
                        }
                        loadingSpinner.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Fehler beim Laden weiterer Praxen:', error);
                        loadMoreBtn.style.display = 'block';
                        loadingSpinner.style.display = 'none';
                        alert('Fehler beim Laden weiterer Praxen. Bitte versuchen Sie es erneut.');
                    });
            });
        }
        
        // Arzt Bewerbungsformular
        document.addEventListener('DOMContentLoaded', function() {
            const bewerbungForm = document.getElementById('arztBewerbungForm');
            const submitBtn = document.getElementById('submitBewerbung');
            const modal = document.getElementById('arztBewerbungModal');
            
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    // Formular validieren
                    if (!bewerbungForm.checkValidity()) {
                        bewerbungForm.reportValidity();
                        return;
                    }
                    
                    // Button deaktivieren während des Sendens
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Wird gesendet...';
                    
                    // FormData erstellen
                    const formData = new FormData(bewerbungForm);
                    
                    // AJAX-Anfrage
                    fetch('api/submit_arzt_bewerbung.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Erfolg - Modal schließen und Erfolgsmeldung anzeigen
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            bsModal.hide();
                            
                            // Formular zurücksetzen
                            bewerbungForm.reset();
                            
                            // Erfolgsbenachrichtigung
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                            successAlert.style.zIndex = '9999';
                            successAlert.style.maxWidth = '600px';
                            successAlert.innerHTML = `
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Bewerbung erfolgreich versendet!</strong><br>
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            document.body.appendChild(successAlert);
                            
                            // Automatisch nach 8 Sekunden ausblenden
                            setTimeout(() => {
                                successAlert.remove();
                            }, 8000);
                        } else {
                            // Fehler
                            alert('Fehler: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
                    })
                    .finally(() => {
                        // Button wieder aktivieren
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-send"></i> Bewerbung absenden';
                    });
                });
            }
            
            // Modal zurücksetzen beim Schließen
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    bewerbungForm.reset();
                });
            }
        });
    </script>
</body>
</html>
