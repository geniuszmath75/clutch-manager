<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/assets/css/auth.css" />
    <title>Register</title>
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
            <form action="/auth/register" method="post" class="auth-form">

                <!-- NICKNAME -->
                <div class="form-field">
                    <label for="nickname" class="form-field__label">
                        Nickname
                    </label>

                    <div class="input-wrapper">
                        <div class="input-icon">
                            <img src="/public/assets/img/person.svg" alt="Person icon" />
                        </div>

                        <input id="nickname" type="text" name="nickname" placeholder="s1mple" required autofocus>
                    </div>
                </div>

                <!-- EMAIL -->
                <div class="form-field">
                    <label for="email" class="form-field__label">
                        Email Address
                    </label>

                    <div class="input-wrapper">
                        <div class="input-icon">
                            <img src="/public/assets/img/email.svg" alt="Email icon" />
                        </div>

                        <input id="email" type="email" name="email" placeholder="name@example.com" required>
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="form-field">
                    <label for="password" class="form-field__label">
                        Password
                    </label>

                    <div class="input-wrapper">
                        <div class="input-icon">
                            <img src="/public/assets/img/locked.svg" alt="Locked icon" />
                        </div>

                        <input id="password" type="password" name="password" placeholder="********" required autofocus>

                        <button type="button" class="input-action">
                            <img src="/public/assets/img/eye-open.svg" alt="Open eye icon" />
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
                                <img src="/public/assets/img/arrow-down.svg" alt="Arrow down icon" />
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
                <div class="form-field">
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
                                <img src="/public/assets/img/arrow-down.svg" alt="Arrow down icon" />
                            </span>
                        </button>

                        <div class="custom-select__dropdown">
                            <?php foreach ($teamRoles as $role): ?>
                                <button
                                        type="button"
                                        data-value="<?= htmlspecialchars($role['ident']) ?>"
                                        class="custom-select__option"
                                >
                                    <?= htmlspecialchars($role['ident']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-accent">
                    <span class="btn-accent__label">Register</span>
                    <img src="/public/assets/img/right-arrow.svg" alt="Right arrow icon" class="btn-accent__icon" />
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