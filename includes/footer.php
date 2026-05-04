    <!-- ============================================================
         Page footer + JS bundle
    ============================================================ -->
    <footer class="footer mt-auto py-2 px-3 border-top bg-body-secondary">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <small class="text-muted">
                &copy; <?= date('Y') ?> <?= htmlspecialchars(
                    function_exists('getSetting') ? getSetting('hospital_name', APP_NAME) : APP_NAME,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </small>
            <small class="text-muted">
                <?= APP_NAME ?> v<?= APP_VERSION ?>
            </small>
        </div>
    </footer>

    <!-- ============================================================
         JavaScript libraries
    ============================================================ -->

    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>

    <!-- Bootstrap 5.3 bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>

    <!-- Chart.js 4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- DataTables 1.13 -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- FullCalendar 6.1 -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <!-- ============================================================
         Global JS initialisation
    ============================================================ -->
    <script>
    // CSRF token for all AJAX requests
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    $.ajaxSetup({
        headers: { 'X-CSRF-Token': CSRF_TOKEN }
    });

    // ---- DataTables default init --------------------------------
    $(document).ready(function () {

        // Auto-init any table with class .dt-table
        $('.dt-table').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                search:        'Filter:',
                lengthMenu:    'Show _MENU_ entries',
                info:          'Showing _START_ to _END_ of _TOTAL_ entries',
                emptyTable:    'No data available',
                zeroRecords:   'No matching records found',
            },
            order: [[0, 'desc']],
        });

        // ---- Select2 default init --------------------------------
        $('.select2').select2({
            theme:       'bootstrap-5',
            width:       '100%',
            placeholder: $(this).data('placeholder') || 'Select…',
            allowClear:  true,
        });

        // ---- Flatpickr default init ------------------------------
        flatpickr('.date-picker', {
            dateFormat: 'd/m/Y',
            allowInput: true,
        });
        flatpickr('.datetime-picker', {
            dateFormat:  'd/m/Y H:i',
            enableTime:  true,
            time_24hr:   true,
            allowInput:  true,
        });
        flatpickr('.time-picker', {
            enableTime:  true,
            noCalendar:  true,
            dateFormat:  'H:i',
            time_24hr:   true,
            allowInput:  true,
        });

        // ---- Dark mode toggle -----------------------------------
        const toggleBtn = document.getElementById('darkModeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const html = document.documentElement;
                const isDark = html.getAttribute('data-bs-theme') === 'dark';
                html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
                document.cookie = 'otms_dark_mode=' + (isDark ? '0' : '1')
                    + '; path=/; max-age=' + (365 * 86400) + '; SameSite=Strict';
                this.querySelector('i')?.classList.toggle('fa-moon', isDark);
                this.querySelector('i')?.classList.toggle('fa-sun',  !isDark);
            });
        }

        // ---- Session timeout warning ----------------------------
        const sessionTimeoutMs = <?= defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT * 60 * 1000 : 1800000 ?>;
        const warningMs        = sessionTimeoutMs - (2 * 60 * 1000); // warn 2 min before

        if (sessionTimeoutMs > 0) {
            setTimeout(function () {
                Swal.fire({
                    title:             'Session Expiring Soon',
                    text:              'Your session will expire in 2 minutes. Save your work.',
                    icon:              'warning',
                    showCancelButton:  true,
                    confirmButtonText: 'Stay Logged In',
                    cancelButtonText:  'Logout',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        // Ping server to refresh session
                        $.get('<?= defined('BASE_URL') ? BASE_URL : '/' ?>api/ping.php');
                    } else {
                        window.location.href = '<?= defined('BASE_URL') ? BASE_URL : '/' ?>logout.php';
                    }
                });
            }, warningMs);

            setTimeout(function () {
                window.location.href = '<?= defined('BASE_URL') ? BASE_URL : '/' ?>login.php?timeout=1';
            }, sessionTimeoutMs);
        }
    });

    // ---- SweetAlert2 confirm helper ----------------------------
    /**
     * Show a confirm dialog before following a link or submitting a form.
     *
     * Usage (link):
     *   <a href="delete.php?id=1" onclick="return confirmAction(this,'Delete this record?')">Delete</a>
     *
     * Usage (form submit):
     *   <form onsubmit="return confirmAction(null,'Are you sure?')">
     */
    function confirmAction(el, message, title) {
        Swal.fire({
            title:             title || 'Are you sure?',
            text:              message || 'This action cannot be undone.',
            icon:              'warning',
            showCancelButton:  true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText:  'Cancel',
        }).then(function (result) {
            if (result.isConfirmed && el) {
                window.location.href = el.href;
            } else if (result.isConfirmed && !el) {
                // caller is responsible for form submit after resolved
            }
        });
        return false; // Prevent default navigation; SweetAlert2 handles it
    }

    /**
     * Display a SweetAlert2 toast notification.
     *
     * @param {string} message
     * @param {string} type  - 'success' | 'error' | 'warning' | 'info'
     */
    function showToast(message, type) {
        const Toast = Swal.mixin({
            toast:            true,
            position:         'top-end',
            showConfirmButton: false,
            timer:            3500,
            timerProgressBar: true,
        });
        Toast.fire({ icon: type || 'success', title: message });
    }
    </script>

    <?php
    // Flash toast messages set in $_SESSION
    if (!empty($_SESSION['flash_success'])): ?>
    <script>
        $(document).ready(() => showToast(<?= json_encode($_SESSION['flash_success']) ?>, 'success'));
    </script>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
    <script>
        $(document).ready(() => showToast(<?= json_encode($_SESSION['flash_error']) ?>, 'error'));
    </script>
    <?php unset($_SESSION['flash_error']); endif; ?>

</body>
</html>
