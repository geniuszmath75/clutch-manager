<?php
$nickname = $_SESSION['user']['nickname'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css" />
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
    <title>Admin Dashboard – Clutch Manager</title>
</head>
<body
        class="admin-dashboard"
        data-system-role="ADMIN"
        data-nickname="<?= htmlspecialchars($nickname, ENT_QUOTES) ?>"
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
            <h1 class="dashboard-header__nickname">
                <?= htmlspecialchars($nickname, ENT_QUOTES) ?>
            </h1>
        </div>
        <div class="dashboard-header__meta">
            <span class="dashboard-header__role">ADMIN</span>
        </div>
    </header>

    <!-- TEAM STATS SECTION -->
    <section class="dashboard-section" aria-label="Team statistics">
        <h2 class="dashboard-section__title">Team overview</h2>

        <div class="table-wrapper">
            <table class="data-table" id="teams-table">
                <thead>
                <tr>
                    <th scope="col">Team</th>
                    <th scope="col">Tag</th>
                    <th scope="col">Matches</th>
                    <th scope="col">Win rate</th>
                    <th scope="col">Team K/D</th>
                    <th scope="col">Avg kills / match</th>
                    <th scope="col">Avg dmg / match</th>
                </tr>
                </thead>
                <tbody id="teams-tbody">
                <!-- Populated by dashboard-admin.ts -->
                </tbody>
            </table>
        </div>

        <p class="table-empty" id="teams-empty" hidden>No team data available.</p>
        <p class="table-error" id="teams-error" hidden></p>

        <nav class="pagination" id="teams-pagination" aria-label="Teams pagination">
            <button class="pagination__btn" id="teams-btn-prev" aria-label="Previous page">PREV</button>
            <span class="pagination__info" id="teams-pagination-info"></span>
            <button class="pagination__btn" id="teams-btn-next" aria-label="Next page">NEXT</button>
        </nav>
    </section>

    <!-- AUDIT LOG SECTION -->
    <section class="dashboard-section" aria-label="Audit log">
        <h2 class="dashboard-section__title">Activity log</h2>

        <div class="table-wrapper">
            <table class="data-table" id="audit-table">
                <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Actor</th>
                    <th scope="col">Role</th>
                    <th scope="col">Team</th>
                    <th scope="col">Action</th>
                    <th scope="col">Entity</th>
                    <th scope="col">Entity ID</th>
                </tr>
                </thead>
                <tbody id="audit-tbody">
                <!-- Populated by dashboard-admin.ts -->
                </tbody>
            </table>
        </div>

        <p class="table-empty" id="audit-empty" hidden>No activity recorded yet.</p>
        <p class="table-error" id="audit-error" hidden></p>

        <nav class="pagination" id="audit-pagination" aria-label="Audit log pagination">
            <button class="pagination__btn" id="audit-btn-prev" aria-label="Previous page">PREV</button>
            <span class="pagination__info" id="audit-pagination-info"></span>
            <button class="pagination__btn" id="audit-btn-next" aria-label="Next page">NEXT</button>
        </nav>
    </section>

</main>

<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/dashboard-admin.js"></script>
</body>
</html>