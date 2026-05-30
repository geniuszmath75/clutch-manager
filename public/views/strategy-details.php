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
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="/public/assets/css/strategy-details.css">
    <title>Strategy Details – Clutch Manager</title>
</head>
<body
        class="strategy-details-page"
        data-system-role="<?= htmlspecialchars($systemRole) ?>"
        data-strategy-id="<?= !empty($strategyId) ? (int)$strategyId : null ?>">

<!-- MOBILE TOPBAR -->
<div class="mobile-topbar">
    <a href="/dashboard" class="mobile-topbar__logo">
        <div class="mobile-topbar__logo-icon">
            <img src="/public/assets/img/logo.svg" alt="Clutch Manager" width="20" height="20">
        </div>
        <div class="mobile-topbar__logo-text">
            <span>Clutch</span>
            <span class="mobile-topbar__logo-accent">Manager</span>
        </div>
    </a>
    <button class="btn-nav-toggle" id="btn-nav-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar-nav">
        <i class="fa-solid fa-bars" id="nav-toggle-icon"></i>
    </button>
</div>

<!-- OVERLAY -->
<div class="sidebar-nav__overlay" id="sidebar-overlay" aria-hidden="true"></div>

<!-- SIDEBAR NAVIGATION -->
<nav class="sidebar-nav" id="sidebar-nav" aria-label="Main navigation">
    <a href="/dashboard" class="sidebar-nav__logo">
        <div class="sidebar-nav__logo-icon">
            <img src="/public/assets/img/logo.svg" alt="Clutch Manager" width="20" height="20">
        </div>
        <div class="sidebar-nav__logo-text">
            <span>Clutch</span>
            <span class="sidebar-nav__logo-accent">Manager</span>
        </div>
    </a>

    <ul class="sidebar-nav__links" role="list">
        <li>
            <a href="/dashboard" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-gauge"></i></span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="/dashboard/players" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-users"></i></span>
                Players
            </a>
        </li>
        <li>
            <a href="/dashboard/matches" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-trophy"></i></span>
                Matches
            </a>
        </li>
        <li>
            <a href="/dashboard/strategies" class="sidebar-nav__link is-active" aria-current="page">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-chess"></i></span>
                Strategies
            </a>
        </li>
        <li>
            <a href="/dashboard/settings" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-gear"></i></span>
                Settings
            </a>
        </li>
    </ul>

    <div class="sidebar-nav__footer">
        <form method="POST" action="/auth/logout">
            <button type="submit" class="sidebar-nav__logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                Log out
            </button>
        </form>
    </div>
</nav>

<!-- MAIN -->
<main class="strategy-details-page__content" id="app">

    <!-- Back link -->
    <a href="/dashboard/strategies" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Strategies
    </a>

    <!-- Loading state -->
    <div id="detail-loading" class="state-loading" aria-live="polite">
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading strategy…
    </div>

    <!-- Error state -->
    <p class="state-error" id="detail-error" hidden role="alert"></p>

    <!-- Populated by strategy-details.ts -->
    <div id="detail-content" hidden>

        <!-- Strategy name -->
        <h1 class="detail-title" id="detail-name"></h1>

        <!-- Badges: Map + Type -->
        <div class="detail-badges">
            <span class="badge badge--map" id="detail-map-badge">
                <i class="fa-solid fa-map"></i>
            </span>
            <span class="badge badge--type" id="detail-type-badge">
                <i class="fa-solid fa-crosshairs"></i>
            </span>
        </div>

        <!-- Cards grid -->
        <div class="detail-cards">

            <!-- Card 1: Assigned Players -->
            <div class="detail-card">
                <div class="detail-card__header">
                    <h2 class="detail-card__title">
                        <i class="fa-solid fa-users"></i>
                        Assigned Players
                    </h2>
                    <span class="detail-card__meta" id="detail-players-count"></span>
                </div>
                <hr class="detail-card__divider">
                <ul class="players-list" id="detail-players-list"></ul>
            </div>

            <!-- Card 2: Overview -->
            <div class="detail-card">
                <div class="detail-card__header">
                    <h2 class="detail-card__title">
                        <i class="fa-solid fa-bars-staggered"></i>
                        Overview
                    </h2>
                </div>
                <hr class="detail-card__divider">
                <div class="detail-card__body">
                    <p class="detail-card__text" id="detail-description"></p>
                </div>
            </div>

            <!-- Card 3: Steps To Do -->
            <div class="detail-card">
                <div class="detail-card__header">
                    <h2 class="detail-card__title">
                        <i class="fa-solid fa-list-ol"></i>
                        Steps To Do
                    </h2>
                </div>
                <hr class="detail-card__divider">
                <ol class="steps-list steps-list--detail" id="detail-steps-list"></ol>
            </div>

        </div>

        <!-- Action buttons -->
        <div class="detail-actions" id="detail-actions">
            <?php if ($canWrite): ?>
                <button class="btn-accent" id="btn-edit-strategy">
                    <i class="fa-solid fa-pen btn-accent__icon"></i>
                    <span class="btn-accent__label">Edit Strategy</span>
                </button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <button class="btn-danger" id="btn-delete-strategy">
                    <i class="fa-solid fa-trash"></i>
                    Delete Strategy
                </button>
            <?php endif; ?>
        </div>

    </div>

