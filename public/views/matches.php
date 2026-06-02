<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$teamId = $_SESSION['user']['team_id'] ?? null;
$teamName = $_SESSION['user']['team_name'] ?? null;
$canWrite = in_array($systemRole, ['COACH', 'ADMIN'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matches</title>
    <link rel="icon" type="image/x-icon" href="/public/assets/img/logo.svg">
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="/public/assets/css/matches.css" />
</head>
<body
        class="matches-page"
        data-system-role="<?= htmlspecialchars($systemRole) ?>"
        data-team-id="<?= htmlspecialchars((string)$teamId) ?>"
>

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

<!-- MAIN CONTENT -->
<main class="matches-page__content" id="app">
    <!-- Page header -->
    <header class="page-header">
        <div class="page-header__titles">
            <h1 class="page-header__title">Matches</h1>
            <p class="page-header__subtitle">Manage the all played matches.</p>
        </div>
        <?php if ($canWrite): ?>
            <button id="btn-add-match" class="btn-accent btn-accent__label btn-add-match">
                <i class="fa-solid fa-circle-plus"></i>
                Add Match
            </button>
        <?php endif; ?>
    </header>

    <!-- Error banner -->
    <div id="error-banner" class="error-banner error-banner--hidden" hidden></div>

    <!-- Filters -->
    <div id="matches-filters" class="matches-filters">
        <div class="matches-filters__search">
            <i class="fa-solid fa-magnifying-glass matches-filters__search-icon"></i>
            <input
                    type="text"
                    id="filter-opponent"
                    placeholder="Search by opponent name..."
                    autocomplete="off"
                    aria-label="Search by opponent"
            >
        </div>

        <div class="custom-select">

            <input
                    type="hidden"
                    name="map_name"
                    id="filter-map"
                    required
            >

            <button type="button" class="custom-select__trigger" aria-expanded="false">
                All maps
                <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
            </button>

            <div class="custom-select__dropdown">
                <button
                        type="button"
                        class="custom-select__option"
                        data-value="ALL">
                    All maps
                </button>
                <?php
                if (!empty($maps)) {
                    foreach ($maps as $map) {
                        printf(
                                '<button
                                                            type="button"
                                                            class="custom-select__option"
                                                            data-value="%s">%s</button>',
                                htmlspecialchars($map['ident']),
                                htmlspecialchars($map['ident'])
                        );
                    }
                }
                ?>
            </div>
        </div>

        <div class="custom-select">

            <input
                    type="hidden"
                    name="result_name"
                    id="filter-result"
                    required
            >

            <button type="button" class="custom-select__trigger" aria-expanded="false">
                All results
                <span class="custom-select__arrow">
                        <i class="fa-solid fa-chevron-down"></i>
                </span>
            </button>

            <div class="custom-select__dropdown">
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="ALL">
                                    All results
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="WIN">
                                    Win
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="LOSS">
                                    Loss
                                </button>
                                <button
                                        type="button"
                                        class="custom-select__option"
                                        data-value="DRAW">
                                    Draw
                                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="matches-table-wrapper">
        <table class="matches-table" id="matches-table" aria-label="Matches list">
            <thead>
            <tr>
                <th scope="col">Opponent</th>
                <th scope="col">Map</th>
                <th scope="col">Score</th>
                <th scope="col">Result</th>
                <th scope="col">Date</th>
                <th scope="col" class="col-actions">Actions</th>
            </tr>
            </thead>
            <tbody id="matches-tbody">
            <tr>
                <td class="empty-state" colspan="7">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Loading...
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav id="pagination" class="pagination-wrapper" aria-label="Matches pagination" hidden>
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

    <!-- MODAL — ADD MATCH -->
    <?php if ($canWrite): ?>
        <dialog id="modal-add-match" class="modal" aria-labelledby="modal-add-title">

            <div class="modal__header">
                <h2 id="modal-add-title" class="modal__title">Add New Match</h2>
                <button class="btn-modal-close" id="btn-modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal__body">

                <!-- MATCH INFO -->
                <section class="modal-section" aria-labelledby="section-match-info">
                    <h3 id="section-match-info" class="modal-section__title">Match Info</h3>

                    <div class="form-grid">
                        <!-- Team — locked for COACH, selectable for ADMIN -->
                        <?php if ($systemRole === 'ADMIN'): ?>
                            <div class="form-field">
                                <label class="form-field__label" for="match-team-id">Team</label>
                                <div class="custom-select">
                                    <input
                                            type="hidden"
                                            name="match-team-id"
                                            id="match-team-id"
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
                                                        htmlspecialchars($team->id),
                                                        htmlspecialchars($team->name)
                                                );
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="form-field">
                                <label class="form-field__label" for="match-team-display">Team</label>
                                <input
                                        type="text"
                                        id="match-team-display"
                                        value="<?php
                                        if (!empty($teamName)) {
                                            echo htmlspecialchars($teamName);
                                        }
                                        ?>"
                                        disabled
                                >
                            </div>
                        <?php endif; ?>

                        <div class="form-field">
                            <label class="form-field__label" for="match-opponent">Opponent Team</label>
                            <input type="text" id="match-opponent" name="opponent_name"
                                   placeholder="e.g. Natus Vincere" maxlength="255">
                        </div>

                        <div class="form-field">
                            <label class="form-field__label" for="match-team-score">Team Score</label>
                            <input type="number" id="match-team-score" name="team_score" min="0" max="99" placeholder="0">
                        </div>

                        <div class="form-field">
                            <label class="form-field__label" for="match-opponent-score">Opponent Score</label>
                            <input type="number" id="match-opponent-score" name="opponent_score" min="0" max="99" placeholder="0">
                        </div>

                        <div class="form-field">
                            <label class="form-field__label" for="match-map">Map</label>
                            <div class="custom-select">
                                <input
                                        type="hidden"
                                        name="map_id"
                                        id="match-map"
                                        required
                                >

                                <button type="button" class="custom-select__trigger" aria-expanded="false">
                                    Select map
                                    <span class="custom-select__arrow">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                </button>

                                <div class="custom-select__dropdown">
                                    <?php
                                    if (!empty($maps)) {
                                        foreach ($maps as $map) {
                                            printf(
                                                    '<button
                                                            type="button"
                                                            class="custom-select__option"
                                                            data-value="%s">%s</button>',
                                                    htmlspecialchars((int)$map['id']),
                                                    htmlspecialchars($map['ident'])
                                            );
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-field__label" for="match-game-mode">Game Mode</label>
                            <div class="custom-select">
                                <input
                                        type="hidden"
                                        name="game_mode_id"
                                        id="match-game-mode"
                                        required
                                >

                                <button type="button" class="custom-select__trigger" aria-expanded="false">
                                    Select mode
                                    <span class="custom-select__arrow">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                </button>

                                <div class="custom-select__dropdown">
                                    <?php
                                    if (!empty($gameModes)) {
                                        foreach ($gameModes as $mode) {
                                            printf(
                                                    '<button
                                                            type="button"
                                                            class="custom-select__option"
                                                            data-value="%s">%s</button>',
                                                    htmlspecialchars((int)$mode['id']),
                                                    htmlspecialchars($mode['ident'])
                                            );
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-field__label" for="match-duration">Duration (min)</label>
                            <input type="number" id="match-duration" name="duration" min="1" max="999" placeholder="45">
                        </div>

                        <div class="form-field">
                            <label class="form-field__label" for="match-played-at">Date &amp; Time</label>
                            <input type="datetime-local" id="match-played-at" name="played_at">
                        </div>
                    </div>
                </section>

                <!-- PLAYER STATS -->
                <section class="modal-section" aria-labelledby="section-player-stats">
                    <h3 id="section-player-stats" class="modal-section__title">Player Stats</h3>

                    <div class="stats-table-wrapper">
                        <table class="stats-table" id="stats-table" aria-label="Player statistics">
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
                            <tbody id="stats-tbody">
                            <tr>
                                <td class="empty-state" colspan="8">
                                    <?php if ($systemRole === 'ADMIN'): ?>
                                        Select a team to load players.
                                    <?php else: ?>
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                        Loading players...
                                    <?php endif; ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>

            <div class="modal__footer">
                <div id="modal-error" class="modal-error" hidden role="alert"></div>
                <div class="modal__actions">
                    <button class="btn-secondary" id="btn-cancel">
                        <span class="btn-secondary__label">Cancel</span>
                    </button>
                    <button class="btn-accent" id="btn-save-match">
                        <span class="btn-accent__label">Save Match</span>
                    </button>
                </div>
            </div>

        </dialog>
    <?php endif; ?>
</main>

<script type="module" src="/public/assets/js/helpers/custom-select.js"></script>
<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/matches.js"></script>
</body>
</html>