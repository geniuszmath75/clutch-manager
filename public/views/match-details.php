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
</head>
<body
        data-match-id="<?= !empty($matchId) ? (int)$matchId : null ?>"
        data-system-role="<?= htmlspecialchars($systemRole) ?>"
        data-can-write="<?= $canWrite ? 'true' : 'false' ?>"
>

<div class="layout">
    <nav>
        <a href="/dashboard">Dashboard</a>
        <a href="/dashboard/players">Players</a>
        <a href="/dashboard/matches" aria-current="page">Matches</a>
        <form method="POST" action="/auth/logout" style="display:inline">
            <button type="submit">Log out</button>
        </form>
    </nav>
    <main class="main-content">

        <!-- Back link -->
        <a href="/dashboard/matches" class="back-link">&larr; Back to Matches</a>

        <!-- LOADING / ERROR states (TS will toggle these) -->
        <div id="details-loading" class="loading-state">Loading match...</div>
        <div id="details-error" class="alert alert--error" hidden></div>

        <!-- MATCH HEADER -->
        <div id="match-header" class="match-header" hidden>

            <div class="match-header__meta">
                <!-- WIN / LOSS / DRAW badge + date + game mode -->
                <span id="meta-result-badge" class="badge"></span>
                <span id="meta-played-at" class="match-header__date"></span>
                <span id="meta-game-mode" class="badge badge--secondary"></span>
            </div>

            <!-- Score -->
            <h1 id="meta-score" class="match-header__score"></h1>

            <!-- Map + duration badges -->
            <div class="match-header__tags">
                <span id="meta-map" class="badge badge--map"></span>
                <span id="meta-duration" class="badge badge--duration"></span>
            </div>

        </div>

        <!-- STATS TABLE -->
        <div id="stats-section" class="stats-section" hidden>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Player</th>
                        <th>K / D / A</th>
                        <th>Rating</th>
                        <th>+/-</th>
                        <th>ADR</th>
                        <th>KAST</th>
                        <th>HS %</th>
                        <th>Flash assists</th>
                    </tr>
                    </thead>
                    <tbody id="stats-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- ACTIONS (COACH / ADMIN only — rendered server-side) -->
        <?php if ($canWrite): ?>
            <div id="match-actions" class="match-actions" hidden>
                <button class="btn btn--secondary" id="btn-edit-match">Edit Match</button>
                <button class="btn btn--danger" id="btn-delete-match">Delete Match</button>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- MODAL — EDIT MATCH; TypeScript pre-fills the fields. -->
<?php if ($canWrite): ?>
    <dialog id="modal-edit-match" hidden>
        <div class="modal__overlay"></div>

        <div class="modal__dialog modal__dialog--wide">
            <div class="modal__header">
                <h2 class="modal__title">Edit Match</h2>
                <button class="modal__close" id="btn-edit-modal-close" aria-label="Close">&times;</button>
            </div>

            <div class="modal__body">

                <!-- ── MATCH INFO ── -->
                <section class="modal-section">
                    <h3 class="modal-section__title">Match info</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label" for="edit-opponent">Opponent Team</label>
                            <input type="text" class="input" id="edit-opponent" name="opponent_name" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label class="label" for="edit-team-score">Team Score</label>
                            <input type="number" class="input" id="edit-team-score" name="team_score" min="0" max="99">
                        </div>

                        <div class="form-group">
                            <label class="label" for="edit-opponent-score">Opponent Score</label>
                            <input type="number" class="input" id="edit-opponent-score" name="opponent_score" min="0"
                                   max="99">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label" for="edit-map">Map</label>
                            <select class="select" id="edit-map" name="map_id">
                                <!-- populated by TS after fetching /maps -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label" for="edit-duration">Duration (min)</label>
                            <input type="number" class="input" id="edit-duration" name="duration" min="1" max="999">
                        </div>

                        <div class="form-group">
                            <label class="label" for="edit-played-at">Date &amp; Time</label>
                            <input type="datetime-local" class="input" id="edit-played-at" name="played_at">
                        </div>

                        <div class="form-group">
                            <label class="label" for="edit-game-mode">Game Mode</label>
                            <select class="select" id="edit-game-mode" name="game_mode_id">
                                <!-- populated by TS -->
                            </select>
                        </div>
                    </div>
                </section>

                <!-- PLAYER STATS -->
                <section class="modal-section">
                    <h3 class="modal-section__title">Player Stats</h3>

                    <div class="table-wrapper">
                        <table class="table" id="edit-stats-table">
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
                            <tbody id="edit-stats-tbody"></tbody>
                        </table>
                    </div>
                </section>

            </div>

            <div class="modal__footer">
                <div id="edit-modal-error" class="alert alert--error" hidden></div>
                <div class="modal__actions">
                    <button class="btn btn--secondary" id="btn-edit-cancel">Cancel</button>
                    <button class="btn btn--primary" id="btn-update-match">Save Changes</button>
                </div>
            </div>
        </div>
    </dialog>

    <!-- CONFIRM DELETE DIALOG -->
    <dialog id="modal-confirm-delete" class="modal" hidden>
        <div class="modal__overlay"></div>
        <div class="modal__dialog">
            <div class="modal__header">
                <h2 class="modal__title">Delete Match</h2>
            </div>
            <div class="modal__body">
                <p>Are you sure you want to delete this match? This action cannot be undone.</p>
            </div>
            <div class="modal__footer">
                <div class="modal__actions">
                    <button class="btn btn--secondary" id="btn-delete-cancel">Cancel</button>
                    <button class="btn btn--danger" id="btn-delete-confirm">Delete</button>
                </div>
            </div>
        </div>
    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/match-details.js"></script>
</body>
</html>