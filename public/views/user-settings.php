<?php

use Src\Enum\SystemRole;

$nickname   = $_SESSION['user']['nickname']    ?? '';
$email      = $_SESSION['user']['email']       ?? '';
$systemRole = $_SESSION['user']['system_role'] ?? '';
$teamId     = $_SESSION['user']['team_id']     ?? null;
$teamName   = $_SESSION['user']['team_name']   ?? null;
$teamRole   = $_SESSION['user']['team_role']   ?? null;
?>
    <!DOCTYPE html>
    <html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Settings – Clutch Manager</title>
</head>
<body
    data-system-role="<?= htmlspecialchars($systemRole, ENT_QUOTES) ?>"
    data-team-id="<?= $teamId !== null ? (int) $teamId : '' ?>"
    data-team-name="<?= htmlspecialchars($teamName ?? '', ENT_QUOTES) ?>"
>

<nav>
    <a href="/dashboard">Dashboard</a>
    <a href="/dashboard/players">Players</a>
    <a href="/dashboard/matches">Matches</a>
    <a href="/dashboard/strategies">Strategies</a>
    <a href="/dashboard/settings" aria-current="page">Settings</a>
    <form method="POST" action="/auth/logout" style="display:inline">
        <button type="submit">Log out</button>
    </form>
</nav>

<header class="settings-header">
    <h1 class="settings-header__title">User Settings</h1>
</header>