</main>

<!-- EDIT STRATEGY MODAL -->
<dialog class="modal" id="modal-edit" aria-labelledby="modal-edit-title">

    <div class="modal__header">
        <h2 class="modal__title" id="modal-edit-title">Edit Strategy</h2>
        <button class="btn-modal-close" id="modal-edit-close" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="modal__body">

        <!-- Row 1: Name + Map -->
        <div class="form-row">
            <div class="form-field">
                <label class="form-field__label" for="edit-name">
                    Strategy Name <span class="required">*</span>
                </label>
                <input type="text" id="edit-name" maxlength="255" placeholder="e.g. A-split Rush">
            </div>

            <div class="form-field">
                <label class="form-field__label">Map <span class="required">*</span></label>
                <!-- custom-select — populated by TS after fetchDictionaries() -->
                <div class="custom-select" id="edit-map-select">
                    <input type="hidden" id="edit-map" name="map_id">
                    <button type="button" id="edit-map-trigger" class="custom-select__trigger" aria-expanded="false">
                        Select map…
                        <span class="custom-select__arrow">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </button>
                    <div id="edit-map-dropdown" class="custom-select__dropdown"></div>
                </div>
            </div>
        </div>

        <!-- Row 2: Strategy Type -->
        <div class="form-field">
            <label class="form-field__label">
                Strategy Type <span class="required">*</span>
            </label>
            <div class="type-selector" id="edit-type-selector" role="group" aria-label="Strategy type">
                <!-- populated by TS -->
            </div>
        </div>

        <!-- Row 3: Assigned Players -->
        <div class="form-field" id="edit-player-field">
            <label class="form-field__label">Assigned Players</label>
            <div class="player-tag-input" id="edit-player-tags">
                <div class="player-tag-input__tags" id="edit-tags-list"></div>
                <div class="player-tag-input__dropdown-wrap">
                    <button type="button" class="btn-add-player-tag" id="btn-edit-add-player-tag">
                        <i class="fa-solid fa-plus"></i>
                        Add Player
                    </button>
                    <div class="player-tag-input__dropdown" id="edit-player-dropdown" hidden></div>
                </div>
            </div>
        </div>

        <!-- Row 4: Description -->
        <div class="form-field">
            <label class="form-field__label" for="edit-description">
                Description <span class="required">*</span>
            </label>
            <textarea id="edit-description" rows="3"
                      placeholder="Briefly describe the goal of this strategy…"></textarea>
        </div>

        <!-- Row 5: Execution Steps -->
        <div class="form-field">
            <label class="form-field__label">Execution Steps</label>
            <div class="steps-input">
                <div class="steps-input__add-row">
                    <input type="text" id="edit-step-input" placeholder="Describe next step…">
                    <button type="button" class="btn-secondary" id="btn-edit-add-step">Add</button>
                </div>
                <ol class="steps-list" id="edit-steps-list"></ol>
            </div>
        </div>

    </div>

    <div class="modal__footer">
        <p class="modal-error" id="edit-error" hidden role="alert"></p>

        <div class="modal__actions">
            <button class="btn-secondary" id="btn-edit-cancel">
                <span class="btn-secondary__label">Cancel</span>
            </button>
            <button class="btn-accent" id="btn-edit-save">
                <span class="btn-accent__label">Save Changes</span>
            </button>
        </div>
    </div>

</dialog>

<!-- DELETE CONFIRM MODAL (COACH / ADMIN only) -->
<?php if ($canDelete): ?>
    <dialog class="modal" id="modal-delete" aria-labelledby="modal-delete-title">

        <div class="modal__header">
            <h2 class="modal__title" id="modal-delete-title">Delete Strategy</h2>
            <button class="btn-modal-close" id="modal-delete-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal__body">
            <p>Are you sure you want to delete <strong id="modal-delete-name"></strong>?
                This action cannot be undone.</p>
            <p class="modal-error" id="delete-error" hidden role="alert"></p>
        </div>

        <div class="modal__footer">
            <div class="modal__actions">
                <button class="btn-secondary" id="btn-delete-cancel">
                    <span class="btn-secondary__label">Cancel</span>
                </button>
                <button class="btn-danger" id="btn-delete-confirm">
                    <i class="fa-solid fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>

    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/strategy-details.js"></script>
</body>
</html>