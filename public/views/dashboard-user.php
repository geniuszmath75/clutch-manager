<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$nickname   = $_SESSION['user']['nickname']    ?? 'Player';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/public/assets/css/dashboard-user.css" />
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
    <title>Dashboard – Clutch Manager</title>
</head>
<body
        class="user-dashboard"
        data-system-role="<?= htmlspecialchars($systemRole, ENT_QUOTES) ?>"
        data-nickname="<?= htmlspecialchars($nickname, ENT_QUOTES) ?>"
>

<!-- SIDEBAR NAVIGATION -->
<nav class="sidebar-nav">
    <a href="/dashboard" class="sidebar-nav__logo">
        <div class="sidebar-nav__logo-icon">
            <img src="/public/assets/img/logo.svg" alt="Clutch Manager" />
        </div>
        <div class="sidebar-nav__logo-text">
            <span>Clutch</span>
            <span class="sidebar-nav__logo-accent">Manager</span>
        </div>
    </a>

    <ul class="sidebar-nav__links">
        <li>
            <a href="/dashboard" class="sidebar-nav__link is-active" aria-current="page">
                <span class="sidebar-nav__link-icon">
                    <i class="fa-solid fa-gauge"></i>
                </span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="/dashboard/players" class="sidebar-nav__link">
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
                <span class="sidebar-nav__logout-icon">
                    <i class="fa-solid fa-sign-out-alt"></i>
                </span>
                Log out
            </button>
        </form>
    </div>
</nav>

<main class="dashboard-main">
    <!-- HEADER -->
    <header class="dashboard-header">
        <div class="dashboard-header__greeting">
            <span class="dashboard-header__label">Welcome back,</span>
            <h1 class="dashboard-header__nickname" id="header-nickname">
                <?= htmlspecialchars($nickname, ENT_QUOTES) ?>
            </h1>
        </div>
        <div class="dashboard-header__meta">
            <span class="dashboard-header__role"><?= htmlspecialchars($systemRole, ENT_QUOTES) ?></span>
        </div>
    </header>

    <!-- STATS SECTION -->
    <section class="dashboard-section" aria-label="Player statistics">
        <h2 class="dashboard-section__title">Statistics</h2>

        <!-- Player/Coach tabs — each tab = one player; active tab shows their stat cards -->
        <div class="dashboard-tabs" id="player-tabs" role="tablist" aria-label="Select player" hidden>
            <!-- Populated by dashboard-user.ts -->
        </div>

        <!-- Stat cards grid — populated by dashboard-user.ts -->
        <div class="stats-grid" id="stats-grid" aria-live="polite">
            <p class="stats-grid__empty" id="stats-empty" hidden>No statistics available yet.</p>
        </div>
    </section>

    <!-- RECENT MATCHES SECTION -->
    <section class="dashboard-section" aria-label="Recent match history">
        <h2 class="dashboard-section__title">Recent match history</h2>

        <div class="table-wrapper">
            <table class="data-table" id="recent-matches-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Map</th>
                        <th scope="col">Result</th>
                        <th scope="col">Score</th>
                    </tr>
                </thead>
                <tbody id="recent-matches-tbody">
                <!-- Populated by dashboard-user.ts -->
                </tbody>
            </table>
        </div>

        <p class="table-empty" id="recent-matches-empty" hidden>No matches played yet.</p>
        <p class="table-error" id="recent-matches-error" hidden></p>
    </section>

</main>

<script type="module" src="/public/assets/js/dashboard-user.js"></script>
</body>
</html>