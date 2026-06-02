<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$canWrite = in_array($systemRole, ['COACH', 'ADMIN'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategies</title>
    <link rel="icon" type="image/x-icon" href="/public/assets/img/logo.svg">
    <link rel="stylesheet" type="text/css" href="/public/assets/css/strategies.css" >
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
</head>
<body class="strategies-page" data-system-role="<?= htmlspecialchars($systemRole) ?>">

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
    <button class="btn-nav-toggle" id="btn-nav-toggle" aria-label="Open navigation"
            aria-expanded="false" aria-controls="sidebar-nav">
        <i class="fa-solid fa-bars" id="nav-toggle-icon"></i>
    </button>
</div>

<!-- OVERLAY -->
<div class="sidebar-nav__overlay" id="sidebar-overlay" aria-hidden="true"></div>

<!-- SIDEBAR -->
<nav class="sidebar-nav" id="sidebar-nav" aria-label="Main navigation">
    <a href="/dashboard" class="sidebar-nav__logo">
        <div class="sidebar-nav__logo-icon">
            <img src="/public/assets/img/logo.svg" alt="Clutch Manager">
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

<!-- MAIN CONTENT -->
<main class="strategies-page__content" id="app">

    <!-- Page header -->
    <header class="page-header">
        <div class="page-header__titles">
            <h1 class="page-header__title">Tactical Strategies</h1>
            <p class="page-header__subtitle">Manage your team's winning plays.</p>
        </div>
        <?php if ($canWrite): ?>
            <button class="btn-accent btn-add-strategy" id="btn-open-add">
                <i class="fa-solid fa-circle-plus btn-accent__icon"></i>
                <span class="btn-accent__label">Add Strategy</span>
            </button>
        <?php endif; ?>
    </header>

    <!-- Type tabs -->
    <div class="strategy-tabs" role="tablist" aria-label="Filter by strategy type">
        <button class="strategy-tabs__tab is-active" role="tab" data-type="" aria-selected="true">
            All Strategies
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="1" aria-selected="false">
            Site Attack
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="2" aria-selected="false">
            Site Defense
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="3" aria-selected="false">
            Eco Round
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="4" aria-selected="false">
            Default Setup
        </button>
    </div>

    <!-- Strategy cards grid -->
    <div class="strategy-grid" id="strategy-grid" hidden></div>

    <!-- Loading states -->
    <div id="strategies-loading" class="loading-state" aria-live="polite">
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading strategies...
    </div>

    <!-- Empty state -->
    <p class="empty-state" id="empty-state" hidden>No strategies found.</p>

    <!-- Error state -->
    <p class="error-state" id="error-state" hidden></p>

    <!-- Pagination -->
    <nav id="pagination" class="strategies-pagination" aria-label="Strategies pagination" hidden>
        <span id="pagination-info"></span>
        <div class="pagination-controls">
            <button id="btn-prev" class="btn-page" aria-label="Previous page">
                <i class="fa-solid fa-chevron-left"></i>
                Previous
            </button>
            <button id="btn-next" class="btn-page" aria-label="Next page">
                Next
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </nav>

</main>

<!-- ADD STRATEGY MODAL (COACH / ADMIN only) -->
<?php if ($canWrite): ?>
    <dialog class="modal" id="modal-add" aria-labelledby="modal-add-title">

        <div class="modal__header">
            <h2 class="modal__title" id="modal-add-title">Create New Strategy</h2>
            <button class="btn-modal-close" id="modal-add-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal__body">

            <!-- Row 1: Name + Map -->
            <div class="form-row">

                <div class="form-field">
                    <label class="form-field__label" for="add-name">
                        Strategy Name <span class="required">*</span>
                    </label>
                    <input type="text" id="add-name" placeholder="e.g. A-split Rush" maxlength="255">
                </div>

                <div class="form-field">
                    <label class="form-field__label">
                        Map <span class="required">*</span>
                    </label>
                    <!-- custom-select — options populated by TS after loadDictionaries() -->
                    <div class="custom-select" id="add-map-select">
                        <input type="hidden" id="add-map" name="map_id">
                        <button type="button" id="add-map-trigger" class="custom-select__trigger" aria-expanded="false">
                            Select map
                            <span class="custom-select__arrow">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                        </button>
                        <div id="add-map-dropdown" class="custom-select__dropdown"></div>
                    </div>
                </div>

            </div>

            <!-- Row 2: Strategy Type -->
            <div class="form-field">
                <label class="form-field__label">
                    Strategy Type <span class="required">*</span>
                </label>
                <div class="type-selector" id="add-type-selector" role="group" aria-label="Strategy type">
                    <?php
                    $typeLabels = ['ATTACK' => 'Attack', 'DEFENSE' => 'Defense', 'ECO' => 'Eco', 'DEFAULT' => 'Default'];
                    if (!empty($strategyTypes)) {
                        foreach ($strategyTypes as $st) {
                            $label = $typeLabels[$st['ident']] ?? ucfirst(strtolower($st['ident']));
                            printf(
                                    '<button type="button" class="type-selector__option" data-type-id="%d" aria-pressed="false">%s</button>',
                                    (int)$st['id'],
                                    htmlspecialchars($label)
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Row 3: Assigned Players -->
            <div class="form-field" id="player-field">
                <label class="form-field__label">Assigned Players</label>
                <div class="player-tag-input" id="add-player-tags">
                    <div class="player-tag-input__tags" id="add-tags-list"></div>
                    <div class="player-tag-input__dropdown-wrap">
                        <button type="button" class="btn-add-player-tag" id="btn-add-player-tag">
                            <i class="fa-solid fa-plus"></i>
                            Add Player
                        </button>
                        <div class="player-tag-input__dropdown" id="add-player-dropdown" hidden></div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Description -->
            <div class="form-field">
                <label class="form-field__label" for="add-description">
                    Description <span class="required">*</span>
                </label>
                <textarea id="add-description" rows="3"
                          placeholder="Briefly describe the goal of this strategy..."></textarea>
            </div>

            <!-- Row 5: Execution Steps -->
            <div class="form-field">
                <label class="form-field__label">Execution Steps</label>
                <div class="steps-input">
                    <div class="steps-input__add-row">
                        <input type="text" id="add-step-input" placeholder="Describe next step...">
                        <button type="button" class="btn-secondary" id="btn-add-step">Add</button>
                    </div>
                    <ol class="steps-list" id="add-steps-list"></ol>
                </div>
            </div>

        </div>

        <div class="modal__footer">
            <p class="modal-error" id="add-error" hidden role="alert"></p>
            <div class="modal__actions">
                <button class="btn-secondary" id="btn-add-cancel">
                    <span class="btn-secondary__label">Cancel</span>
                </button>
                <button class="btn-accent" id="btn-add-save">
                    <span class="btn-accent__label">Save Strategy</span>
                </button>
            </div>
        </div>

    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/strategies.js"></script>
</body>
</html>