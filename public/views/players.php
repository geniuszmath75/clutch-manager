<?php
$role = $_SESSION['user']['system_role'] ?? '';
$teamId = $_SESSION['user']['team_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" initial-scale=1>
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="/public/assets/css/players.css">
    <title>Players</title>
</head>
<body class="players-page"
      data-role="<?= htmlspecialchars($role) ?>"
      data-team-id="<?= !empty($teamId) ? htmlspecialchars((string)$teamId) : '' ?>">

<!-- MOBILE TOPBAR (hidden on desktop) -->
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

<!-- OVERLAY (mobile drawer backdrop) -->
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
                <span class="sidebar-nav__link-icon">
                    <i class="fa-solid fa-gauge"></i>
                </span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="/dashboard/players" class="sidebar-nav__link is-active" aria-current="page">
                <span class="sidebar-nav__link-icon">
                    <i class="fa-solid fa-users"></i>
                </span>
                Players
            </a>
        </li>
        <li>
            <a href="/dashboard/matches" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon">
                    <i class="fa-solid fa-trophy"></i>
                </span>
                Matches
            </a>
        </li>
        <li>
            <a href="/dashboard/strategies" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon">
                    <i class="fa-solid fa-chess"></i>
                </span>
                Strategies
            </a>
        </li>
        <li>
            <a href="/dashboard/settings" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon">
                    <i class="fa-solid fa-gear"></i>
                </span>
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

<main class="players-page__content" id="app">
    <!-- Page header -->
    <header class="page-header">
        <div class="page-header__titles">
            <h1 class="page-header__title" id="page-title">Players Management</h1>
            <p class="page-header__subtitle">Review performance stats and manage your team roster.</p>
        </div>
        <!-- Visible for ADMIN and COACH only (toggled by players.ts) -->
        <?php
            if ($role === 'COACH' || $role === 'ADMIN'): ?>
                        <button id="btn-add-player" class="btn-accent btn-accent__label btn-add-player">
                            <i class="fas fa-user-plus"></i>
                            Add Player
                        </button>
        <?php endif; ?>
    </header>

    <!-- Loading / error states -->
    <div id="players-loading" class="state-loading" aria-live="polite">
        <i class="fa-solid fa-spinner"></i>
        Loading players...
    </div>
    <div id="players-error" class="state-error" hidden role="alert"></div>

    <!-- Players list -->
    <div id="players-list" hidden>

        <!-- Filters -->
        <div class="players-filters">
            <div class="custom-select">

                <input
                        type="hidden"
                        name="team_role_ident"
                        id="role-filter"
                        required
                >

                <button type="button" class="custom-select__trigger" aria-expanded="false">
                    All roles
                    <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                </button>

                <div class="custom-select__dropdown">
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="ALL"
                    >
                        All roles
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="IGL"
                    >
                        IGL
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="AWP"
                    >
                        AWP
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="ENTRY"
                    >
                        Entry Fragger
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="SUPPORT"
                    >
                        Support
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="LURKER"
                    >
                        Lurker
                    </button>
                </div>
            </div>
            <div class="custom-select">

                <input
                        type="hidden"
                        name="player_status"
                        id="status-filter"
                        required
                >

                <button type="button" class="custom-select__trigger" aria-expanded="false">
                    All statuses
                    <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                </button>

                <div class="custom-select__dropdown">
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="ALL"
                    >
                        All statuses
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="ACTIVE"
                    >
                        Active
                    </button>
                    <button
                            type="button"
                            class="custom-select__option"
                            data-value="INACTIVE"
                    >
                        Inactive
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="players-table-wrapper">
            <table class="players-table" aria-label="Players list">
                <thead>
                <tr>
                    <th scope="col">Nickname</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="col-actions">Actions</th>
                </tr>
                </thead>
                <tbody id="players-tbody"></tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav id="pagination" class="pagination-wrapper" aria-label="Player pagination" hidden>
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
    </div>

    <!-- Modal: Edit player -->
    <dialog id="modal-edit-player" class="modal modal-edit-player" aria-labelledby="modal-edit-title">
            <div class="modal__header">
                <h2 id="modal-edit-title" class="modal__title">Edit Player</h2>
                <button type="button" id="btn-close-edit" class="btn-modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-edit-player" class="modal__body" method="dialog" novalidate>
                <div class="form-field">
                    <label class="form-field__label" for="edit-nickname">Nickname</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="fa-solid fa-user fa-xs" style="color: var(--quaternary)"></i>
                        </span>
                        <input type="text" id="edit-nickname" name="nickname"
                               minlength="2" maxlength="32" required
                               placeholder="Player nickname">
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-field__label" for="edit-team-role">Team Role</label>
                    <div class="input-wrapper">
                        <div class="custom-select">

                            <input
                                    type="hidden"
                                    name="team_role_ident"
                                    id="edit-team-role"
                                    required
                            >

                            <button type="button" id="edit-team-role-trigger" class="custom-select__trigger" aria-expanded="false">
                                Select role
                                <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                            </button>

                            <div class="custom-select__dropdown">
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="IGL"
                                >
                                    IGL
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="AWP"
                                >
                                    AWP
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="ENTRY"
                                >
                                    Entry Fragger
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="SUPPORT"
                                >
                                    Support
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="LURKER"
                                >
                                    Lurker
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <p id="edit-error" class="modal-error" role="alert" hidden></p>

                <div class="modal__actions">
                    <button type="button" id="btn-cancel-edit" class="btn-secondary">
                        <span class="btn-secondary__label">Cancel</span>
                    </button>
                    <button type="submit" id="btn-save-player" class="btn-accent">
                        <span class="btn-accent__label">Save Changes</span>
                    </button>
                </div>
            </form>
    </dialog>

    <!-- Modal: Add player to team -->
    <dialog id="modal-add-player" class="modal modal-add-player" aria-labelledby="modal-add-title">
            <div class="modal__header">
                <h2 id="modal-add-title" class="modal__title">Add Player to Team</h2>
                <button type="button" id="btn-close-add" class="btn-modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal__body">
                <div class="form-field">
                    <label class="form-field__label" for="add-player-select">Player</label>
                    <div class="input-wrapper">
                        <div class="custom-select">

                            <input
                                    type="hidden"
                                    name="player_status"
                                    id="add-player-select"
                                    required
                            >

                            <button type="button" id="add-player-select-trigger" class="custom-select__trigger" aria-expanded="false">
                                Loading...
                                <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                            </button>

                            <div id="add-player-select-dropdown" class="custom-select__dropdown">
                                    <button
                                            type="button"
                                            class="custom-select__option"
                                            data-value="">
                                        Loading...
                                    </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team selector — visible for ADMIN only; COACH uses session team_id -->
                <?php
                if ($role === 'ADMIN'): ?>
                <div id="team-select-wrapper" class="form-field">
                    <label class="form-field__label" for="add-team-select">Team</label>
                    <div class="input-wrapper">
                        <div class="custom-select">

                            <input
                                    type="hidden"
                                    name="player_status"
                                    id="add-team-select"
                                    required
                            >

                            <button type="button" class="custom-select__trigger" aria-expanded="false">
                                Select team
                                <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                            </button>

                            <div class="custom-select__dropdown">
                                <?php
                                if (!empty($teams)) {
                                    foreach ($teams as $team) {
                                        printf(
                                                '<button
                                                            type="button"
                                                            class="custom-select__option"
                                                            data-value="%s">%s</button>',
                                                htmlspecialchars((string)$team->id),
                                                htmlspecialchars($team->name)
                                        );
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <p id="add-error" class="modal-error" role="alert" hidden></p>

                <div class="modal__actions">
                    <button type="button" id="btn-cancel-add" class="btn-secondary">
                        <span class="btn-secondary__label">Cancel</span>
                    </button>
                    <button type="button" id="btn-confirm-add" class="btn-accent">
                        <span class="btn-accent__label">Add to Team</span>
                    </button>
                </div>
            </div>
    </dialog>
</main>

<script type="module" src="/public/assets/js/helpers/custom-select.js"></script>
<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/players.js"></script>
</body>
</html>