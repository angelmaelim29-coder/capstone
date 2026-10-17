<!-- ============================================ -->
<!-- SHARED CONFIRM MODAL + TOAST (Include on any page) -->
<!-- ============================================ -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box" id="confirmBox">
        <div class="confirm-icon-wrap">
            <div class="confirm-icon success" id="confirmIcon">
                <i class="bi bi-check-circle-fill" id="confirmIconInner"></i>
            </div>
            <div class="confirm-title" id="confirmTitle">Confirm Action</div>
        </div>
        <div class="confirm-message" id="confirmMessage">
            Are you sure?
        </div>
        <div class="confirm-actions">
            <button class="btn-confirm-no" onclick="closeConfirm()">Cancel</button>
            <button class="btn-confirm-yes" id="confirmYesBtn">
                <i class="bi bi-check-lg"></i> Confirm
            </button>
        </div>
    </div>
</div>

<!-- App Toast -->
<div class="app-toast" id="appToast">
    <div class="app-toast-icon" id="appToastIconContainer">
        <i class="bi bi-check-circle-fill" id="appToastIcon"></i>
    </div>
    <div class="app-toast-content">
        <div class="app-toast-title" id="appToastTitle">Success!</div>
        <div class="app-toast-msg" id="appToastMsg">Action completed.</div>
    </div>
</div>