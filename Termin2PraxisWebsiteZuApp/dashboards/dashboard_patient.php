<?php
require_once '../includes/config.php';
requireRole('patient');

$conn = getDBConnection();

// Freie Termine laden mit Praxis-Informationen
$sql = "SELECT a.*, p.name as praxis_name, p.adresse, p.plz, p.stadt, p.telefon, p.email 
        FROM appointments a 
        LEFT JOIN praxen p ON a.praxis_id = p.id 
        WHERE a.status = 'frei' 
        ORDER BY a.date, a.time";
$freie_termine = $conn->query($sql);

// Termine des Patienten laden (angefragt und bestätigt) mit Praxis-Informationen
$user_id = $_SESSION['user_id'];
$sql = "SELECT a.*, p.name as praxis_name, p.adresse, p.plz, p.stadt, p.telefon, p.email 
        FROM appointments a 
        LEFT JOIN praxen p ON a.praxis_id = p.id 
        WHERE a.user_id = ? AND a.status IN ('angefragt', 'bestätigt') 
        ORDER BY a.date, a.time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meine_termine = $stmt->get_result();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patienten Dashboard - Termin2Praxis</title>
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
                <span>Termin2Praxis - Patient</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Willkommen, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php">Abmelden</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Meine Termine -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5>Meine Termine</h5>
            </div>
            <div class="card-body">
                <div id="meineTermine">
                    <?php if ($meine_termine->num_rows === 0): ?>
                        <p class="text-muted">Sie haben noch keine Termine gebucht.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Praxis</th>
                                        <th>Datum</th>
                                        <th>Uhrzeit</th>
                                        <th>Dauer</th>
                                        <th>Art</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($termin = $meine_termine->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if ($termin['praxis_name']): ?>
                                                    <strong><?php echo htmlspecialchars($termin['praxis_name']); ?></strong>
                                                    <?php if ($termin['stadt']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($termin['stadt']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($termin['date'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</td>
                                            <td><?php echo $termin['duration'] ? $termin['duration'] . ' Min.' : '-'; ?></td>
                                            <td><?php echo $termin['description'] ? htmlspecialchars($termin['description']) : '-'; ?></td>
                                            <td>
                                                <?php if ($termin['status'] === 'angefragt'): ?>
                                                    <span class="badge bg-warning text-dark">Angefragt</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Bestätigt</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Verfügbare Termine -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5>Verfügbare Termine buchen</h5>
            </div>
            <div class="card-body">
                <div id="verfuegbareTermine">
                    <?php if ($freie_termine->num_rows === 0): ?>
                        <p class="text-muted">Derzeit sind keine freien Termine verfügbar.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php while ($termin = $freie_termine->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <?php if ($termin['praxis_name']): ?>
                                                <h6 class="card-subtitle mb-2 text-primary">
                                                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($termin['praxis_name']); ?>
                                                </h6>
                                                <?php if ($termin['adresse'] || $termin['stadt']): ?>
                                                    <p class="card-text small text-muted mb-2">
                                                        <?php 
                                                        $adressTeile = [];
                                                        if ($termin['adresse']) $adressTeile[] = $termin['adresse'];
                                                        if ($termin['plz'] || $termin['stadt']) {
                                                            $ortTeile = [];
                                                            if ($termin['plz']) $ortTeile[] = $termin['plz'];
                                                            if ($termin['stadt']) $ortTeile[] = $termin['stadt'];
                                                            $adressTeile[] = implode(' ', $ortTeile);
                                                        }
                                                        echo htmlspecialchars(implode(', ', $adressTeile));
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <h6 class="card-title">
                                                <i class="bi bi-calendar"></i> <?php echo date('d.m.Y', strtotime($termin['date'])); ?>
                                            </h6>
                                            <p class="card-text">
                                                <strong><i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($termin['time'])); ?> Uhr</strong>
                                                <?php if ($termin['duration']): ?>
                                                    <br><small class="text-muted">Dauer: <?php echo $termin['duration']; ?> Min.</small>
                                                <?php endif; ?>
                                                <?php if ($termin['description']): ?>
                                                    <br><small class="text-info"><?php echo htmlspecialchars($termin['description']); ?></small>
                                                <?php endif; ?>
                                            </p>
                                            <button class="btn btn-primary btn-sm w-100" onclick="bookAppointment(<?php echo $termin['id']; ?>)">
                                                <i class="bi bi-calendar-check"></i> Termin buchen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/patient.js"></script>
</body>
</html>
