(function() {
    const TIMEOUT = 900;
    const WARNING_BEFORE = 120;
    const CHECK_INTERVAL = 30000;

    function checkSession() {
        const lastActivity = parseInt(document.body.dataset.lastActivity || '0', 10);
        const elapsed = Math.floor(Date.now() / 1000) - lastActivity;
        const remaining = TIMEOUT - elapsed;

        if (remaining <= WARNING_BEFORE && remaining > 0 && !document.getElementById('session-warning-modal')) {
            showWarning(remaining);
        } else if (remaining <= 0) {
            window.location.href = '/sigsm/views/login.php?expired=1';
        }
    }

    function showWarning(seconds) {
        const minutes = Math.floor(seconds / 60);
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.setAttribute('role', 'alertdialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'session-warning-title');
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-warning">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title fw-bold" id="session-warning-title">Sesión por expirar</h5>
                    </div>
                    <div class="modal-body">
                        <p>Su sesión expirará en <strong id="countdown">${minutes} minuto${minutes !== 1 ? 's' : ''}</strong>.</p>
                        <p class="mb-0">Haga clic en "Continuar trabajando" para mantener su sesión activa.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="extend-session">Continuar trabajando</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        document.body.insertAdjacentHTML('beforeend', '<div class="modal-backdrop fade show"></div>');

        document.getElementById('extend-session').addEventListener('click', function() {
            fetch('/sigsm/includes/extend_session.php')
                .then(() => {
                    document.body.dataset.lastActivity = Math.floor(Date.now() / 1000);
                    modal.remove();
                    document.querySelector('.modal-backdrop')?.remove();
                });
        });

        document.getElementById('countdown').focus();
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.body.dataset.lastActivity = Math.floor(Date.now() / 1000);
        setInterval(checkSession, CHECK_INTERVAL);

        ['click', 'keydown', 'mousemove'].forEach(evt => {
            document.addEventListener(evt, () => {
                document.body.dataset.lastActivity = Math.floor(Date.now() / 1000);
            }, { passive: true });
        });
    });
})();