<?php

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
    <title>User Settings</title>
    <link rel="icon" type="image/x-icon" href="/public/assets/img/logo.svg">
    <link rel="stylesheet" type="text/css" href="/public/assets/css/user-settings.css">
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
</head>
<body class="settings-page"
    data-system-role="<?= htmlspecialchars($systemRole, ENT_QUOTES) ?>"
    data-team-id="<?= $teamId !== null ? (int) $teamId : '' ?>"
    data-team-name="<?= htmlspecialchars($teamName ?? '', ENT_QUOTES) ?>"
>

<!-- MOBILE TOPBAR -->
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

<!-- OVERLAY -->
<div class="sidebar-nav__overlay" id="sidebar-overlay" aria-hidden="true"></div>

<!-- SIDEBAR NAVIGATION -->
<nav class="sidebar-nav" id="sidebar-nav" aria-label="Main navigation">
    <a href="/dashboard" class="sidebar-nav__logo">
        <div class="sidebar-nav__logo-icon">
            <img src="/public/assets/img/logo.svg" alt="Clutch Manager">
        </div>
        <div class="sidebar-nav__logo-text">
            <span>Clutch</span>
            <span class="sidebar-nav__logo-accent">Manager</span>
        </div>
    </a>

    <ul class="sidebar-nav__links" role="list">
        <li>
            <a href="/dashboard" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-gauge"></i></span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="/dashboard/players" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-users"></i></span>
                Players
            </a>
        </li>
        <li>
            <a href="/dashboard/matches" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-trophy"></i></span>
                Matches
            </a>
        </li>
        <li>
            <a href="/dashboard/strategies" class="sidebar-nav__link">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-chess"></i></span>
                Strategies
            </a>
        </li>
        <li>
            <a href="/dashboard/settings" class="sidebar-nav__link is-active" aria-current="page">
                <span class="sidebar-nav__link-icon"><i class="fa-solid fa-gear"></i></span>
                Settings
            </a>
        </li>
    </ul>

    <div class="sidebar-nav__footer">
        <form method="POST" action="/auth/logout">
            <button type="submit" class="sidebar-nav__logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                Log out
            </button>
        </form>
    </div>
</nav>

