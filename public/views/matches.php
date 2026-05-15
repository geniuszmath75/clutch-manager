<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$teamId = $_SESSION['user']['team_id'] ?? null;
$canWrite = in_array($systemRole, ['COACH', 'ADMIN'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matches</title>
</head>
<body
        data-system-role="<?= htmlspecialchars($systemRole) ?>"
        data-team-id="<?= htmlspecialchars((string)$teamId) ?>"
>

<div class="layout">
    <nav>
        <a href="/dashboard">Dashboard</a>
        <a href="/dashboard/players">Players</a>
        <a href="/dashboard/matches" aria-current="page">Matches</a>
        <a href="/dashboard/strategies">Strategies</a>
        <a href="/dashboard/settings">Settings</a>
        <form method="POST" action="/auth/logout" style="display:inline">
            <button type="submit">Log out</button>
        </form>
    </nav>
    <main class="main-content">
        <div class="page-header">
            <h1 id="page-title">Matches</h1>

            <?php if ($canWrite): ?>
                <button id="btn-add-match">
                    + Add Match
                </button>
            <?php endif; ?>
        </div>

        <!-- FILTERS -->
        <div class="filters">
            <input
                    type="text"
                    id="filter-opponent"
                    class="input"
                    placeholder="Search opponent..."
                    autocomplete="off"
            />

            <select id="filter-map" class="select">
                <option value="">All maps</option>
                <?php
                if (!empty($maps)) {
                    foreach ($maps as $map) {
                        printf(
                                '<option value="%s">%s</option>',
                                htmlspecialchars($map['ident']),
                                htmlspecialchars($map['ident'])
                        );
                    }
                }
                ?>
            </select>

            <select id="filter-result" class="select">
                <option value="">All results</option>
                <option value="WIN">WIN</option>
                <option value="LOSS">LOSS</option>
                <option value="DRAW">DRAW</option>
            </select>
        </div>

        <!-- ERROR BANNER -->
        <div id="error-banner" class="alert alert--error" hidden></div>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table class="table" id="matches-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Opponent</th>
                    <th>Map</th>
                    <th>Score</th>
                    <th>Result</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="matches-tbody">
                <tr>
                    <td colspan="7" class="table__empty">Loading...</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <nav id="pagination" aria-label="Matches pagination" hidden>
            <button id="btn-prev" aria-label="Previous page">PREV</button>
            <span id="pagination-info"></span>
            <button id="btn-next" aria-label="Next page">NEXT</button>
        </nav>
    </main>
</div>

<!-- MODAL — ADD MATCH
     Visible only for COACH / ADMIN (rendered conditionally server-side) -->
<?php if ($canWrite): ?>
    <dialog id="modal-add-match" hidden>
        <div id="modal-overlay"></div>

        <div class="modal__dialog modal__dialog--wide">
            <div class="modal__header">
                <h2 class="modal__title">Add New Match</h2>
                <button class="modal__close" id="btn-modal-close" aria-label="Close">&times;</button>
            </div>

            <div class="modal__body">

                <!-- MATCH INFO -->
                <section class="modal-section">
                    <h3 class="modal-section__title">Match info</h3>

                    <div class="form-row">
                        <!-- Team (locked for COACH, selectable for ADMIN) -->
                        <?php if ($systemRole === 'ADMIN'): ?>
                            <div class="form-group">
                                <label class="label" for="match-team-id">Team</label>
                                <select class="select" id="match-team-id" name="team_id">
                                    <option value="">— select team —</option>
                                    <?php
                                    if (!empty($teams)) {
                                        foreach ($teams as $team) {
                                            printf(
                                                    '<option value="%d">%s</option>',
                                                    htmlspecialchars($team->id),
                                                    htmlspecialchars($team->name)
                                            );
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label class="label" for="match-team-display">Team</label>
                                <input
                                        type="text"
                                        class="input"
                                        id="match-team-display"
                                        value="<?php
                                        if (!empty($teams)) {
                                            // Find own team name from injected list
                                            $ownTeam = array_filter($teams, fn($t) => (int)$t['id'] === (int)$teamId);
                                            $ownTeam = reset($ownTeam);
                                            echo htmlspecialchars($ownTeam['name'] ?? '');
                                        }
                                        ?>"
                                        disabled
                                >
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="label" for="match-opponent">Opponent Team</label>
                            <input type="text" class="input" id="match-opponent" name="opponent_name"
                                   placeholder="e.g. Natus Vincere" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label class="label" for="match-team-score">Team Score</label>
                            <input type="number" class="input" id="match-team-score" name="team_score" min="0" max="99">
                        </div>

                        <div class="form-group">
                            <label class="label" for="match-opponent-score">Opponent Score</label>
                            <input type="number" class="input" id="match-opponent-score" name="opponent_score" min="0"
                                   max="99">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label" for="match-map">Map</label>
                            <select class="select" id="match-map" name="map_id">
                                <option value="">— select map —</option>
                                <?php
                                if (!empty($maps)) {
                                    foreach ($maps as $map) {
                                        printf(
                                                '<option value="%d">%s</option>',
                                                htmlspecialchars((int)$map['id']),
                                                htmlspecialchars($map['ident'])
                                        );
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label" for="match-duration">Duration (min)</label>
                            <input type="number" class="input" id="match-duration" name="duration" min="1" max="999">
                        </div>

                        <div class="form-group">
                            <label class="label" for="match-played-at">Date &amp; Time</label>
                            <input type="datetime-local" class="input" id="match-played-at" name="played_at">
                        </div>

                        <div class="form-group">
                            <label class="label" for="match-game-mode">Game Mode</label>
                            <select class="select" id="match-game-mode" name="game_mode_id">
                                <option value="">— select mode —</option>
                                <?php
                                if (!empty($gameModes)) {
                                    foreach ($gameModes as $mode) {
                                        printf(
                                                '<option value="%d">%s</option>',
                                                htmlspecialchars((int)$mode['id']),
                                                htmlspecialchars($mode['ident'])
                                        );
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- PLAYER STATS -->
                <section class="modal-section">
                    <h3 class="modal-section__title">Player Stats</h3>

                    <div class="table-wrapper">
                        <table class="table" id="stats-table">
                            <thead>
                            <tr>
                                <th>Nickname</th>
                                <th>Kills</th>
                                <th>Deaths</th>
                                <th>Assists</th>
                                <th>Flash Assists</th>
                                <th>Damage</th>
                                <th>HS %</th>
                                <th>RKAST</th>
                            </tr>
                            </thead>
                            <tbody id="stats-tbody">
                            <tr>
                                <td colspan="8" class="table__empty">
                                    <?php if ($systemRole === 'ADMIN'): ?>
                                        Select a team to load players.
                                    <?php else: ?>
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
                <div id="modal-error" class="alert alert--error" hidden></div>
                <div class="modal__actions">
                    <button class="btn btn--secondary" id="btn-cancel">Cancel</button>
                    <button class="btn btn--primary" id="btn-save-match">Save Match</button>
                </div>
            </div>
        </div>
    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/matches.js"></script>
</body>
</html>