<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenschutzerklärung - Termin2Praxis</title>
    <link rel="icon" type="image/svg+xml" href="assets/T2P_transparent_2.svg">
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
                <h1 class="display-4 mb-4">Datenschutzerklärung</h1>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-5">
                        <h2 class="h4 mb-4">1. Datenschutz auf einen Blick</h2>
                        
                        <h3 class="h5 mb-3">Allgemeine Hinweise</h3>
                        <p>
                            Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten 
                            passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie 
                            persönlich identifiziert werden können. Ausführliche Informationen zum Thema Datenschutz entnehmen 
                            Sie unserer unter diesem Text aufgeführten Datenschutzerklärung.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Datenerfassung auf dieser Website</h3>
                        <p><strong>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</strong></p>
                        <p>
                            Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten 
                            können Sie dem Impressum dieser Website entnehmen.
                        </p>

                        <p><strong>Wie erfassen wir Ihre Daten?</strong></p>
                        <p>
                            Ihre Daten werden zum einen dadurch erhoben, dass Sie uns diese mitteilen. Hierbei kann es sich z.B. um 
                            Daten handeln, die Sie in ein Kontaktformular eingeben oder bei der Registrierung angeben.
                        </p>
                        <p>
                            Andere Daten werden automatisch oder nach Ihrer Einwilligung beim Besuch der Website durch unsere 
                            IT-Systeme erfasst. Das sind vor allem technische Daten (z.B. Internetbrowser, Betriebssystem oder 
                            Uhrzeit des Seitenaufrufs). Die Erfassung dieser Daten erfolgt automatisch, sobald Sie diese Website betreten.
                        </p>

                        <p><strong>Wofür nutzen wir Ihre Daten?</strong></p>
                        <p>
                            Ein Teil der Daten wird erhoben, um eine fehlerfreie Bereitstellung der Website zu gewährleisten. 
                            Andere Daten können zur Analyse Ihres Nutzerverhaltens verwendet werden. Im Falle der Terminbuchung 
                            werden Ihre Daten verwendet, um Arzttermine zu vermitteln und zu verwalten.
                        </p>

                        <p><strong>Welche Rechte haben Sie bezüglich Ihrer Daten?</strong></p>
                        <p>
                            Sie haben jederzeit das Recht, unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer 
                            gespeicherten personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht, die Berichtigung 
                            oder Löschung dieser Daten zu verlangen. Wenn Sie eine Einwilligung zur Datenverarbeitung erteilt 
                            haben, können Sie diese Einwilligung jederzeit für die Zukunft widerrufen. Außerdem haben Sie das 
                            Recht, unter bestimmten Umständen die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten 
                            zu verlangen. Des Weiteren steht Ihnen ein Beschwerderecht bei der zuständigen Aufsichtsbehörde zu.
                        </p>

                        <h2 class="h4 mt-5 mb-4">2. Hosting</h2>
                        <p>
                            Wir hosten die Inhalte unserer Website bei folgendem Anbieter:
                        </p>
                        <p>
                            <strong>[Hosting-Anbieter Name]</strong><br>
                            [Adresse des Hosting-Anbieters]
                        </p>
                        <p>
                            Wenn Sie unsere Website besuchen, werden Ihre Daten auf den Servern des Hosting-Anbieters verarbeitet. 
                            Hierbei können u.a. IP-Adressen und Browserinformationen erfasst werden.
                        </p>

                        <h2 class="h4 mt-5 mb-4">3. Allgemeine Hinweise und Pflichtinformationen</h2>
                        
                        <h3 class="h5 mb-3">Datenschutz</h3>
                        <p>
                            Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre 
                            personenbezogenen Daten vertraulich und entsprechend der gesetzlichen Datenschutzvorschriften sowie 
                            dieser Datenschutzerklärung.
                        </p>
                        <p>
                            Wenn Sie diese Website benutzen, werden verschiedene personenbezogene Daten erhoben. Personenbezogene 
                            Daten sind Daten, mit denen Sie persönlich identifiziert werden können. Die vorliegende Datenschutzerklärung 
                            erläutert, welche Daten wir erheben und wofür wir sie nutzen. Sie erläutert auch, wie und zu welchem Zweck das geschieht.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Hinweis zur verantwortlichen Stelle</h3>
                        <p>
                            Die verantwortliche Stelle für die Datenverarbeitung auf dieser Website ist:
                        </p>
                        <p>
                            [Firmenname / Name]<br>
                            [Straße und Hausnummer]<br>
                            [PLZ und Ort]<br>
                            Telefon: [Telefonnummer]<br>
                            E-Mail: [E-Mail-Adresse]
                        </p>
                        <p>
                            Verantwortliche Stelle ist die natürliche oder juristische Person, die allein oder gemeinsam mit anderen 
                            über die Zwecke und Mittel der Verarbeitung von personenbezogenen Daten (z.B. Namen, E-Mail-Adressen o. Ä.) entscheidet.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Speicherdauer</h3>
                        <p>
                            Soweit innerhalb dieser Datenschutzerklärung keine speziellere Speicherdauer genannt wurde, verbleiben 
                            Ihre personenbezogenen Daten bei uns, bis der Zweck für die Datenverarbeitung entfällt. Wenn Sie ein 
                            berechtigtes Löschersuchen geltend machen oder eine Einwilligung zur Datenverarbeitung widerrufen, 
                            werden Ihre Daten gelöscht, sofern wir keine anderen rechtlich zulässigen Gründe für die Speicherung 
                            Ihrer personenbezogenen Daten haben (z.B. steuer- oder handelsrechtliche Aufbewahrungsfristen); im 
                            letztgenannten Fall erfolgt die Löschung nach Fortfall dieser Gründe.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Widerruf Ihrer Einwilligung zur Datenverarbeitung</h3>
                        <p>
                            Viele Datenverarbeitungsvorgänge sind nur mit Ihrer ausdrücklichen Einwilligung möglich. Sie können eine 
                            bereits erteilte Einwilligung jederzeit widerrufen. Die Rechtmäßigkeit der bis zum Widerruf erfolgten 
                            Datenverarbeitung bleibt vom Widerruf unberührt.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Recht auf Datenübertragbarkeit</h3>
                        <p>
                            Sie haben das Recht, Daten, die wir auf Grundlage Ihrer Einwilligung oder in Erfüllung eines Vertrags 
                            automatisiert verarbeiten, an sich oder an einen Dritten in einem gängigen, maschinenlesbaren Format 
                            aushändigen zu lassen. Sofern Sie die direkte Übertragung der Daten an einen anderen Verantwortlichen 
                            verlangen, erfolgt dies nur, soweit es technisch machbar ist.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Auskunft, Löschung und Berichtigung</h3>
                        <p>
                            Sie haben im Rahmen der geltenden gesetzlichen Bestimmungen jederzeit das Recht auf unentgeltliche 
                            Auskunft über Ihre gespeicherten personenbezogenen Daten, deren Herkunft und Empfänger und den Zweck 
                            der Datenverarbeitung und ggf. ein Recht auf Berichtigung oder Löschung dieser Daten. Hierzu sowie zu 
                            weiteren Fragen zum Thema personenbezogene Daten können Sie sich jederzeit an uns wenden.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Recht auf Einschränkung der Verarbeitung</h3>
                        <p>
                            Sie haben das Recht, die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen. 
                            Hierzu können Sie sich jederzeit an uns wenden. Das Recht auf Einschränkung der Verarbeitung besteht 
                            in folgenden Fällen:
                        </p>
                        <ul>
                            <li>Wenn Sie die Richtigkeit Ihrer bei uns gespeicherten personenbezogenen Daten bestreiten, benötigen wir 
                                in der Regel Zeit, um dies zu überprüfen. Für die Dauer der Prüfung haben Sie das Recht, die 
                                Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen.</li>
                            <li>Wenn die Verarbeitung Ihrer personenbezogenen Daten unrechtmäßig geschah/geschieht, können Sie statt 
                                der Löschung die Einschränkung der Datenverarbeitung verlangen.</li>
                            <li>Wenn wir Ihre personenbezogenen Daten nicht mehr benötigen, Sie sie jedoch zur Ausübung, Verteidigung 
                                oder Geltendmachung von Rechtsansprüchen benötigen, haben Sie das Recht, statt der Löschung die 
                                Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen.</li>
                        </ul>

                        <h2 class="h4 mt-5 mb-4">4. Datenerfassung auf dieser Website</h2>
                        
                        <h3 class="h5 mb-3">Server-Log-Dateien</h3>
                        <p>
                            Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, 
                            die Ihr Browser automatisch an uns übermittelt. Dies sind:
                        </p>
                        <ul>
                            <li>Browsertyp und Browserversion</li>
                            <li>verwendetes Betriebssystem</li>
                            <li>Referrer URL</li>
                            <li>Hostname des zugreifenden Rechners</li>
                            <li>Uhrzeit der Serveranfrage</li>
                            <li>IP-Adresse</li>
                        </ul>
                        <p>
                            Eine Zusammenführung dieser Daten mit anderen Datenquellen wird nicht vorgenommen. Die Erfassung dieser 
                            Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Der Websitebetreiber hat ein berechtigtes 
                            Interesse an der technisch fehlerfreien Darstellung und der Optimierung seiner Website – hierzu müssen 
                            die Server-Log-Files erfasst werden.
                        </p>

                        <h3 class="h5 mt-4 mb-3">Registrierung auf dieser Website</h3>
                        <p>
                            Sie können sich auf dieser Website registrieren, um zusätzliche Funktionen auf der Seite zu nutzen, 
                            insbesondere um Arzttermine zu buchen. Die dazu eingegebenen Daten verwenden wir nur zum Zwecke der 
                            Nutzung des jeweiligen Angebotes oder Dienstes, für den Sie sich registriert haben. Die bei der 
                            Registrierung abgefragten Pflichtangaben müssen vollständig angegeben werden. Anderenfalls werden wir 
                            die Registrierung ablehnen.
                        </p>
                        <p>
                            Für wichtige Änderungen etwa beim Angebotsumfang oder bei technisch notwendigen Änderungen nutzen wir 
                            die bei der Registrierung angegebene E-Mail-Adresse, um Sie auf diesem Wege zu informieren.
                        </p>
                        <p>
                            Die Verarbeitung der bei der Registrierung eingegebenen Daten erfolgt zum Zwecke der Durchführung des 
                            durch die Registrierung begründeten Nutzungsverhältnisses und ggf. zur Anbahnung weiterer Verträge 
                            (Art. 6 Abs. 1 lit. b DSGVO).
                        </p>

                        <h3 class="h5 mt-4 mb-3">Terminbuchungen</h3>
                        <p>
                            Bei der Buchung von Arztterminen über unsere Plattform werden folgende Daten erhoben und verarbeitet:
                        </p>
                        <ul>
                            <li>Name und Kontaktdaten (E-Mail, ggf. Telefonnummer)</li>
                            <li>Gewünschter Terminzeitpunkt</li>
                            <li>Gewählte Arztpraxis</li>
                            <li>Versicherungsstatus (gesetzlich/privat)</li>
                        </ul>
                        <p>
                            Diese Daten werden ausschließlich zur Vermittlung und Verwaltung von Arztterminen verwendet und an die 
                            entsprechende Arztpraxis weitergegeben. Die Rechtsgrundlage für die Verarbeitung ist Art. 6 Abs. 1 lit. b DSGVO 
                            (Vertragserfüllung).
                        </p>

                        <h3 class="h5 mt-4 mb-3">Kontaktformular</h3>
                        <p>
                            Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular 
                            inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall 
                            von Anschlussfragen bei uns gespeichert. Diese Daten geben wir nicht ohne Ihre Einwilligung weiter.
                        </p>

                        <h2 class="h4 mt-5 mb-4">5. Weitergabe von Daten an Dritte</h2>
                        <p>
                            Eine Übermittlung Ihrer persönlichen Daten an Dritte zu anderen als den im Folgenden aufgeführten Zwecken 
                            findet nicht statt. Wir geben Ihre persönlichen Daten nur an Dritte weiter, wenn:
                        </p>
                        <ul>
                            <li>Sie Ihre ausdrückliche Einwilligung dazu erteilt haben (Art. 6 Abs. 1 lit. a DSGVO),</li>
                            <li>die Weitergabe zur Erfüllung eines Vertrages erforderlich ist (z.B. Weitergabe an die Arztpraxis bei Terminbuchung) 
                                (Art. 6 Abs. 1 lit. b DSGVO),</li>
                            <li>für die Weitergabe eine gesetzliche Verpflichtung besteht (Art. 6 Abs. 1 lit. c DSGVO), oder</li>
                            <li>die Weitergabe zur Geltendmachung, Ausübung oder Verteidigung von Rechtsansprüchen erforderlich ist und 
                                kein Grund zur Annahme besteht, dass Sie ein überwiegendes schutzwürdiges Interesse an der Nichtweitergabe 
                                Ihrer Daten haben (Art. 6 Abs. 1 lit. f DSGVO).</li>
                        </ul>

                        <h2 class="h4 mt-5 mb-4">6. Ihre Rechte als Betroffener</h2>
                        <p>
                            Sie haben folgende Rechte bezüglich Ihrer bei uns gespeicherten personenbezogenen Daten:
                        </p>
                        <ul>
                            <li><strong>Recht auf Auskunft:</strong> Sie können Auskunft über die von uns verarbeiteten personenbezogenen Daten verlangen.</li>
                            <li><strong>Recht auf Berichtigung:</strong> Sie können die Berichtigung unrichtiger Daten verlangen.</li>
                            <li><strong>Recht auf Löschung:</strong> Sie können die Löschung Ihrer Daten verlangen.</li>
                            <li><strong>Recht auf Einschränkung:</strong> Sie können die Einschränkung der Verarbeitung verlangen.</li>
                            <li><strong>Recht auf Widerspruch:</strong> Sie können der Verarbeitung Ihrer Daten widersprechen.</li>
                            <li><strong>Recht auf Datenübertragbarkeit:</strong> Sie können die Übertragung Ihrer Daten verlangen.</li>
                            <li><strong>Beschwerderecht:</strong> Sie können sich bei einer Aufsichtsbehörde beschweren.</li>
                        </ul>

                        <h2 class="h4 mt-5 mb-4">7. SSL- bzw. TLS-Verschlüsselung</h2>
                        <p>
                            Diese Seite nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher Inhalte, wie zum 
                            Beispiel Bestellungen oder Anfragen, die Sie an uns als Seitenbetreiber senden, eine SSL- bzw. 
                            TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie daran, dass die Adresszeile des Browsers 
                            von „http://" auf „https://" wechselt und an dem Schloss-Symbol in Ihrer Browserzeile.
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
