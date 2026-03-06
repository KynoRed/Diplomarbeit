<?php
require_once '../includes/config.php';
requireRole('arzt');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Praxen des eingeloggten Arztes laden
$sql = "SELECT * FROM praxen WHERE owner_id = ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meine_praxen = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meine Praxen - Termin2Praxis</title>
    <link rel="icon" type="image/svg+xml" href="../assets/T2P_transparent_2.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .praxis-management-card {
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            cursor: pointer;
        }
        .praxis-management-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        .praxis-img {
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        /* Neue Praxis Karte Style */
        .add-praxis-card {
            cursor: pointer;
            border: 2px dashed #dee2e6;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
        }
        .add-praxis-card:hover {
            border-color: #0d6efd;
            background: linear-gradient(135deg, #e7f1ff 0%, #cfe2ff 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.15);
        }
        .add-praxis-card:hover .add-icon-circle {
            background: #0d6efd;
            color: white;
            transform: scale(1.1);
        }
        .add-praxis-card:hover .card-title {
            color: #0d6efd;
        }
        .add-icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        /* Adress-Autovervollständigung Styling */
        #neue-address-suggestions {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 5px;
        }
        #neue-address-suggestions .list-group-item {
            cursor: pointer;
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }
        #neue-address-suggestions .list-group-item:last-child {
            border-bottom: none;
        }
        #neue-address-suggestions .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/T2P_transparent_2.svg" alt="T2P Logo" style="height: 45px; margin-right: 10px;">
                <span>Termin2Praxis - Praxisverwaltung</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link me-3" href="dashboard_arzt.php">Terminverwaltung</a>
                <span class="navbar-text me-3">
                    Willkommen, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php">Abmelden</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 mb-2">Meine Praxen</h1>
                <p class="lead text-muted">Verwalten Sie hier Ihre Arztpraxen. Klicken Sie auf eine Praxis, um die Details zu bearbeiten.</p>
            </div>
        </div>

        <?php if ($meine_praxen->num_rows === 0): ?>
            <div class="row g-4">
                <!-- Neue Praxis erstellen Karte -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-primary add-praxis-card" data-bs-toggle="modal" data-bs-target="#neuePraxisModal">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center" style="min-height: 350px;">
                            <div class="add-icon-circle mb-3">
                                <i class="bi bi-plus-lg"></i>
                            </div>
                            <h5 class="card-title">Erste Praxis erstellen</h5>
                            <p class="card-text text-muted small">Fügen Sie Ihre erste Praxis hinzu</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php while ($praxis = $meine_praxen->fetch_assoc()): 
                    // Prüfe auf leere URL oder alten Placeholder
                    $bild_url = $praxis['bild_url'] ?? '';
                    if (empty($bild_url) || strpos($bild_url, 'via.placeholder.com') !== false) {
                        $bild_url = '../assets/fotoFolgt.png';
                    } elseif (strpos($bild_url, 'uploads/') === 0) {
                        $bild_url = '../' . $bild_url;
                    }
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card praxis-management-card h-100" onclick="window.location.href='../edit_praxis.php?id=<?php echo $praxis['id']; ?>'">
                            <img src="<?php echo htmlspecialchars($bild_url); ?>" 
                                 class="card-img-top praxis-img" 
                                 alt="<?php echo htmlspecialchars($praxis['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($praxis['name']); ?></h5>
                                
                                <?php if (!empty($praxis['spezialgebiet'])): ?>
                                    <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($praxis['spezialgebiet']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($praxis['kategorie'])): ?>
                                    <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($praxis['kategorie']); ?></span>
                                <?php endif; ?>
                                
                                <p class="card-text text-muted small">
                                    <?php 
                                    $beschreibung = $praxis['beschreibung'] ?? '';
                                    echo htmlspecialchars(strlen($beschreibung) > 100 ? substr($beschreibung, 0, 100) . '...' : $beschreibung); 
                                    ?>
                                </p>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> 
                                        <?php 
                                        if (!empty($praxis['adresse'])) {
                                            echo htmlspecialchars($praxis['adresse']);
                                        } else {
                                            echo 'Keine Adresse angegeben';
                                        }
                                        ?>
                                    </small>
                                </div>
                                
                                <div class="mt-3">
                                    <a href="../edit_praxis.php?id=<?php echo $praxis['id']; ?>" class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-pencil"></i> Bearbeiten
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <!-- Neue Praxis erstellen Karte - direkt neben den anderen -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 add-praxis-card" data-bs-toggle="modal" data-bs-target="#neuePraxisModal">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                            <div class="add-icon-circle mb-3">
                                <i class="bi bi-plus-lg"></i>
                            </div>
                            <h6 class="card-title text-primary mb-1">Weitere Praxis</h6>
                            <p class="card-text text-muted small mb-0">Hinzufügen</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal für neue Praxis -->
    <div class="modal fade" id="neuePraxisModal" tabindex="-1" aria-labelledby="neuePraxisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="neuePraxisModalLabel">
                        <i class="bi bi-plus-circle"></i> Neue Praxis erstellen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <form id="neuePraxisForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Linke Spalte: Formular -->
                            <div class="col-lg-7">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Felder mit <span class="text-danger">*</span> sind Pflichtfelder.
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-building"></i> Grundinformationen</h6>
                                
                                <div class="mb-3">
                                    <label for="neue_praxis_name" class="form-label">Praxisname <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="neue_praxis_name" name="name" required 
                                           placeholder="z.B. Praxis Dr. Mustermann">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_spezialgebiet" class="form-label">Spezialgebiet <span class="text-danger">*</span></label>
                                        <select class="form-select" id="neue_praxis_spezialgebiet" name="spezialgebiet" required>
                                            <option value="">Bitte wählen...</option>
                                            <option value="Allgemeinmedizin">Allgemeinmedizin</option>
                                            <option value="Innere Medizin">Innere Medizin</option>
                                            <option value="Orthopädie">Orthopädie</option>
                                            <option value="Kardiologie">Kardiologie</option>
                                            <option value="Dermatologie">Dermatologie</option>
                                            <option value="Neurologie">Neurologie</option>
                                            <option value="Pädiatrie">Pädiatrie</option>
                                            <option value="Gynäkologie">Gynäkologie</option>
                                            <option value="Urologie">Urologie</option>
                                            <option value="HNO">HNO</option>
                                            <option value="Augenheilkunde">Augenheilkunde</option>
                                            <option value="Psychiatrie">Psychiatrie</option>
                                            <option value="Zahnmedizin">Zahnmedizin</option>
                                            <option value="Chirurgie">Chirurgie</option>
                                            <option value="Radiologie">Radiologie</option>
                                            <option value="Anästhesie">Anästhesie</option>
                                            <option value="Onkologie">Onkologie</option>
                                            <option value="Physiotherapie">Physiotherapie</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_kategorie" class="form-label">Kategorie</label>
                                        <select class="form-select" id="neue_praxis_kategorie" name="kategorie">
                                            <option value="">Bitte wählen...</option>
                                            <option value="Allgemeine & hausärztliche Versorgung">Allgemeine & hausärztliche Versorgung</option>
                                            <option value="Organe & innere Erkrankungen">Organe & innere Erkrankungen</option>
                                            <option value="Chirurgische Fächer">Chirurgische Fächer</option>
                                            <option value="Kopf, Sinne & Nerven">Kopf, Sinne & Nerven</option>
                                            <option value="Frauen, Männer & Kinder">Frauen, Männer & Kinder</option>
                                            <option value="Haut, Allergien & Immunsystem">Haut, Allergien & Immunsystem</option>
                                            <option value="Krebs & schwere Erkrankungen">Krebs & schwere Erkrankungen</option>
                                            <option value="Diagnostik & Technik">Diagnostik & Technik</option>
                                            <option value="Prävention & spezielle Versorgung">Prävention & spezielle Versorgung</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_versicherungsart" class="form-label">Akzeptierte Versicherungen</label>
                                        <select class="form-select" id="neue_praxis_versicherungsart" name="versicherungsart">
                                            <option value="Beide" selected>Gesetzlich & Privat</option>
                                            <option value="Gesetzlich">Nur Gesetzlich</option>
                                            <option value="Privat">Nur Privat</option>
                                        </select>
                                        <small class="text-muted">Welche Versicherungsarten akzeptiert Ihre Praxis?</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_opening_time" class="form-label">Öffnungszeit</label>
                                        <input type="time" class="form-control" id="neue_praxis_opening_time" name="opening_time" value="08:00">
                                        <small class="text-muted">Ab wann ist Ihre Praxis geöffnet?</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_closing_time" class="form-label">Schließzeit</label>
                                        <input type="time" class="form-control" id="neue_praxis_closing_time" name="closing_time" value="18:00">
                                        <small class="text-muted">Bis wann ist Ihre Praxis geöffnet?</small>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> <strong>Hinweis:</strong> Ihre Praxis wird nur während der Öffnungszeiten in der Praxisliste angezeigt.
                                </div>
                                
                                <h6 class="border-bottom pb-2 mb-3 mt-4"><i class="bi bi-telephone"></i> Erreichbarkeitszeiten (optional)</h6>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="neue_praxis_use_same_times" checked onchange="toggleNewPraxisReachabilityFields()">
                                    <label class="form-check-label" for="neue_praxis_use_same_times">
                                        Erreichbarkeitszeiten entsprechen den Öffnungszeiten
                                    </label>
                                </div>
                                <div id="neue_praxis_reachability_fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="neue_praxis_reachability_start" class="form-label">Erreichbar ab</label>
                                            <input type="time" class="form-control" id="neue_praxis_reachability_start" name="reachability_start" value="07:00">
                                            <small class="text-muted">Ab wann sind Sie telefonisch/per E-Mail erreichbar?</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="neue_praxis_reachability_end" class="form-label">Erreichbar bis</label>
                                            <input type="time" class="form-control" id="neue_praxis_reachability_end" name="reachability_end" value="20:00">
                                            <small class="text-muted">Bis wann sind Sie telefonisch/per E-Mail erreichbar?</small>
                                        </div>
                                    </div>
                                    <div class="alert alert-light border mb-3">
                                        <i class="bi bi-lightbulb"></i> <strong>Beispiel:</strong> Öffnungszeiten 08:00-18:00 Uhr, aber telefonisch bereits ab 07:00 Uhr und bis 20:00 Uhr erreichbar.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="neue_praxis_beschreibung" class="form-label">Beschreibung</label>
                                    <textarea class="form-control" id="neue_praxis_beschreibung" name="beschreibung" rows="2" 
                                              placeholder="Beschreiben Sie Ihre Praxis kurz..."></textarea>
                                </div>
                                
                                <!-- Praxisbild -->
                                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-image"></i> Praxisbild</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="neue_praxis_bild" class="form-label">Bild hochladen</label>
                                        <input type="file" class="form-control" id="neue_praxis_bild" name="praxis_bild" 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="text-muted">Max. 5MB (JPG, PNG, GIF, WebP)</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="neue_praxis_bild_url" class="form-label">Oder Bild-URL</label>
                                        <input type="url" class="form-control" id="neue_praxis_bild_url" name="bild_url" 
                                               placeholder="https://beispiel.de/bild.jpg">
                                    </div>
                                </div>
                                
                                <!-- Standort -->
                                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-geo-alt"></i> Standort</h6>
                                
                                <div class="mb-3 position-relative">
                                    <label for="neue_praxis_adresse" class="form-label">Straße und Hausnummer <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="neue_praxis_adresse" name="adresse" required
                                           placeholder="Musterstraße 123" autocomplete="off">
                                    <div id="neue-address-suggestions" class="list-group position-absolute w-100" style="z-index: 1000; display: none; max-height: 300px; overflow-y: auto;"></div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="neue_praxis_plz" class="form-label">PLZ <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="neue_praxis_plz" name="plz" required placeholder="12345">
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label for="neue_praxis_stadt" class="form-label">Stadt <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="neue_praxis_stadt" name="stadt" required placeholder="Berlin">
                                    </div>
                                </div>
                                
                                <!-- Kontaktdaten -->
                                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-telephone"></i> Kontaktdaten</h6>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_telefon" class="form-label">Telefon <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="neue_praxis_telefon" name="telefon" 
                                               placeholder="+49 30 12345678">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="neue_praxis_email" class="form-label">E-Mail <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="neue_praxis_email" name="email" 
                                               placeholder="info@praxis.de">
                                    </div>
                                </div>
                                <div class="alert alert-warning small py-2">
                                    <i class="bi bi-exclamation-triangle"></i> Mind. Telefon oder E-Mail muss angegeben werden.
                                </div>
                            </div>
                            
                            <!-- Rechte Spalte: Live-Vorschau -->
                            <div class="col-lg-5">
                                <div class="sticky-top" style="top: 10px;">
                                    <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-eye"></i> Live-Vorschau</h6>
                                    <p class="text-muted small">So sehen Patienten Ihre Praxis:</p>
                                    
                                    <div class="card shadow-sm">
                                        <img src="../assets/fotoFolgt.png" 
                                             class="card-img-top" 
                                             alt="Praxis Vorschau"
                                             id="vorschauBildNeu"
                                             style="height: 180px; object-fit: cover;">
                                        <div class="card-body">
                                            <h5 class="card-title" id="vorschauName">Praxisname</h5>
                                            
                                            <span class="badge bg-primary mb-2" id="vorschauSpezialgebiet" style="display: none;"></span>
                                            <span class="badge bg-secondary mb-2" id="vorschauKategorie" style="display: none;"></span>
                                            
                                            <p class="card-text text-muted small" id="vorschauBeschreibung">
                                                <em>Beschreibung wird hier angezeigt...</em>
                                            </p>
                                            
                                            <hr>
                                            
                                            <p class="mb-1 small">
                                                <i class="bi bi-geo-alt text-primary"></i> 
                                                <span id="vorschauAdresse">Adresse wird hier angezeigt</span>
                                            </p>
                                            
                                            <p class="mb-1 small" id="vorschauTelefonZeile" style="display: none;">
                                                <i class="bi bi-telephone text-primary"></i> 
                                                <span id="vorschauTelefon"></span>
                                            </p>
                                            
                                            <p class="mb-0 small" id="vorschauEmailZeile" style="display: none;">
                                                <i class="bi bi-envelope text-primary"></i> 
                                                <span id="vorschauEmail"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Abbrechen
                        </button>
                        <button type="submit" class="btn btn-success" id="btnPraxisErstellen">
                            <i class="bi bi-plus-circle"></i> Praxis erstellen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live-Vorschau aktualisieren
        function updatePreview() {
            // Name
            const name = document.getElementById('neue_praxis_name').value || 'Praxisname';
            document.getElementById('vorschauName').textContent = name;
            
            // Spezialgebiet
            const spezialgebiet = document.getElementById('neue_praxis_spezialgebiet').value;
            const spezialgebietBadge = document.getElementById('vorschauSpezialgebiet');
            if (spezialgebiet) {
                spezialgebietBadge.textContent = spezialgebiet;
                spezialgebietBadge.style.display = 'inline-block';
            } else {
                spezialgebietBadge.style.display = 'none';
            }
            
            // Kategorie
            const kategorie = document.getElementById('neue_praxis_kategorie').value;
            const kategorieBadge = document.getElementById('vorschauKategorie');
            if (kategorie) {
                kategorieBadge.textContent = kategorie;
                kategorieBadge.style.display = 'inline-block';
            } else {
                kategorieBadge.style.display = 'none';
            }
            
            // Beschreibung
            const beschreibung = document.getElementById('neue_praxis_beschreibung').value;
            const beschreibungEl = document.getElementById('vorschauBeschreibung');
            if (beschreibung) {
                beschreibungEl.innerHTML = beschreibung;
            } else {
                beschreibungEl.innerHTML = '<em>Beschreibung wird hier angezeigt...</em>';
            }
            
            // Adresse
            const adresse = document.getElementById('neue_praxis_adresse').value;
            const plz = document.getElementById('neue_praxis_plz').value;
            const stadt = document.getElementById('neue_praxis_stadt').value;
            let adresseText = 'Adresse wird hier angezeigt';
            if (adresse || plz || stadt) {
                const parts = [];
                if (adresse) parts.push(adresse);
                if (plz || stadt) parts.push([plz, stadt].filter(Boolean).join(' '));
                adresseText = parts.join(', ');
            }
            document.getElementById('vorschauAdresse').textContent = adresseText;
            
            // Telefon
            const telefon = document.getElementById('neue_praxis_telefon').value;
            const telefonZeile = document.getElementById('vorschauTelefonZeile');
            if (telefon) {
                document.getElementById('vorschauTelefon').textContent = telefon;
                telefonZeile.style.display = 'block';
            } else {
                telefonZeile.style.display = 'none';
            }
            
            // E-Mail
            const email = document.getElementById('neue_praxis_email').value;
            const emailZeile = document.getElementById('vorschauEmailZeile');
            if (email) {
                document.getElementById('vorschauEmail').textContent = email;
                emailZeile.style.display = 'block';
            } else {
                emailZeile.style.display = 'none';
            }
        }
        
        // Bild-Vorschau für Dateiupload
        document.getElementById('neue_praxis_bild').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Dateigröße prüfen (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Die Datei ist zu groß. Maximale Größe: 5MB');
                    this.value = '';
                    return;
                }
                
                // Bild-URL leeren, da Datei Priorität hat
                document.getElementById('neue_praxis_bild_url').value = '';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('vorschauBildNeu').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Bild-Vorschau für URL
        document.getElementById('neue_praxis_bild_url').addEventListener('input', function(e) {
            const url = e.target.value.trim();
            if (url) {
                // Datei-Input leeren, da URL verwendet wird
                document.getElementById('neue_praxis_bild').value = '';
                document.getElementById('vorschauBildNeu').src = url;
            } else {
                document.getElementById('vorschauBildNeu').src = '../assets/fotoFolgt.png';
            }
        });
        
        // Fehlerbehandlung für Bild
        document.getElementById('vorschauBildNeu').addEventListener('error', function() {
            this.src = '../assets/fotoFolgt.png';
        });
        
        // Event-Listener für alle Eingabefelder
        const formFields = ['neue_praxis_name', 'neue_praxis_spezialgebiet', 'neue_praxis_kategorie', 
                          'neue_praxis_beschreibung', 'neue_praxis_adresse', 'neue_praxis_plz', 
                          'neue_praxis_stadt', 'neue_praxis_telefon', 'neue_praxis_email'];
        
        formFields.forEach(function(fieldId) {
            const element = document.getElementById(fieldId);
            if (element) {
                element.addEventListener('input', updatePreview);
                element.addEventListener('change', updatePreview);
            }
        });
        
        // Modal Reset beim Schließen
        document.getElementById('neuePraxisModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('neuePraxisForm').reset();
            document.getElementById('vorschauBildNeu').src = '../assets/fotoFolgt.png';
            updatePreview();
        });
        
        // Formular absenden
        document.getElementById('neuePraxisForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validierung: Mindestens Telefon oder E-Mail
            const telefon = document.getElementById('neue_praxis_telefon').value.trim();
            const email = document.getElementById('neue_praxis_email').value.trim();
            
            if (!telefon && !email) {
                alert('Bitte geben Sie mindestens eine Telefonnummer oder E-Mail-Adresse an, damit Patienten Sie erreichen können.');
                return;
            }
            
            const btn = document.getElementById('btnPraxisErstellen');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Wird erstellt...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('../api/create_praxis.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Erfolgreich erstellt - zurück zur Übersicht
                    window.location.href = 'dashboard_praxisbesitzer.php';
                } else {
                    alert('Fehler: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    </script>
    
    <script>
        // Erreichbarkeitszeiten optional für neue Praxis
        function toggleNewPraxisReachabilityFields() {
            const checkbox = document.getElementById('neue_praxis_use_same_times');
            const fieldsDiv = document.getElementById('neue_praxis_reachability_fields');
            const reachabilityStart = document.getElementById('neue_praxis_reachability_start');
            const reachabilityEnd = document.getElementById('neue_praxis_reachability_end');
            const openingTime = document.getElementById('neue_praxis_opening_time');
            const closingTime = document.getElementById('neue_praxis_closing_time');
            
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
        
        // Live-Synchronisierung bei Änderung der Öffnungszeiten
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('neue_praxis_opening_time').addEventListener('change', function() {
                if (document.getElementById('neue_praxis_use_same_times').checked) {
                    document.getElementById('neue_praxis_reachability_start').value = this.value;
                }
            });
            
            document.getElementById('neue_praxis_closing_time').addEventListener('change', function() {
                if (document.getElementById('neue_praxis_use_same_times').checked) {
                    document.getElementById('neue_praxis_reachability_end').value = this.value;
                }
            });
            
            // Initial synchronisieren
            toggleNewPraxisReachabilityFields();
            
            // Adress-Autovervollständigung für neue Praxis
            setupAddressAutocomplete();
        });
        
        function setupAddressAutocomplete() {
            let neueAddressTimeout = null;
            const neueAdresseInput = document.getElementById('neue_praxis_adresse');
            const neuePlzInput = document.getElementById('neue_praxis_plz');
            const neueStadtInput = document.getElementById('neue_praxis_stadt');
            const neueSuggestionsDiv = document.getElementById('neue-address-suggestions');
            
            if (!neueAdresseInput || !neueSuggestionsDiv) {
                console.error('Adressfelder nicht gefunden!');
                return;
            }
            
            console.log('Adress-Autovervollständigung für neue Praxis initialisiert');
            
            neueAdresseInput.addEventListener('input', function() {
                const query = this.value.trim();
                console.log('Neue Praxis - Input erkannt:', query);
                
                if (query.length < 3) {
                    neueSuggestionsDiv.style.display = 'none';
                    return;
                }
                
                clearTimeout(neueAddressTimeout);
                neueAddressTimeout = setTimeout(() => {
                    console.log('Neue Praxis - Suche Adresse:', query);
                    searchNeueAddress(query);
                }, 500);
            });
            
            function searchNeueAddress(query) {
                // Verwende PHP-Proxy statt direkter API-Anfrage (vermeidet CORS-Probleme)
                const url = `../api/geocode_search.php?q=${encodeURIComponent(query)}`;
                
                console.log('Neue Praxis - API-Anfrage über Proxy:', url);
                
                fetch(url)
                .then(response => {
                    console.log('Neue Praxis - API-Response Status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Neue Praxis - API-Daten:', data);
                    if (data && data.length > 0) {
                        showNeueSuggestions(data);
                    } else {
                        console.log('Neue Praxis - Keine Ergebnisse');
                        neueSuggestionsDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Neue Praxis - Adresssuche fehlgeschlagen:', error);
                    neueSuggestionsDiv.style.display = 'none';
                });
            }
            
            function showNeueSuggestions(results) {
                neueSuggestionsDiv.innerHTML = '';
                let validResults = 0;
                
                results.forEach(result => {
                    const address = result.address;
                    if (!address) return;
                    
                    const street = address.road || address.pedestrian || address.street || '';
                    const houseNumber = address.house_number || '';
                    const postcode = address.postcode || '';
                    const city = address.city || address.town || address.village || address.municipality || '';
                    const country = address.country || '';
                    
                    if (!street && !city) return;
                    
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    
                    let displayStreet = street;
                    if (houseNumber) {
                        displayStreet = `${street} ${houseNumber}`;
                    }
                    
                    // Länderflagge bestimmen
                    let countryFlag = '🇩🇪';
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
                        console.log('Neue Praxis - Adresse ausgewählt:', displayStreet, postcode, city, country);
                        
                        if (displayStreet) {
                            neueAdresseInput.value = displayStreet.trim();
                        }
                        if (postcode) {
                            neuePlzInput.value = postcode;
                        }
                        if (city) {
                            neueStadtInput.value = city;
                        }
                        
                        neueSuggestionsDiv.style.display = 'none';
                        
                        [neueAdresseInput, neuePlzInput, neueStadtInput].forEach(input => {
                            if (input.value) {
                                input.classList.add('border-success');
                                input.classList.add('border-3');
                                setTimeout(() => {
                                    input.classList.remove('border-success');
                                    input.classList.remove('border-3');
                                }, 2500);
                            }
                        });
                        
                        updatePreview();
                    });
                    
                    neueSuggestionsDiv.appendChild(item);
                    validResults++;
                });
                
                console.log('Neue Praxis - Zeige', validResults, 'Vorschläge');
                
                if (validResults > 0) {
                    neueSuggestionsDiv.style.display = 'block';
                } else {
                    neueSuggestionsDiv.style.display = 'none';
                }
            }
            
            document.addEventListener('click', function(e) {
                if (!neueAdresseInput.contains(e.target) && !neueSuggestionsDiv.contains(e.target)) {
                    neueSuggestionsDiv.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
