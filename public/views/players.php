<?php
$role = $_SESSION['user']['system_role'] ?? '';
$teamId = $_SESSION['user']['team_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" initial-scale=1>
    <title>Players</title>
</head>
<body data-role="<?= htmlspecialchars($role) ?>"
      data-team-id="<?= !empty($teamId) ? htmlspecialchars((string)$teamId) : '' ?>">
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
            <!-- Visible for ADMIN and COACH only (controlled by players.ts) -->
            <button id="btn-add-player" hidden>+ Add player to team</button>
        </div>
    </header>

    <div id="players-loading" aria-live="polite">Players loading...</div>
    <div id="players-error" hidden role="alert"></div>

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
            <tbody id="players-tbody"></tbody>
        </table>

        <nav id="pagination" aria-label="Player pagination" hidden>
            <button id="btn-prev" aria-label="Previous page">PREV</button>
            <span id="pagination-info"></span>
            <button id="btn-next" aria-label="Next page">NEXT</button>
        </nav>
    </div>

    <!-- Modal: edit player (ADMIN only) -->
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

    <dialog id="modal-add-player" aria-labelledby="modal-edit-title">
        <h2 id="modal-add-title">Add player to team</h2>

        <label for="add-player-select">Player</label>
        <select id="add-player-select">
            <option value="">Loading...</option>
        </select>

        <!-- Team selector — visible for ADMIN only; COACH uses their own team_id -->
        <div id="team-select-wrapper">
            <label for="add-team-select">Team</label>
            <select id="add-team-select">
                <?php
                if (!empty($teams)) {
                    foreach ($teams as $team) {
                        printf(
                                '<option value="%d">%s</option>',
                                htmlspecialchars((string)$team->id),
                                htmlspecialchars($team->name)
                        );
                    }
                }
                ?>
            </select>
        </div>

        <div class="modal-actions">
            <button type="button" id="btn-confirm-add">Add to team</button>
            <button type="button" id="btn-cancel-add">Cancel</button>
        </div>
        <p id="add-error" role="alert" hidden></p>
    </dialog>
</main>

<script src="/public/assets/js/players.js"></script>
</body>
</html>