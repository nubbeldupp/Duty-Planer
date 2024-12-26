document.addEventListener('DOMContentLoaded', function() {
    // Initialize FullCalendar if the calendar div exists
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: true,
            events: '/api/schedules.php', // Endpoint to fetch schedules
            eventClick: handleEventClick,
            dateClick: handleDateClick
        });
        calendar.render();
    }

    function handleEventClick(info) {
        const scheduleId = info.event.id;
        // Open modal to view/edit schedule details
        fetch(`/api/schedule-details.php?id=${scheduleId}`)
            .then(response => response.json())
            .then(scheduleDetails => {
                // Populate modal with schedule details
                displayScheduleModal(scheduleDetails);
            });
    }

    function handleDateClick(info) {
        // Open modal to create new schedule
        const createScheduleModal = document.getElementById('create-schedule-modal');
        createScheduleModal.querySelector('#schedule-date').value = info.dateStr;
        createScheduleModal.style.display = 'block';
    }

    // Schedule Request Approval System
    function sendScheduleChangeRequest(requestData) {
        fetch('/api/schedule-change-request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Schedule change request sent successfully');
            } else {
                alert('Failed to send schedule change request');
            }
        });
    }

    // Role-Based Access Control
    function checkUserPermissions() {
        fetch('/api/user-permissions.php')
            .then(response => response.json())
            .then(permissions => {
                // Hide/show elements based on user role
                const adminElements = document.querySelectorAll('.admin-only');
                const teamLeadElements = document.querySelectorAll('.team-lead-only');
                
                adminElements.forEach(el => {
                    el.style.display = permissions.isAdmin ? 'block' : 'none';
                });
                
                teamLeadElements.forEach(el => {
                    el.style.display = (permissions.isAdmin || permissions.isTeamLead) ? 'block' : 'none';
                });
            });
    }

    // Initialize role-based access
    checkUserPermissions();
});