<main class="settings-main">

    <!-- ------------------------------------------------------------------ -->
    <!-- CARD: Update profile                                                 -->
    <!-- ------------------------------------------------------------------ -->
    <section class="settings-card" id="card-profile" aria-label="Update profile">
        <div class="settings-card__header">
            <h2 class="settings-card__title">Update profile</h2>
            <button
                class="btn btn--secondary settings-card__edit-btn"
                id="profile-edit-btn"
                type="button"
                aria-expanded="false"
                aria-controls="profile-form"
            >Edit</button>
        </div>

        <form class="settings-form" id="profile-form" novalidate>
            <div class="form-group">
                <label class="form-label" for="profile-nickname">Nickname</label>
                <input
                    class="form-input"
                    id="profile-nickname"
                    name="nickname"
                    type="text"
                    value="<?= htmlspecialchars($nickname, ENT_QUOTES) ?>"
                    minlength="2"
                    maxlength="50"
                    disabled
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="profile-email">Email</label>
                <input
                    class="form-input"
                    id="profile-email"
                    name="email"
                    type="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES) ?>"
                    maxlength="100"
                    disabled
                    autocomplete="email"
                >
            </div>

            <?php if ($systemRole === SystemRole::Player->value): ?>
                <div class="form-group">
                    <label class="form-label" for="profile-team-role">Team role</label>
                    <div class="custom-select">

                        <input
                                type="hidden"
                                name="team_role_ident"
                                id="profile-team-role"
                        >

                        <button type="button" class="custom-select__trigger" id="profile-team-role-btn" aria-expanded="false" disabled>
                            <?= htmlspecialchars($teamRole, ENT_QUOTES) ?>
                            <span class="custom-select__arrow">
                                    <img src="/public/assets/img/arrow-down.svg" alt="Arrow down icon" />
                                </span>
                        </button>

                        <div class="custom-select__dropdown">
                            <?php
                                if(!empty($teamRoles)) {
                                    foreach ($teamRoles as $role) {
                                        printf(
                                                '<button type="button" data-value="%s" class="custom-select__option">%s</button>',
                                                htmlspecialchars($role['ident']),
                                                htmlspecialchars($role['ident'])
                                        );
                                    }
                                }
                             ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <p class="form-error" id="profile-error" hidden></p>
            <p class="form-success" id="profile-success" hidden></p>

            <div class="settings-card__actions" id="profile-actions" hidden>
                <button class="btn btn--ghost" id="profile-cancel-btn" type="button">Cancel</button>
                <button class="btn btn--primary" id="profile-save-btn" type="submit">Save changes</button>
            </div>
        </form>
    </section>

    <!-- ------------------------------------------------------------------ -->
    <!-- CARD: Change password                                                -->
    <!-- ------------------------------------------------------------------ -->
    <section class="settings-card" id="card-password" aria-label="Change password">
        <div class="settings-card__header">
            <h2 class="settings-card__title">Change password</h2>
            <button
                class="btn btn--secondary settings-card__edit-btn"
                id="password-edit-btn"
                type="button"
                aria-expanded="false"
                aria-controls="password-form"
            >Edit</button>
        </div>

        <form class="settings-form" id="password-form" novalidate>
            <div class="form-group">
                <label class="form-label" for="current-password">Current password</label>
                <input
                    class="form-input"
                    id="current-password"
                    name="current_password"
                    type="password"
                    disabled
                    autocomplete="current-password"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="new-password">New password</label>
                <input
                    class="form-input"
                    id="new-password"
                    name="new_password"
                    type="password"
                    minlength="10"
                    disabled
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm-password">Confirm password</label>
                <input
                    class="form-input"
                    id="confirm-password"
                    name="confirm_password"
                    type="password"
                    minlength="10"
                    disabled
                    autocomplete="new-password"
                >
            </div>

            <p class="form-error" id="password-error" hidden></p>
            <p class="form-success" id="password-success" hidden></p>

            <div class="settings-card__actions" id="password-actions" hidden>
                <button class="btn btn--ghost" id="password-cancel-btn" type="button">Cancel</button>
                <button class="btn btn--primary" id="password-save-btn" type="submit">Save changes</button>
            </div>
        </form>
    </section>

    <!-- ------------------------------------------------------------------ -->
    <!-- CARD: Team management (PLAYER + COACH only)                         -->
    <!-- ------------------------------------------------------------------ -->
    <?php if ($systemRole !== 'ADMIN'): ?>
        <section class="settings-card" id="card-team" aria-label="Team management">
            <div class="settings-card__header">
                <h2 class="settings-card__title">Team management</h2>
            </div>

            <div class="settings-team">
                <?php if ($teamId !== 0): ?>
                    <p class="settings-team__info">
                        You are a member of
                        <strong class="settings-team__name" id="team-name-display">
                            <?= htmlspecialchars($teamName ?? 'your team', ENT_QUOTES) ?>
                        </strong>
                    </p>
                <?php else: ?>
                    <p class="settings-team__info settings-team__info--empty" id="team-info">No team assigned.</p>

                    <?php if ($systemRole === 'COACH'): ?>
                        <form class="settings-form settings-form--inline" id="create-team-form" novalidate>
                            <div class="form-group">
                                <label class="form-label" for="team-name">Team name</label>
                                <input
                                    class="form-input"
                                    id="team-name"
                                    name="name"
                                    type="text"
                                    minlength="2"
                                    maxlength="100"
                                    placeholder="Enter team name"
                                    autocomplete="off"
                                >
                            </div>

                            <p class="form-error" id="team-error" hidden></p>
                            <p class="form-success" id="team-success" hidden></p>

                            <div class="settings-card__actions">
                                <button class="btn btn--primary" id="create-team-btn" type="submit">
                                    Create team
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<script type="module" src="/public/assets/js/user-settings.js"></script>
<script>
    document
        .querySelectorAll(".custom-select")
        .forEach(select => {
            const trigger = select.querySelector(".custom-select__trigger");

            const hiddenInput = select.querySelector("input[type='hidden']")

            const options = select.querySelectorAll(".custom-select__option");

            trigger.addEventListener("click", () => {
                select.classList.toggle("custom-select--open");

                trigger.setAttribute("aria-expanded", select.classList.contains("custom-select--open"));
            });

            options.forEach(option => {
                option.addEventListener("click", () => {

                    const value = option.dataset.value;
                    const label = option.textContent;
                    hiddenInput.value = value;

                    trigger.firstChild.textContent = label;

                    select.classList.remove("custom-select--open");

                    trigger.setAttribute("aria-expanded", false);
                })
            })
        })
</script>
</body>
</html>
