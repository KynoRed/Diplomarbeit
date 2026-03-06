// JavaScript für Patienten Dashboard

// Termin buchen
function bookAppointment(appointmentId) {
    if (!confirm('Möchten Sie diesen Termin buchen?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    
    fetch('../api/book_appointment.php', {
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
        alert('Fehler beim Buchen des Termins');
        console.error('Error:', error);
    });
}
