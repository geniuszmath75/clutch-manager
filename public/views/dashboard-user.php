<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$nickname   = $_SESSION['user']['nickname']    ?? 'Player';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard – Clutch Manager</title>
</head>
<body
        data-system-role="<?= htmlspecialchars($systemRole, ENT_QUOTES) ?>"
        data-nickname="<?= htmlspecialchars($nickname, ENT_QUOTES) ?>"
>

<nav>
    <a href="/dashboard" aria-current="page">Dashboard</a>
    <a href="/dashboard/players">Players</a>
    <a href="/dashboard/matches">Matches</a>
    <a href="/dashboard/strategies">Strategies</a>
    <a href="/dashboard/settings">Settings</a>
    <form method="POST" action="/auth/logout" style="display:inline">
        <button type="submit">Log out</button>
    </form>
</nav>

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

<main class="dashboard-main">

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