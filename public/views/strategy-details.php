<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$canWrite = in_array($systemRole, ['COACH', 'ADMIN', 'PLAYER'], true); // PLAYER can edit
$canDelete = in_array($systemRole, ['COACH', 'ADMIN'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategy Details – Clutch Manager</title>
</head>
<body
        data-system-role="<?= htmlspecialchars($systemRole) ?>"
        data-strategy-id="<?= !empty($strategyId) ? (int)$strategyId : null ?>">

<nav>
    <a href="/dashboard">Dashboard</a>
    <a href="/dashboard/players">Players</a>
    <a href="/dashboard/matches">Matches</a>
    <a href="/dashboard/strategies" aria-current="page">Strategies</a>
    <a href="/dashboard/settings">Settings</a>
    <form method="POST" action="/auth/logout" style="display:inline">
        <button type="submit">Log out</button>
    </form>
</nav>

<!-- MAIN -->
<main class="page-main" id="strategy-detail-main">

    <!-- Loading skeleton placeholder -->
    <div id="detail-loading" class="detail-loading">Loading strategy…</div>

    <!-- Populated by match-details.ts -->
    <div id="detail-content" hidden>

        <!-- ── Back + badges row ── -->
        <div class="detail-topbar">
            <a href="/dashboard/strategies" class="btn btn--ghost btn--sm">← Back to Strategies</a>
            <div class="detail-topbar__badges">
                <span class="badge badge--map" id="detail-map-badge"></span>
                <span class="badge badge--type" id="detail-type-badge"></span>
            </div>
        </div>

        <!-- ── Strategy name ── -->
        <h1 class="detail-title" id="detail-name"></h1>

        <!-- ── Cards grid ── -->
        <div class="detail-cards">

            <!-- Card 1: Assigned Players -->
            <div class="detail-card">
                <div class="detail-card__header">
                    <h2 class="detail-card__title">Assigned Players</h2>
                    <span class="detail-card__meta" id="detail-players-count"></span>
                </div>
                <hr class="detail-card__divider">
                <ul class="players-list" id="detail-players-list">
                    <!-- injected by TS -->
                </ul>
            </div>

            <!-- Card 2: Overview -->
            <div class="detail-card">
                <div class="detail-card__header">
                    <h2 class="detail-card__title">Overview</h2>
                </div>
                <hr class="detail-card__divider">
                <p class="detail-card__text" id="detail-description"></p>
            </div>

            <!-- Card 3: Steps To Do -->
            <div class="detail-card">
                <div class="detail-card__header">
                    <h2 class="detail-card__title">Steps To Do</h2>
                </div>
                <hr class="detail-card__divider">
                <ol class="steps-list steps-list--detail" id="detail-steps-list">
                    <!-- injected by TS -->
                </ol>
            </div>

        </div>

        <!-- Action buttons -->
        <div class="detail-actions" id="detail-actions">
            <?php if ($canWrite): ?>
                <button class="btn btn--secondary" id="btn-edit-strategy">Edit Strategy</button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <button class="btn btn--danger" id="btn-delete-strategy">Delete Strategy</button>
            <?php endif; ?>
        </div>


    </div>

    <!-- Error state -->
    <p class="error-state" id="detail-error" hidden></p>

</main>

<!-- EDIT STRATEGY MODAL -->
<dialog class="modal" id="modal-edit" aria-labelledby="modal-edit-title">
    <div class="modal__header">
        <h2 class="modal__title" id="modal-edit-title">Edit Strategy</h2>
        <button class="modal__close" id="modal-edit-close" aria-label="Close">&times;</button>
    </div>

    <div class="modal__body">

        <!-- Row 1: Name + Map -->
        <div class="form-row form-row--2col">
            <div class="form-field">
                <label class="form-label" for="edit-name">Strategy Name <span class="required">*</span></label>
                <input class="form-input" type="text" id="edit-name" maxlength="255">
            </div>
            <div class="form-field">
                <label class="form-label" for="edit-map">Map <span class="required">*</span></label>
                <select class="form-select" id="edit-map">
                    <option value="">Select map…</option>
                    <!-- populated by TS from /strategies/{id} response -->
                </select>
            </div>
        </div>

        <!-- Row 2: Strategy Type (segmented) -->
        <div class="form-field">
            <label class="form-label">Strategy Type <span class="required">*</span></label>
            <div class="type-selector" id="edit-type-selector" role="group" aria-label="Strategy type">
                <!-- populated by TS -->
            </div>
        </div>

        <!-- Row 3: Assigned Players -->
        <div class="form-field">
            <label class="form-label">Assigned Players</label>
            <div class="player-tag-input" id="edit-player-tags">
                <div class="player-tag-input__tags" id="edit-tags-list"></div>
                <div class="player-tag-input__dropdown-wrap">
                    <button type="button" class="btn btn--ghost btn--sm" id="btn-edit-add-player-tag">+ Add player
                    </button>
                    <div class="player-tag-input__dropdown" id="edit-player-dropdown" hidden></div>
                </div>
            </div>
        </div>

        <!-- Row 4: Description -->
        <div class="form-field">
            <label class="form-label" for="edit-description">Description <span class="required">*</span></label>
            <textarea class="form-textarea" id="edit-description" rows="3"></textarea>
        </div>

        <!-- Row 5: Execution Steps -->
        <div class="form-field">
            <label class="form-label">Execution Steps</label>
            <div class="steps-input">
                <div class="steps-input__add-row">
                    <input class="form-input" type="text" id="edit-step-input" placeholder="Describe next step…">
                    <button type="button" class="btn btn--secondary" id="btn-edit-add-step">Add</button>
                </div>
                <ol class="steps-list" id="edit-steps-list"></ol>
            </div>
        </div>

        <p class="form-error" id="edit-error" hidden></p>
    </div>

    <div class="modal__footer">
        <button class="btn btn--ghost" id="btn-edit-cancel">Cancel</button>
        <button class="btn btn--primary" id="btn-edit-save">Save Changes</button>
    </div>
</dialog>

<!-- DELETE CONFIRM MODAL -->
<?php if ($canDelete): ?>
    <dialog class="modal modal--sm" id="modal-delete" aria-labelledby="modal-delete-title">
        <div class="modal__header">
            <h2 class="modal__title" id="modal-delete-title">Delete Strategy</h2>
            <button class="modal__close" id="modal-delete-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal__body">
            <p class="modal__confirm-text">
                Are you sure you want to delete <strong id="modal-delete-name"></strong>?
                This action cannot be undone.
            </p>
            <p class="form-error" id="delete-error" hidden></p>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="btn-delete-cancel">Cancel</button>
            <button class="btn btn--danger" id="btn-delete-confirm">Delete</button>
        </div>
    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/strategy-details.js"></script>
</body>
</html>