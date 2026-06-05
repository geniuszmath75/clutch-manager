<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="icon" type="image/x-icon" href="/public/assets/img/logo.svg">
    <link rel="stylesheet" href="/public/assets/css/auth.css" />
    <script src="https://kit.fontawesome.com/b36ff3dc2a.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="auth-page">
    <div class="auth-page__background"></div>

    <div class="auth-page__blur auth-page__blur--top-left"></div>
    <div class="auth-page__blur auth-page__blur--bottom-right"></div>

    <div class="auth-layout">
        <div class="auth-header">
            <div class="auth-header__logo">
                <div class="logo">
                    <div class="logo__icon-wrapper">
                        <img src="/public/assets/img/logo.svg" alt="logo" class="logo__icon" />
                    </div>

                    <div class="logo__text">
                        <span class="logo__primary">Clutch</span>
                        <span class="logo__accent">Manager</span>
                    </div>
                </div>
            </div>

            <h1 class="auth-header__title">
                Register
            </h1>
        </div>


        <div class="auth-card">
            <form id="register-form" class="auth-form" novalidate>

                <div id="register-error" class="modal-error" hidden></div>

                <!-- NICKNAME -->
                <div class="form-field">
                    <label for="register-nickname" class="form-field__label">
                        Nickname
                    </label>

                    <div class="input-wrapper">
                        <div class="input-icon">
                            <i class="fa-regular fa-user"></i>
                        </div>

                        <input id="register-nickname" type="text" name="nickname" placeholder="s1mple" required autofocus>
                    </div>
                </div>

                <!-- EMAIL -->
                <div class="form-field">
                    <label for="register-email" class="form-field__label">
                        Email Address
                    </label>

                    <div class="input-wrapper">
                        <div class="input-icon">
                            <i class="fa-regular fa-envelope"></i>
                        </div>

                        <input id="register-email" type="email" name="email" placeholder="name@example.com" required>
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="form-field">
                    <label for="register-password" class="form-field__label">
                        Password
                    </label>

                    <div class="input-wrapper">
                        <div class="input-icon">
                            <i class="fa-solid fa-lock"></i>
                        </div>

                        <input id="register-password" type="password" name="password" placeholder="********" required autofocus>

                        <button type="button" class="input-action" id="register-toggle-password" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- SYSTEM ROLE -->
                <div class="form-field">
                    <label for="system_role_ident" class="form-field__label">
                        System Role
                    </label>

                    <div class="custom-select">

                        <input
                                type="hidden"
                                name="system_role_ident"
                                id="system_role_ident"
                                required
                        >

                        <button type="button" class="custom-select__trigger" aria-expanded="false">
                            Select system role
                            <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </button>

                        <div class="custom-select__dropdown">
                            <button
                                    type="button"
                                    class="custom-select__option"
                                    data-value="PLAYER"
                            >
                                Player
                            </button>
                            <button
                                    type="button"
                                    class="custom-select__option"
                                    data-value="COACH"
                            >
                                Coach
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TEAM ROLE -->
                <div class="form-field" id="team-role-field">
                    <label for="team_role_ident" class="form-field__label">
                        Team Role
                    </label>

                    <div class="custom-select">

                        <input
                                type="hidden"
                                name="team_role_ident"
                                id="team_role_ident"
                        >

                        <button type="button" class="custom-select__trigger" aria-expanded="false">
                            Select team role
                            <span class="custom-select__arrow">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </button>

                        <div class="custom-select__dropdown">
                            <?php if (!empty($teamRoles)) {
                                    foreach ($teamRoles as $role) {
                                        printf(
                                            '<button type="button" data-value="%s" class="custom-select__option">%s</button>',
                                            $role['ident'],
                                            $role['ident']
                                        );
                                    }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <button type="submit" id="register-submit" class="btn-accent">
                    <span class="btn-accent__label">Register</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-divider"></div>

            <div class="auth-footer">
                    <span class="auth-footer__text">
                        Already registered?
                    </span>

                <a href="/auth/login" class="auth-footer__link">
                    Login
                </a>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/public/assets/js/helpers/custom-select.js"></script>
<script type="module" src="/public/assets/js/auth.js"></script>
</body>
</html>