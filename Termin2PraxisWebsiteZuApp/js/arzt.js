// JavaScript für Arzt Dashboard

// Toggle für Mehrfach-Termine
document.getElementById('multipleSlots').addEventListener('change', function() {
    const multipleOptions = document.getElementById('multipleOptions');
    multipleOptions.style.display = this.checked ? 'block' : 'none';
});

// Neuen Termin erstellen
document.getElementById('createAppointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const duration = document.getElementById('duration').value;
    const description = document.getElementById('description').value;
    const multipleSlots = document.getElementById('multipleSlots').checked;
    const slotCount = document.getElementById('slotCount').value;
    const slotInterval = document.getElementById('slotInterval').value;
    const messageDiv = document.getElementById('createMessage');
    
    // Praxis-ID aus globaler Variable (wird in dashboard_arzt.php gesetzt)
    if (typeof aktivePraxisId === 'undefined' || !aktivePraxisId) {
        messageDiv.innerHTML = `<div class="alert alert-danger">Bitte wählen Sie zuerst eine Praxis aus.</div>`;
        return;
    }
    
    const formData = new FormData();
    formData.append('date', date);
    formData.append('time', time);
    formData.append('praxis_id', aktivePraxisId);
    if (duration) {
        formData.append('duration', duration);
    }
    if (description) {
        formData.append('description', description);
    }
    if (multipleSlots) {
        formData.append('multipleSlots', 'true');
        formData.append('slotCount', slotCount);
        formData.append('slotInterval', slotInterval);
    }
    
    fetch('../api/create_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            
            // Bei Mehrfach-Terminen Seite neu laden für korrekte Anzeige
            if (multipleSlots || data.count > 1) {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                // Einzelnen Termin dynamisch zur Liste hinzufügen
                addTerminToList(date, time, duration, description);
                
                // Erfolgs-Nachricht nach 3 Sekunden ausblenden
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 3000);
            }
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = `<div class="alert alert-danger">Fehler beim Erstellen des Termins</div>`;
        console.error('Error:', error);
    });
});

// Termin bestätigen
function confirmAppointment(appointmentId) {
    if (!confirm('Möchten Sie diesen Termin wirklich bestätigen?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    
    fetch('../api/confirm_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Fehler beim Bestätigen des Termins');
        console.error('Error:', error);
    });
}

// Funktion zum dynamischen Hinzufügen eines Termins zur Liste
function addTerminToList(date, time, duration, description) {
    // Prüfen, ob "Keine freien Termine"-Nachricht existiert und diese entfernen
    const keineTermineMsg = document.getElementById('keineFreienTermine');
    if (keineTermineMsg) {
        keineTermineMsg.remove();
    }
    
    // Prüfen, ob Tabelle existiert, sonst erstellen
    let tbody = document.getElementById('freieTermineTbody');
    if (!tbody) {
        const freieTermineBody = document.getElementById('freieTermineBody');
        freieTermineBody.innerHTML = `
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
                    </tbody>
                </table>
            </div>
        `;
        tbody = document.getElementById('freieTermineTbody');
    }
    
    // Datum formatieren (von YYYY-MM-DD zu DD.MM.YYYY)
    const [year, month, day] = date.split('-');
    const formattedDate = `${day}.${month}.${year}`;
    
    // Zeit formatieren (von HH:MM zu HH:MM Uhr)
    const formattedTime = `${time} Uhr`;
    
    // Dauer formatieren
    const formattedDuration = duration ? `${duration} Min.` : '-';
    
    // Beschreibung formatieren
    const formattedDescription = description ? description : '-';
    
    // Neue Zeile erstellen
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${formattedDate}</td>
        <td>${formattedTime}</td>
        <td>${formattedDuration}</td>
        <td>${formattedDescription}</td>
        <td><span class="badge bg-secondary">Frei</span></td>
    `;
    
    // Zeile am Anfang der Tabelle einfügen (neueste oben)
    tbody.insertBefore(newRow, tbody.firstChild);
    
    // Zähler aktualisieren
    const header = document.getElementById('freieTermineHeader');
    const currentCount = tbody.children.length;
    header.textContent = `Freie Termine (${currentCount})`;
}

// Datum-Feld auf heute als Minimum setzen
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
});

// Termin ablehnen
function rejectAppointment(appointmentId) {
    if (!confirm('Möchten Sie diesen Termin wirklich ablehnen? Der Patient wird benachrichtigt.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('action', 'reject');
    
    fetch('../api/delete_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Zeile sofort aus der Tabelle entfernen
            const row = document.getElementById('termin-' + appointmentId);
            if (row) {
                row.remove();
            }
            alert(data.message);
            // Seite neu laden, um Zähler zu aktualisieren
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Fehler beim Ablehnen des Termins');
        console.error('Error:', error);
    });
}

// Termin löschen
function deleteAppointment(appointmentId) {
    if (!confirm('Möchten Sie diesen Termin wirklich stornieren? Der Patient wird benachrichtigt.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('action', 'delete');
    
    fetch('../api/delete_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Zeile sofort aus der Tabelle entfernen
            const row = document.getElementById('termin-' + appointmentId);
            if (row) {
                row.remove();
            }
            alert(data.message);
            // Seite neu laden, um Zähler zu aktualisieren
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Fehler beim Löschen des Termins');
        console.error('Error:', error);
    });
}

// Toggle für vergangene Termine
function toggleVergangeneTermine() {
    const vergangeneDiv = document.getElementById('vergangeneTermine');
    const toggleBtn = document.getElementById('toggleBtn');
    
    if (vergangeneDiv.style.display === 'none') {
        vergangeneDiv.style.display = 'block';
        toggleBtn.innerHTML = '- Vergangene Termine ausblenden';
    } else {
        vergangeneDiv.style.display = 'none';
        const count = document.querySelectorAll('#vergangeneTermine tbody tr').length;
        toggleBtn.innerHTML = '+ ' + count + ' vergangene Termine anzeigen';
    }
}

// Buchungsstatus für Praxis umschalten
function toggleBookings(praxisId, currentStatus) {
    const newStatus = currentStatus ? 0 : 1;
    const confirmMsg = currentStatus 
        ? 'Möchten Sie Terminbuchungen wirklich deaktivieren? Patienten können dann keine neuen Termine bei dieser Praxis buchen.'
        : 'Möchten Sie Terminbuchungen wieder aktivieren?';
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('praxis_id', praxisId);
    formData.append('accepting', newStatus);
    
    fetch('../api/toggle_bookings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Fehler: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Es ist ein Fehler aufgetreten.');
    });
}

// Auslastungsstatus für Praxis aktualisieren
function updateOccupancyStatus(praxisId, status) {
    const statusNames = {
        'low': 'Nicht überlaufen',
        'medium': 'Mittel überlaufen',
        'high': 'Stark überlaufen'
    };
    
    const formData = new FormData();
    formData.append('praxis_id', praxisId);
    formData.append('status', status);
    
    fetch('../api/update_occupancy_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Erfolgsmeldung kurz anzeigen
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i> ${data.message}: <strong>${statusNames[status]}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Nach 3 Sekunden automatisch ausblenden
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
            
            // Button-Farben aktualisieren
            location.reload();
        } else {
            alert('Fehler: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Es ist ein Fehler aufgetreten.');
    });
}

