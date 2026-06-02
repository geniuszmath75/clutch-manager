<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$canWrite = in_array($systemRole, ['COACH', 'ADMIN'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details</title>
    <link rel="icon" type="image/x-icon" href="/public/assets/img/logo.svg">
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="/public/assets/css/match-details.css">
</head>
<body class="match-details-page"
        data-match-id="<?= !empty($matchId) ? (int)$matchId : null ?>"
        data-system-role="<?= htmlspecialchars($systemRole) ?>"
        data-can-write="<?= $canWrite ? 'true' : 'false' ?>"
>

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
            <a href="/dashboard/matches" class="sidebar-nav__link is-active" aria-current="page">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-trophy"></i></span>
                Matches
            </a>
        </li>
        <li>
            <a href="/dashboard/strategies" class="sidebar-nav__link">
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

<main class="match-details-page__content" id="app">

    <!-- Back link -->
    <a href="/dashboard/matches" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Matches
    </a>

    <!-- Loading / error states -->
    <div id="details-loading" class="state-loading" aria-live="polite">
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading match...
    </div>
    <div id="details-error" class="state-error" hidden role="alert"></div>

    <!-- MATCH HEADER -->
    <div id="match-header" class="match-header" hidden>
        <div class="match-header__meta">
            <span id="meta-result-badge" class="badge"></span>
            <span id="meta-played-at" class="match-header__date"></span>
            <span id="meta-game-mode" class="badge badge--secondary"></span>
        </div>

        <h1 id="meta-title" class="match-header__title"></h1>

        <div class="match-header__score">
            <span id="meta-score-label" class="match-header__score-label">Score: </span>
            <h2 id="meta-score" class="match-header__score-value"></h2>
        </div>

        <div class="match-header__tags">
            <span id="meta-map" class="badge badge--secondary">
                <i class="fa-solid fa-map"></i>
            </span>
            <span id="meta-duration" class="badge badge--secondary">
                <i class="fa-regular fa-clock"></i>
            </span>
        </div>
    </div>

    <!-- STATS TABLE -->
    <div id="stats-section" class="stats-section" hidden>
        <div class="stats-table-wrapper">
            <table class="stats-table" aria-label="Player statistics">
                <thead>
                <tr>
                    <th scope="col">Player</th>
                    <th scope="col">K / D / A</th>
                    <th scope="col">+/-</th>
                    <th scope="col">ADR</th>
                    <th scope="col">KAST</th>
                    <th scope="col">HS %</th>
                    <th scope="col">Flash Assists</th>
                </tr>
                </thead>
                <tbody id="stats-tbody"></tbody>
            </table>
        </div>
    </div>

    <!-- ACTIONS (COACH / ADMIN only) -->
    <?php if ($canWrite): ?>
        <div id="match-actions" class="match-actions" hidden>
            <button class="btn-accent" id="btn-edit-match">
                <i class="fa-solid fa-pen"></i>
                <span class="btn-accent__label">Edit Match</span>
            </button>
            <button class="btn-danger" id="btn-delete-match">
                <i class="fa-solid fa-trash"></i>
                Delete Match
            </button>
        </div>
    <?php endif; ?>

</main>


<!-- MODAL — EDIT MATCH; TypeScript pre-fills the fields. -->
<?php if ($canWrite): ?>
    <dialog id="modal-edit-match" class="modal" aria-labelledby="modal-edit-title">

        <div class="modal__header">
            <h2 id="modal-edit-title" class="modal__title">Edit Match</h2>
            <button class="btn-modal-close" id="btn-edit-modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal__body">

            <!-- Match Info -->
            <section class="modal-section" aria-labelledby="section-edit-info">
                <h3 id="section-edit-info" class="modal-section__title">Match Info</h3>

                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-field__label" for="edit-opponent">Opponent Team</label>
                        <input type="text" id="edit-opponent" name="opponent_name" maxlength="255" placeholder="e.g. Natus Vincere">
                    </div>

                    <div class="form-field">
                        <label class="form-field__label" for="edit-team-score">Team Score</label>
                        <input type="number" id="edit-team-score" name="team_score" min="0" max="99" placeholder="0">
                    </div>

                    <div class="form-field">
                        <label class="form-field__label" for="edit-opponent-score">Opponent Score</label>
                        <input type="number" id="edit-opponent-score" name="opponent_score" min="0" max="99" placeholder="0">
                    </div>

                    <div class="form-field">
                        <label class="form-field__label">Map</label>
                        <div class="custom-select" id="edit-map-select">
                            <input type="hidden" id="edit-map" name="map_id">
                            <button type="button" id="edit-map-trigger" class="custom-select__trigger" aria-expanded="false">
                                Select map
                                <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                            </button>
                            <div id="edit-map-dropdown" class="custom-select__dropdown"></div>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-field__label">Game Mode</label>
                        <div class="custom-select" id="edit-mode-select">
                            <input type="hidden" id="edit-game-mode" name="game_mode_id">
                            <button type="button" id="edit-mode-trigger" class="custom-select__trigger" aria-expanded="false">
                                Select mode
                                <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                            </button>
                            <div id="edit-mode-dropdown" class="custom-select__dropdown"></div>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-field__label" for="edit-duration">Duration (min)</label>
                        <input type="number" id="edit-duration" name="duration" min="1" max="999" placeholder="45">
                    </div>

                    <div class="form-field">
                        <label class="form-field__label" for="edit-played-at">Date &amp; Time</label>
                        <input type="datetime-local" id="edit-played-at" name="played_at">
                    </div>
                </div>
            </section>

            <!-- Player Stats -->
            <section class="modal-section" aria-labelledby="section-edit-stats">
                <h3 id="section-edit-stats" class="modal-section__title">Player Stats</h3>

                <div class="edit-stats-table-wrapper">
                    <table class="edit-stats-table" id="edit-stats-table" aria-label="Edit player statistics">
                        <thead>
                        <tr>
                            <th scope="col">Nickname</th>
                            <th scope="col">Kills</th>
                            <th scope="col">Deaths</th>
                            <th scope="col">Assists</th>
                            <th scope="col">Flash Assists</th>
                            <th scope="col">Damage</th>
                            <th scope="col">HS %</th>
                            <th scope="col">RKAST</th>
                        </tr>
                        </thead>
                        <tbody id="edit-stats-tbody"></tbody>
                    </table>
                </div>
            </section>

        </div>

        <div class="modal__footer">
            <p id="edit-modal-error" class="modal-error" hidden role="alert"></p>
            <div class="modal__actions">
                <button class="btn-secondary" id="btn-edit-cancel">Cancel</button>
                <button class="btn-accent" id="btn-update-match">
                    <span class="btn-accent__label">Save Changes</span>
                </button>
            </div>
        </div>

    </dialog>

    <!-- CONFIRM DELETE DIALOG -->
    <dialog id="modal-confirm-delete" class="modal match-details-modal" aria-labelledby="modal-delete-title">

        <div class="modal__header">
            <h2 id="modal-delete-title" class="modal__title">Delete Match</h2>
        </div>

        <div class="modal__body">
            <p>Are you sure you want to delete this match? This action cannot be undone.</p>
        </div>

        <div class="modal__footer">
            <div class="modal__actions">
                <button class="btn-secondary" id="btn-delete-cancel">Cancel</button>
                <button class="btn-danger" id="btn-delete-confirm">
                    <i class="fa-solid fa-trash"></i>
                    Delete
                </button>
            </div>
        </div>

    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/match-details.js"></script>
</body>
</html>