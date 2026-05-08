<?php
$systemRole = $_SESSION['user']['system_role'] ?? '';
$canWrite = in_array($systemRole, ['COACH', 'ADMIN'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategies – Clutch Manager</title>
</head>
<body data-system-role="<?= htmlspecialchars($systemRole) ?>">

<nav>
    <a href="/dashboard">Dashboard</a>
    <a href="/dashboard/players">Players</a>
    <a href="/dashboard/matches">Matches</a>
    <a href="/dashboard/strategies" aria-current="page">Strategies</a>
    <form method="POST" action="/auth/logout" style="display:inline">
        <button type="submit">Log out</button>
    </form>
</nav>

<!-- MAIN -->
<main class="page-main">

    <!-- ── Title row ── -->
    <div class="section-header">
        <div class="section-header__left">
            <h1 class="section-header__title">Strategies</h1>
            <p class="section-header__sub" id="strategies-count">Loading…</p>
        </div>
        <?php if ($canWrite): ?>
            <button class="btn btn--primary" id="btn-open-add">+ Add Strategy</button>
        <?php endif; ?>
    </div>

    <!-- ── Type tabs ── -->
    <div class="strategy-tabs" role="tablist" aria-label="Filter by strategy type">
        <button class="strategy-tabs__tab is-active" role="tab" data-type="" aria-selected="true">
            All Strategies
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="1" aria-selected="false">
            Site Attack
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="2" aria-selected="false">
            Site Defense
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="3" aria-selected="false">
            Eco Round
        </button>
        <button class="strategy-tabs__tab" role="tab" data-type="4" aria-selected="false">
            Default Setup
        </button>
    </div>

    <!-- ── Strategy cards grid ── -->
    <div class="strategy-grid" id="strategy-grid">
        <!-- filled by strategies.ts -->
    </div>

    <!-- ── Empty state ── -->
    <p class="empty-state" id="empty-state" hidden>No strategies found.</p>

    <!-- ── Error state ── -->
    <p class="error-state" id="error-state" hidden></p>

    <!-- PAGINATION -->
    <nav id="pagination" aria-label="Strategies pagination" hidden>
        <button id="btn-prev" aria-label="Previous page">PREV</button>
        <span id="pagination-info"></span>
        <button id="btn-next" aria-label="Next page">NEXT</button>
    </nav>

</main>

<!-- ADD STRATEGY MODAL -->
<?php if ($canWrite): ?>
    <dialog class="modal" id="modal-add" aria-labelledby="modal-add-title">
        <div class="modal__header">
            <h2 class="modal__title" id="modal-add-title">Create new strategy</h2>
            <button class="modal__close" id="modal-add-close" aria-label="Close">&times;</button>
        </div>

        <div class="modal__body">

            <!-- Row 1: Name + Map -->
            <div class="form-row form-row--2col">
                <div class="form-field">
                    <label class="form-label" for="add-name">Strategy Name <span class="required">*</span></label>
                    <input class="form-input" type="text" id="add-name" placeholder="e.g. A-split Rush" maxlength="255">
                </div>
                <div class="form-field">
                    <label class="form-label" for="add-map">Map <span class="required">*</span></label>
                    <select class="form-select" id="add-map">
                        <option value="">Select map…</option>
                        <?php if (!empty($maps)) {
                            foreach ($maps as $map) {
                                printf(
                                        '<option value="%d">%s</option>',
                                        (int)$map['id'],
                                        htmlspecialchars(ucfirst(strtolower($map['ident'])))
                                );
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Strategy Type (segmented) -->
            <div class="form-field">
                <label class="form-label">Strategy Type <span class="required">*</span></label>
                <div class="type-selector" id="add-type-selector" role="group" aria-label="Strategy type">
                    <?php
                    $typeLabels = ['ATTACK' => 'Attack', 'DEFENSE' => 'Defense', 'ECO' => 'Eco', 'DEFAULT' => 'Default'];
                    if (!empty($strategyTypes)) {
                        foreach ($strategyTypes as $st) {
                            $label = $typeLabels[$st['ident']] ?? ucfirst(strtolower($st['ident']));
                            printf(
                                    '<button type="button"
                                        class="type-selector__option"
                                        data-type-id="%d"
                                        aria-pressed="false">
                                            %s
                                        </button>',
                                    (int)$st['id'],
                                    htmlspecialchars($label)
                            );
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Row 3: Assigned Players -->
            <div class="form-field">
                <label class="form-label">Assigned Players</label>
                <div class="player-tag-input" id="add-player-tags">
                    <div class="player-tag-input__tags" id="add-tags-list">
                        <!-- tags injected by TS -->
                    </div>
                    <div class="player-tag-input__dropdown-wrap">
                        <button type="button" class="btn btn--ghost btn--sm" id="btn-add-player-tag">+ Add player
                        </button>
                        <div class="player-tag-input__dropdown" id="add-player-dropdown" hidden>
                            <!-- player options injected by TS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Description -->
            <div class="form-field">
                <label class="form-label" for="add-description">Description <span class="required">*</span></label>
                <textarea class="form-textarea" id="add-description" rows="3"
                          placeholder="Briefly describe the goal of this strategy…"></textarea>
            </div>

            <!-- Row 5: Execution Steps -->
            <div class="form-field">
                <label class="form-label">Execution Steps</label>
                <div class="steps-input">
                    <div class="steps-input__add-row">
                        <input class="form-input" type="text" id="add-step-input" placeholder="Describe next step…">
                        <button type="button" class="btn btn--secondary" id="btn-add-step">Add</button>
                    </div>
                    <ol class="steps-list" id="add-steps-list">
                        <!-- steps injected by TS -->
                    </ol>
                </div>
            </div>

            <!-- Error -->
            <p class="form-error" id="add-error" hidden></p>

        </div>

        <div class="modal__footer">
            <button class="btn btn--ghost" id="btn-add-cancel">Cancel</button>
            <button class="btn btn--primary" id="btn-add-save">Save Strategy</button>
        </div>
    </dialog>
<?php endif; ?>

<script type="module" src="/public/assets/js/strategies.js"></script>
</body>
</html>