<!-- MAIN -->
<div class="settings-page__content">

    <header class="settings-header">
        <h1 class="settings-header__title">User Settings</h1>
        <p class="settings-header__subtitle">Manage your identity and team permissions.</p>
    </header>


    <main class="settings-main">

        <!-- CARD: Update profile -->
        <section class="settings-card" id="card-profile" aria-label="Update profile">
            <div class="settings-card__header">
                <h2 class="settings-card__title">Update Profile</h2>
                <button class="btn-secondary settings-card__edit-btn"
                        id="profile-edit-btn" type="button"
                        aria-expanded="false" aria-controls="profile-form">
                    Edit
                </button>
            </div>

            <form class="settings-form" id="profile-form" novalidate>

                <!-- Nickname + Email side by side -->
                <div class="settings-form__row">
                    <div class="form-field">
                        <label class="form-field__label" for="profile-nickname">Nickname</label>
                        <input id="profile-nickname" name="nickname" type="text"
                               value="<?= htmlspecialchars($nickname, ENT_QUOTES) ?>"
                               minlength="2" maxlength="50" disabled autocomplete="username">
                    </div>

                    <div class="form-field">
                        <label class="form-field__label" for="profile-email">Email Address</label>
                        <input id="profile-email" name="email" type="email"
                               value="<?= htmlspecialchars($email, ENT_QUOTES) ?>"
                               maxlength="100" disabled autocomplete="email">
                    </div>
                </div>

                <?php if ($systemRole === 'PLAYER'): ?>
                    <div class="form-field">
                        <label class="form-field__label">Team Role</label>
                        <div class="custom-select">
                            <input type="hidden" name="team_role_ident" id="profile-team-role"
                                   value="<?= htmlspecialchars($teamRole ?? '', ENT_QUOTES) ?>">
                            <button type="button" class="custom-select__trigger"
                                    id="profile-team-role-btn" aria-expanded="false" disabled>
                                <?= htmlspecialchars($teamRole ?? 'Select role', ENT_QUOTES) ?>
                                <span class="custom-select__arrow">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </span>
                            </button>
                            <div class="custom-select__dropdown">
                                <?php
                                if (!empty($teamRoles)) {
                                    foreach ($teamRoles as $role) {
                                        printf(
                                                '<button type="button" class="custom-select__option" data-value="%s">%s</button>',
                                                htmlspecialchars($role['ident'], ENT_QUOTES),
                                                htmlspecialchars($role['ident'], ENT_QUOTES)
                                        );
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <p class="form-error" id="profile-error" hidden role="alert"></p>
                <p class="form-success" id="profile-success" hidden role="status"></p>

                <div class="settings-card__actions settings-card__actions--hidden" id="profile-actions" hidden>
                    <button class="btn-secondary" id="profile-cancel-btn" type="button">
                        <span class="btn-secondary__label">Cancel</span>
                    </button>
                    <button class="btn-accent" id="profile-save-btn" type="submit">
                        <i class="fa-solid fa-floppy-disk btn-accent__icon"></i>
                        <span class="btn-accent__label">Save Changes</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- CARD: Change password -->
        <section class="settings-card" id="card-password" aria-label="Change password">
            <div class="settings-card__header">
                <h2 class="settings-card__title">Change Password</h2>
                <button class="btn-secondary settings-card__edit-btn"
                        id="password-edit-btn" type="button"
                        aria-expanded="false" aria-controls="password-form">
                    Edit
                </button>
            </div>

            <form class="settings-form" id="password-form" novalidate>

                <div class="form-field">
                    <label class="form-field__label" for="current-password">Current Password</label>
                    <input id="current-password" name="current_password" type="password"
                           disabled autocomplete="current-password">
                </div>

                <div class="settings-form__row">
                    <div class="form-field">
                        <label class="form-field__label" for="new-password">New Password</label>
                        <input id="new-password" name="new_password" type="password"
                               minlength="10" disabled autocomplete="new-password">
                    </div>

                    <div class="form-field">
                        <label class="form-field__label" for="confirm-password">Confirm Password</label>
                        <input id="confirm-password" name="confirm_password" type="password"
                               minlength="10" disabled autocomplete="new-password">
                    </div>
                </div>

                <p class="form-error" id="password-error" hidden role="alert"></p>
                <p class="form-success" id="password-success" hidden role="status"></p>

                <div class="settings-card__actions settings-card__actions--hidden" id="password-actions" hidden>
                    <button class="btn-secondary" id="password-cancel-btn" type="button">
                        <span class="btn-secondary__label">Cancel</span>
                    </button>
                    <button class="btn-accent" id="password-save-btn" type="submit">
                        <i class="fa-solid fa-floppy-disk btn-accent__icon"></i>
                        <span class="btn-accent__label">Save Changes</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- CARD: Team management (PLAYER + COACH only) -->
        <?php if ($systemRole !== 'ADMIN'): ?>
            <section class="settings-card" id="card-team" aria-label="Team management">
                <div class="settings-card__header">
                    <h2 class="settings-card__title">Team Management</h2>
                </div>

                <div class="settings-team">
                    <?php if (!empty($teamId)): ?>
                        <p class="settings-team__info">
                            You are a member of
                            <strong class="settings-team__name" id="team-name-display">
                                <?= htmlspecialchars($teamName ?? 'your team', ENT_QUOTES) ?>
                            </strong>
                        </p>
                    <?php else: ?>
                        <p class="settings-team__info settings-team__info--empty" id="team-info">
                            No team assigned.
                        </p>

                        <?php if ($systemRole === 'COACH'): ?>
                            <form class="settings-form settings-form--inline"
                                  id="create-team-form" novalidate>
                                <div class="form-field">
                                    <label class="form-field__label" for="team-name">Team Name</label>
                                    <input id="team-name" name="name" type="text"
                                           minlength="2" maxlength="100"
                                           placeholder="Enter team name" autocomplete="off">
                                </div>

                                <p class="form-error" id="team-error" hidden role="alert"></p>
                                <p class="form-success" id="team-success" hidden role="status"></p>

                                <div class="settings-card__actions">
                                    <button class="btn-accent" id="create-team-btn" type="submit">
                                        <i class="fa-solid fa-people-group btn-accent__icon"></i>
                                        <span class="btn-accent__label">Create Team</span>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>
</div>

<script type="module" src="/public/assets/js/helpers/sidebar-nav.js"></script>
<script type="module" src="/public/assets/js/helpers/custom-select.js"></script>
<script type="module" src="/public/assets/js/user-settings.js"></script>
</body>
</html>
