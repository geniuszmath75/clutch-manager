<?php $role = $_SESSION['user']['system_role'] ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" initial-scale=1>
    <title>Players</title>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">
<nav>
    <a href="/dashboard">Dashboard</a>
    <a href="/dashboard/players" aria-current="page">Players</a>
    <form method="POST" action="/auth/logout" style="display:inline">
        <button type="submit">Log out</button>
    </form>
</nav>

<main id="app">
    <header class="page-header">
        <h1 id="page-title">Players</h1>
        <div class="page-actions">
            <!-- Filtering by role -->
            <select id="role-filter" aria-label="Filter by role">
                <option value="">All roles</option>
                <option value="IGL">IGL</option>
                <option value="AWP">AWP</option>
                <option value="ENTRY">Entry Fragger</option>
                <option value="SUPPORT">Support</option>
                <option value="LURKER">Lurker</option>
            </select>
            <!-- Filtering by status -->
            <select id="status-filter" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="ACTIVE">Active</option>
                <option value="INACTIVE">Inactive</option>
            </select>
            <!-- Przycisk dodawania — widoczny tylko dla ADMIN/COACH (obsługa przez TS) -->
            <button id="btn-add-player" hidden>+ Add player to team</button>
        </div>
    </header>

    <!-- Stan ładowania -->
    <div id="players-loading" aria-live="polite">Players loading...</div>

    <!-- Stan błędu -->
    <div id="players-error" hidden role="alert"></div>

    <!-- Lista graczy -->
    <div id="players-list" hidden>
        <table>
            <thead>
            <tr>
                <th scope="col">Nickname</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col" class="actions-col">Actions</th>
            </tr>
            </thead>
            <tbody id="players-tbody">
            <!-- Wypełniany przez players.ts -->
            </tbody>
        </table>

        <!-- Paginacja — widoczna tylko gdy totalPages > 1 -->
        <nav id="pagination" aria-label="Player pagination" hidden>
            <button id="btn-prev" aria-label="Previous page">‹</button>
            <span id="pagination-info"></span>
            <button id="btn-next" aria-label="Next page">›</button>
        </nav>
    </div>

    <!-- Modal: edycja gracza (placeholder — wypełniany przez players.ts) -->
    <dialog id="modal-edit-player" aria-labelledby="modal-edit-title">
        <h2 id="modal-edit-title">Edit player</h2>
        <form id="form-edit-player" method="dialog">
            <label for="edit-nickname">Nickname</label>
            <input type="text" id="edit-nickname" name="nickname"
                   minlength="2" maxlength="32" required/>

            <label for="edit-team-role">Team role</label>
            <select id="edit-team-role" name="team_role_ident">
                <option value="">— brak —</option>
                <option value="IGL">IGL</option>
                <option value="AWP">AWPer</option>
                <option value="ENTRY">Entry Fragger</option>
                <option value="SUPPORT">Support</option>
                <option value="LURKER">Lurker</option>
            </select>

            <div class="modal-actions">
                <button type="submit" id="btn-save-player">Save</button>
                <button type="button" id="btn-cancel-edit">Cancel</button>
            </div>
            <p id="edit-error" role="alert" hidden></p>
        </form>
    </dialog>
</main>

<script src="/public/assets/js/players.js"></script>
</body>
</html>