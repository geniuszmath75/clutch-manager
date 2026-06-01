import { apiFetch } from './helpers/fetch-helpers.js';

export {};

/**
 * Types
 */

interface LoginPayload {
    email: string;
    password: string;
}

interface RegisterPayload {
    nickname: string;
    email: string;
    password: string;
    system_role_ident: string;
    team_role_ident: string | null;
}

/**
 * DOM Elements
 */

// Login
const loginForm         = document.getElementById('login-form')         as HTMLFormElement  | null;
const loginEmail        = document.getElementById('login-email')        as HTMLInputElement | null;
const loginPassword     = document.getElementById('login-password')     as HTMLInputElement | null;
const loginToggleBtn    = document.getElementById('login-toggle-password') as HTMLButtonElement | null;
const loginSubmitBtn    = document.getElementById('login-submit')       as HTMLButtonElement | null;
const loginError        = document.getElementById('login-error')        as HTMLDivElement   | null;
const loginSuccess      = document.getElementById('login-success')      as HTMLDivElement   | null;

// Register
const registerForm      = document.getElementById('register-form')      as HTMLFormElement  | null;
const registerNickname  = document.getElementById('register-nickname')  as HTMLInputElement | null;
const registerEmail     = document.getElementById('register-email')     as HTMLInputElement | null;
const registerPassword  = document.getElementById('register-password')  as HTMLInputElement | null;
const registerToggleBtn = document.getElementById('register-toggle-password') as HTMLButtonElement | null;
const registerSubmitBtn = document.getElementById('register-submit')    as HTMLButtonElement | null;
const registerError     = document.getElementById('register-error')     as HTMLDivElement   | null;
const systemRoleInput   = document.getElementById('system_role_ident')  as HTMLInputElement | null;
const teamRoleInput     = document.getElementById('team_role_ident')    as HTMLInputElement | null;
const teamRoleField     = document.getElementById('team-role-field')    as HTMLDivElement   | null;

/**
 * API
 */
async function loginUser(payload: LoginPayload): Promise<void> {
    const res = await apiFetch<null>('/auth/login', {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Login failed.');
    }
}

async function registerUser(payload: RegisterPayload): Promise<void> {
    const res = await apiFetch<null>('/auth/register', {
        method: 'POST',
        body: JSON.stringify(payload),
    });

    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Registration failed.');
    }
}

/**
 * Render
 */

function showError(container: HTMLDivElement, message: string): void {
    container.textContent = message;
    container.hidden = false;
}

function hideError(container: HTMLDivElement): void {
    container.textContent = '';
    container.hidden = true;
}

function showRegisteredFlash(): void {
    if (!loginSuccess) return;

    loginSuccess.textContent = 'Account created!';
    loginSuccess.hidden = false;
}

/**
 * Event listeners
 */

loginToggleBtn?.addEventListener('click', () => {
    if (loginPassword && loginToggleBtn) {
        togglePasswordVisibility(loginPassword, loginToggleBtn);
    }
});

loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!loginEmail || !loginPassword || !loginSubmitBtn || !loginError) return;

    hideError(loginError);
    loginSubmitBtn.disabled = true;

    try {
        await loginUser({
            email:    loginEmail.value.trim(),
            password: loginPassword.value,
        });

        window.location.href = '/dashboard';
    } catch (err) {
        showError(loginError, err instanceof Error ? err.message : 'Login failed.');
    } finally {
        loginSubmitBtn.disabled = false;
    }
});

registerToggleBtn?.addEventListener('click', () => {
    if (registerPassword && registerToggleBtn) {
        togglePasswordVisibility(registerPassword, registerToggleBtn);
    }
});

// React to custom-select changes on system_role_ident
systemRoleInput?.addEventListener('change', syncTeamRoleVisibility);

registerForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!registerNickname || !registerEmail || !registerPassword || !registerSubmitBtn || !registerError || !systemRoleInput) return;

    hideError(registerError);
    registerSubmitBtn.disabled = true;

    try {
        await registerUser({
            nickname:         registerNickname.value.trim(),
            email:            registerEmail.value.trim(),
            password:         registerPassword.value,
            system_role_ident: systemRoleInput.value,
            team_role_ident:  teamRoleInput?.value || null,
        });

        window.location.href = '/auth/login?registered=1';
    } catch (err) {
        showError(registerError, err instanceof Error ? err.message : 'Registration failed.');
    } finally {
        registerSubmitBtn.disabled = false;
    }
});

/**
 * UI helpers
 */

function togglePasswordVisibility(input: HTMLInputElement, btn: HTMLButtonElement): void {
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';

    const icon = btn.querySelector('i');
    if (icon) {
        icon.classList.toggle('fa-eye',        !isHidden);
        icon.classList.toggle('fa-eye-slash',   isHidden);
    }
}

/**
 * Show/hide the Team Role field based on selected system role.
 * COACH has no team role; PLAYER requires one.
 */
function syncTeamRoleVisibility(): void {
    if (!teamRoleField || !systemRoleInput) return;

    const isPlayer = systemRoleInput.value === 'PLAYER';
    teamRoleField.hidden = !isPlayer;

    // Clear the value when hiding so it doesn't get submitted
    if (!isPlayer && teamRoleInput) {
        teamRoleInput.value = '';
        // Reset custom-select trigger label
        const trigger = teamRoleField.querySelector<HTMLButtonElement>('.custom-select__trigger');
        if (trigger) {
            const arrow = trigger.querySelector('.custom-select__arrow');
            trigger.textContent = 'Select team role';
            if (arrow) trigger.appendChild(arrow);
        }
    }
}

/**
 * Init
 */

function init(): void {
    const params = new URLSearchParams(window.location.search);

    // Show flash only when arriving from /auth/register (referrer guard),
    // then immediately strip ?registered=1 from the URL so that a manual
    // page refresh or a copied link never re-triggers the message.
    if (loginForm && params.get('registered') === '1') {
        const fromRegister = document.referrer.includes('/auth/register');

        if (fromRegister) {
            showRegisteredFlash();
        }

        // Always consume the flag — keeps the URL clean regardless of referrer.
        params.delete('registered');
        const cleanUrl = params.toString()
            ? `${window.location.pathname}?${params.toString()}`
            : window.location.pathname;
        history.replaceState(null, '', cleanUrl);
    }

    // Set initial team role field visibility
    syncTeamRoleVisibility();
}

init();