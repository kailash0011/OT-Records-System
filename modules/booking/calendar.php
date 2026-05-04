<?php
/**
 * Booking Calendar (FullCalendar 6.1)
 * OT Records Management System
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$pageTitle = 'OT Scheduling Calendar';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex" id="wrapper">
<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
<div id="page-content-wrapper" class="flex-grow-1 overflow-auto">

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3 py-2">
        <span class="navbar-brand mb-0 h6 fw-bold"><i class="fas fa-calendar-week me-2 text-primary"></i>OT Scheduling Calendar</span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>booking_new.php" class="btn btn-primary btn-sm me-1">
                <i class="fas fa-plus me-1"></i>New Booking
            </a>
            <a href="<?= BASE_URL ?>booking_list.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list me-1"></i>List View
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <!-- Legend -->
        <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="badge" style="background:#3788d8;font-size:.75rem;padding:.4em .7em">Scheduled</span>
            <span class="badge" style="background:#f9a825;font-size:.75rem;padding:.4em .7em">In Progress</span>
            <span class="badge" style="background:#388e3c;font-size:.75rem;padding:.4em .7em">Completed</span>
            <span class="badge" style="background:#d32f2f;font-size:.75rem;padding:.4em .7em">Cancelled</span>
            <span class="badge" style="background:#757575;font-size:.75rem;padding:.4em .7em">Postponed</span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-2">
                <div id="calendar" style="min-height:600px;"></div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-check me-2 text-primary"></i>Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- populated by JS -->
            </div>
            <div class="modal-footer">
                <a href="#" id="eventEditLink" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const modal      = new bootstrap.Modal(document.getElementById('eventModal'));

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView:  'timeGridWeek',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        buttonText: {
            today:   'Today',
            month:   'Month',
            week:    'Week',
            day:     'Day',
            list:    'List',
        },
        slotMinTime:   '06:00:00',
        slotMaxTime:   '22:00:00',
        allDaySlot:    false,
        nowIndicator:  true,
        height:        'auto',
        navLinks:      true,
        editable:      false,
        selectable:    false,
        events: {
            url:     '<?= BASE_URL ?>api/get_schedule.php',
            method:  'GET',
            failure: function () {
                Swal.fire('Error', 'Failed to load schedule events.', 'error');
            },
        },
        eventClick: function (info) {
            const ev   = info.event;
            const p    = ev.extendedProps;
            const start = ev.start ? ev.start.toLocaleString('en-GB', {dateStyle:'short',timeStyle:'short'}) : '';
            const end   = ev.end   ? ev.end.toLocaleTimeString('en-GB', {timeStyle:'short'}) : '';

            document.getElementById('eventModalBody').innerHTML =
                '<table class="table table-sm table-borderless mb-0">'
                + '<tr><th class="text-muted w-35">Booking #</th><td class="fw-semibold">' + escHtml(p.booking_number) + '</td></tr>'
                + '<tr><th class="text-muted">Procedure</th><td>' + escHtml(ev.title) + '</td></tr>'
                + '<tr><th class="text-muted">Patient</th><td>' + escHtml(p.patient) + '</td></tr>'
                + '<tr><th class="text-muted">Surgeon</th><td>' + escHtml(p.surgeon) + '</td></tr>'
                + '<tr><th class="text-muted">OT Room</th><td>' + escHtml(p.room) + '</td></tr>'
                + '<tr><th class="text-muted">Status</th><td><span class="badge bg-primary">' + escHtml(p.status) + '</span></td></tr>'
                + '<tr><th class="text-muted">Priority</th><td>' + escHtml(p.priority) + '</td></tr>'
                + '<tr><th class="text-muted">Start</th><td>' + start + '</td></tr>'
                + '<tr><th class="text-muted">Duration</th><td>' + p.duration + ' min</td></tr>'
                + '</table>';

            document.getElementById('eventEditLink').href =
                '<?= BASE_URL ?>modules/booking/edit.php?id=' + ev.id;

            modal.show();
        },
        loading: function (isLoading) {
            calendarEl.style.opacity = isLoading ? '0.5' : '1';
        },
    });

    calendar.render();

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
