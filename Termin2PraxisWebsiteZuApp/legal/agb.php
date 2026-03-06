<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGB - Termin2Praxis</title>
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
                <h1 class="display-4 mb-4">Allgemeine Geschäftsbedingungen (AGB)</h1>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-5">
                        <h2 class="h4 mb-4">1. Geltungsbereich</h2>
                        <p>
                            Diese Allgemeinen Geschäftsbedingungen (nachfolgend "AGB") gelten für alle Verträge, die zwischen dem 
                            Betreiber der Plattform Termin2Praxis (nachfolgend "Anbieter") und den Nutzern der Plattform 
                            (nachfolgend "Nutzer") über die Nutzung der Online-Terminbuchungsplattform geschlossen werden.
                        </p>
                        <p>
                            Abweichende, entgegenstehende oder ergänzende AGB des Nutzers werden nicht Vertragsbestandteil, es sei 
                            denn, ihrer Geltung wird ausdrücklich zugestimmt.
                        </p>

                        <h2 class="h4 mt-5 mb-4">2. Vertragsgegenstand</h2>
                        <p>
                            Der Anbieter stellt eine Online-Plattform zur Verfügung, über die Nutzer (Patienten) Termine bei 
                            teilnehmenden Arztpraxen buchen können. Die Plattform dient lediglich der Vermittlung von Terminen. 
                            Der eigentliche Behandlungsvertrag kommt ausschließlich zwischen dem Patienten und der Arztpraxis zustande.
                        </p>
                        <p>
                            Der Anbieter ist nicht Vertragspartner des Behandlungsvertrages und übernimmt keine Haftung für die 
                            medizinische Behandlung oder die Qualität der ärztlichen Leistungen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">3. Registrierung und Nutzerkonto</h2>
                        
                        <h3 class="h5 mb-3">3.1 Registrierung</h3>
                        <p>
                            Zur Nutzung der Terminbuchungsfunktion ist eine Registrierung erforderlich. Bei der Registrierung sind 
                            wahrheitsgemäße und vollständige Angaben zu machen. Der Nutzer verpflichtet sich, seine Daten aktuell 
                            zu halten und Änderungen unverzüglich mitzuteilen.
                        </p>

                        <h3 class="h5 mt-4 mb-3">3.2 Nutzerkonto</h3>
                        <p>
                            Nach erfolgreicher Registrierung erhält der Nutzer Zugang zu einem passwortgeschützten Nutzerkonto. 
                            Der Nutzer ist verpflichtet, seine Zugangsdaten vertraulich zu behandeln und vor dem Zugriff Dritter 
                            zu schützen. Bei Verlust oder unbefugter Nutzung der Zugangsdaten ist der Anbieter unverzüglich zu informieren.
                        </p>

                        <h3 class="h5 mt-4 mb-3">3.3 Mehrfachregistrierungen</h3>
                        <p>
                            Pro Person ist nur eine Registrierung zulässig. Mehrfachregistrierungen sind nicht gestattet und können 
                            zur Sperrung des Nutzerkontos führen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">4. Terminbuchung</h2>
                        
                        <h3 class="h5 mb-3">4.1 Buchungsvorgang</h3>
                        <p>
                            Der Nutzer kann über die Plattform verfügbare Termine bei teilnehmenden Arztpraxen anfragen. Die Terminanfrage 
                            stellt noch keine verbindliche Buchung dar. Der Termin wird erst nach Bestätigung durch die Arztpraxis verbindlich.
                        </p>

                        <h3 class="h5 mt-4 mb-3">4.2 Terminbestätigung</h3>
                        <p>
                            Die Arztpraxis entscheidet über die Annahme oder Ablehnung der Terminanfrage. Der Nutzer wird per E-Mail 
                            über die Entscheidung der Praxis informiert. Bei Terminbestätigung kommt ein Behandlungsvertrag direkt 
                            zwischen Nutzer und Arztpraxis zustande.
                        </p>

                        <h3 class="h5 mt-4 mb-3">4.3 Terminabsage</h3>
                        <p>
                            Kann der Nutzer einen bestätigten Termin nicht wahrnehmen, ist er verpflichtet, diesen rechtzeitig 
                            (mindestens 24 Stunden vorher) über die Plattform oder direkt bei der Arztpraxis abzusagen. Bei 
                            wiederholtem unentschuldigtem Nichterscheinen (No-Show) behält sich der Anbieter vor, das Nutzerkonto 
                            zu sperren.
                        </p>

                        <h3 class="h5 mt-4 mb-3">4.4 Terminänderungen</h3>
                        <p>
                            Die Arztpraxis behält sich vor, bestätigte Termine aus wichtigem Grund zu verschieben oder abzusagen. 
                            Der Nutzer wird in diesem Fall unverzüglich informiert und erhält die Möglichkeit, einen Ersatztermin zu buchen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">5. Pflichten des Nutzers</h2>
                        
                        <h3 class="h5 mb-3">5.1 Zulässige Nutzung</h3>
                        <p>
                            Der Nutzer verpflichtet sich, die Plattform nur für ihren bestimmungsgemäßen Zweck (Terminbuchung bei 
                            Arztpraxen) zu nutzen. Missbräuchliche Nutzung, insbesondere:
                        </p>
                        <ul>
                            <li>Buchung von Terminen ohne Wahrnehmungsabsicht</li>
                            <li>Mehrfachbuchungen für denselben Zeitraum</li>
                            <li>Verwendung falscher Angaben</li>
                            <li>Versuch, die Plattform zu manipulieren oder zu beschädigen</li>
                        </ul>
                        <p>ist untersagt und kann zur sofortigen Sperrung des Nutzerkontos führen.</p>

                        <h3 class="h5 mt-4 mb-3">5.2 Pünktlichkeit</h3>
                        <p>
                            Der Nutzer verpflichtet sich, gebuchte Termine pünktlich wahrzunehmen. Bei Verspätungen von mehr als 
                            15 Minuten kann die Arztpraxis den Termin absagen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">6. Vergütung und Zahlungsbedingungen</h2>
                        <p>
                            Die Nutzung der Plattform ist für Patienten kostenlos. Die Abrechnung ärztlicher Leistungen erfolgt 
                            direkt zwischen Patient und Arztpraxis nach den geltenden Gebührenordnungen (GOÄ/EBM) bzw. 
                            Versicherungsbedingungen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">7. Haftung</h2>
                        
                        <h3 class="h5 mb-3">7.1 Haftungsbeschränkung</h3>
                        <p>
                            Der Anbieter haftet unbeschränkt für Vorsatz und grobe Fahrlässigkeit sowie für die Verletzung von Leben, 
                            Körper oder Gesundheit. Für leichte Fahrlässigkeit haftet der Anbieter nur bei Verletzung wesentlicher 
                            Vertragspflichten (Kardinalspflichten). In diesem Fall ist die Haftung auf den vorhersehbaren, 
                            vertragstypischen Schaden begrenzt.
                        </p>

                        <h3 class="h5 mt-4 mb-3">7.2 Keine Haftung für ärztliche Leistungen</h3>
                        <p>
                            Der Anbieter übernimmt keine Haftung für die Qualität, Verfügbarkeit oder Ergebnisse der medizinischen 
                            Behandlung durch die Arztpraxen. Für ärztliche Behandlungsfehler haftet ausschließlich die behandelnde Arztpraxis.
                        </p>

                        <h3 class="h5 mt-4 mb-3">7.3 Technische Verfügbarkeit</h3>
                        <p>
                            Der Anbieter bemüht sich um eine hohe Verfügbarkeit der Plattform, kann diese jedoch nicht garantieren. 
                            Insbesondere bei Wartungsarbeiten, technischen Störungen oder höherer Gewalt kann es zu temporären 
                            Ausfällen kommen. Der Anbieter haftet nicht für Schäden, die durch solche Ausfälle entstehen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">8. Datenschutz</h2>
                        <p>
                            Der Anbieter verpflichtet sich, alle personenbezogenen Daten gemäß den geltenden Datenschutzbestimmungen, 
                            insbesondere der Datenschutz-Grundverordnung (DSGVO), zu verarbeiten. Nähere Informationen finden Sie in 
                            unserer <a href="datenschutz.php">Datenschutzerklärung</a>.
                        </p>

                        <h2 class="h4 mt-5 mb-4">9. Vertragslaufzeit und Kündigung</h2>
                        
                        <h3 class="h5 mb-3">9.1 Laufzeit</h3>
                        <p>
                            Der Nutzungsvertrag wird auf unbestimmte Zeit geschlossen und kann von beiden Seiten jederzeit ohne 
                            Einhaltung einer Frist gekündigt werden.
                        </p>

                        <h3 class="h5 mt-4 mb-3">9.2 Kündigung durch den Nutzer</h3>
                        <p>
                            Der Nutzer kann sein Konto jederzeit über die Kontoeinstellungen löschen oder per E-Mail an den Anbieter kündigen.
                        </p>

                        <h3 class="h5 mt-4 mb-3">9.3 Kündigung durch den Anbieter</h3>
                        <p>
                            Der Anbieter kann das Nutzerkonto bei Verstoß gegen diese AGB, insbesondere bei missbräuchlicher Nutzung, 
                            mit sofortiger Wirkung sperren oder löschen. Der Nutzer wird hierüber per E-Mail informiert.
                        </p>

                        <h2 class="h4 mt-5 mb-4">10. Änderungen der AGB</h2>
                        <p>
                            Der Anbieter behält sich vor, diese AGB jederzeit zu ändern. Nutzer werden über Änderungen mindestens 
                            4 Wochen vor deren Inkrafttreten per E-Mail informiert. Widerspricht der Nutzer den Änderungen nicht 
                            innerhalb von 4 Wochen nach Zugang der Änderungsmitteilung, gelten die geänderten AGB als akzeptiert. 
                            Im Falle des Widerspruchs kann der Anbieter das Nutzungsverhältnis ordentlich kündigen.
                        </p>

                        <h2 class="h4 mt-5 mb-4">11. Geistige Eigentumsrechte</h2>
                        <p>
                            Alle Inhalte der Plattform (Texte, Bilder, Grafiken, Design, Software) sind urheberrechtlich geschützt. 
                            Die Nutzung, Vervielfältigung oder Verbreitung ohne ausdrückliche Zustimmung des Anbieters ist untersagt.
                        </p>

                        <h2 class="h4 mt-5 mb-4">12. Schlussbestimmungen</h2>
                        
                        <h3 class="h5 mb-3">12.1 Anwendbares Recht</h3>
                        <p>
                            Es gilt das Recht der Bundesrepublik Deutschland unter Ausschluss des UN-Kaufrechts.
                        </p>

                        <h3 class="h5 mt-4 mb-3">12.2 Gerichtsstand</h3>
                        <p>
                            Gerichtsstand für alle Streitigkeiten aus diesem Vertragsverhältnis ist der Sitz des Anbieters, 
                            sofern der Nutzer Kaufmann, juristische Person des öffentlichen Rechts oder öffentlich-rechtliches 
                            Sondervermögen ist.
                        </p>

                        <h3 class="h5 mt-4 mb-3">12.3 Salvatorische Klausel</h3>
                        <p>
                            Sollten einzelne Bestimmungen dieser AGB unwirksam sein oder werden, bleibt die Wirksamkeit der übrigen 
                            Bestimmungen hiervon unberührt. An die Stelle der unwirksamen Bestimmung tritt eine Regelung, die dem 
                            wirtschaftlichen Zweck der unwirksamen Bestimmung am nächsten kommt.
                        </p>

                        <h2 class="h4 mt-5 mb-4">13. Streitbeilegung</h2>
                        <p>
                            Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit, die Sie unter 
                            <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr</a> 
                            finden. Wir sind nicht bereit und nicht verpflichtet, an einem Streitbeilegungsverfahren vor einer 
                            Verbraucherschlichtungsstelle teilzunehmen.
                        </p>

                        <p class="mt-5"><small class="text-muted">Stand: Februar 2026</small></p>
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
