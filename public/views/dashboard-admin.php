<?php
$nickname = $_SESSION['user']['nickname'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard – Clutch Manager</title>
</head>
<body
        data-system-role="ADMIN"
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
        <h1 class="dashboard-header__nickname">
            <?= htmlspecialchars($nickname, ENT_QUOTES) ?>
        </h1>
    </div>
    <div class="dashboard-header__meta">
        <span class="dashboard-header__role">ADMIN</span>
    </div>
</header>

<main class="dashboard-main">

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

<script type="module" src="/public/assets/js/dashboard-admin.js"></script>
</body>
</html>