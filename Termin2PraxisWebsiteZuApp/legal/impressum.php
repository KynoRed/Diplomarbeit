<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum - Termin2Praxis</title>
    <link rel="icon" type="image/svg+xml" href="../assets/T2P_transparent_2.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/T2P_transparent_2.svg" alt="T2P Logo" style="height: 45px; margin-right: 10px;">
                <span>Termin2Praxis</span>
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <span class="navbar-text me-3">
                        Willkommen, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="../logout.php">Abmelden</a>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-sm" href="../login.php">Anmelden</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="display-4 mb-4">Impressum</h1>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-5">
                        <h2 class="h4 mb-4">Angaben gemäß § 5 TMG</h2>
                        
                        <p><strong>Betreiber der Website:</strong></p>
                        <p>
                            [Firmenname / Name des Betreibers]<br>
                            [Straße und Hausnummer]<br>
                            [PLZ und Ort]<br>
                            Deutschland
                        </p>

                        <h3 class="h5 mt-4 mb-3">Kontakt</h3>
                        <p>
                            Telefon: [Telefonnummer]<br>
                            E-Mail: [E-Mail-Adresse]<br>
                            Website: www.termin2praxis.de
                        </p>

                        <h3 class="h5 mt-4 mb-3">Vertretungsberechtigter</h3>
                        <p>
                            [Name des Geschäftsführers / Inhabers]
                        </p>

                        <h3 class="h5 mt-4 mb-3">Registereintrag</h3>
                        <p>
                            Eintragung im Handelsregister<br>
                            Registergericht: [z.B. Amtsgericht München]<br>
                            Registernummer: [z.B. HRB 123456]
                        </p>

                        <h3 class="h5 mt-4 mb-3">Umsatzsteuer-ID</h3>
                        <p>
                            Umsatzsteuer-Identifikationsnummer gemäß §27a Umsatzsteuergesetz:<br>
                            [USt-IdNr.]
                        </p>

                        <h3 class="h5 mt-4 mb-3">Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h3>
                        <p>
                            [Name]<br>
                            [Adresse]
                        </p>

                        <h3 class="h5 mt-4 mb-3">Streitschlichtung</h3>
                        <p>
                            Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: 
                            <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr</a>.<br>
                            Unsere E-Mail-Adresse finden Sie oben im Impressum.
                        </p>
                        <p>
                            Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer 
                            Verbraucherschlichtungsstelle teilzunehmen.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Haftung für Inhalte</h3>
                        <p>
                            Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den 
                            allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht 
                            verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen 
                            zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.
                        </p>
                        <p>
                            Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen 
                            Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt 
                            der Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden von entsprechenden 
                            Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Haftung für Links</h3>
                        <p>
                            Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. 
                            Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten 
                            Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich. Die verlinkten Seiten 
                            wurden zum Zeitpunkt der Verlinkung auf mögliche Rechtsverstöße überprüft. Rechtswidrige Inhalte waren 
                            zum Zeitpunkt der Verlinkung nicht erkennbar.
                        </p>
                        <p>
                            Eine permanente inhaltliche Kontrolle der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer 
                            Rechtsverletzung nicht zumutbar. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links 
                            umgehend entfernen.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Urheberrecht</h3>
                        <p>
                            Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen 
                            Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der 
                            Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers. 
                            Downloads und Kopien dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet.
                        </p>
                        <p>
                            Soweit die Inhalte auf dieser Seite nicht vom Betreiber erstellt wurden, werden die Urheberrechte Dritter 
                            beachtet. Insbesondere werden Inhalte Dritter als solche gekennzeichnet. Sollten Sie trotzdem auf eine 
                            Urheberrechtsverletzung aufmerksam werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden 
                            von Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.
                        </p>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="../index.php" class="btn btn-primary">Zurück zur Startseite</a>
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
                    <a href="impressum.php" class="text-white text-decoration-none me-3">Impressum</a>
                    <a href="datenschutz.php" class="text-white text-decoration-none me-3">Datenschutz</a>
                    <a href="agb.php" class="text-white text-decoration-none">AGB</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
