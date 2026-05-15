import { apiFetch } from './helpers/fetch-helpers.js';
import { escapeHtml } from './helpers/string-helpers.js';

export {}

/**
 * Types
 */
interface ProfileResponse {
    id:       number;
    nickname: string;
    email:    string;
    teamRole: string;
}

interface TeamResponse {
    id:   number;
    name: string;
    tag:  string;
}

/**
 * State
 */
const body       = document.body;
const systemRole = body.dataset['systemRole'] ?? '';
const isCoach    = systemRole === 'COACH';

// Snapshot of original input values — used by Cancel to restore state
let profileSnapshot = { nickname: '', email: '', teamRole: '' };

/**
 * DOM elements
 */
// Profile card
const profileEditBtn    = document.getElementById('profile-edit-btn')!    as HTMLButtonElement;
const profileCancelBtn  = document.getElementById('profile-cancel-btn')!  as HTMLButtonElement;
const profileSaveBtn    = document.getElementById('profile-save-btn')!    as HTMLButtonElement;
const profileActionsEl  = document.getElementById('profile-actions')!     as HTMLElement;
const profileForm       = document.getElementById('profile-form')!        as HTMLFormElement;
const profileNickname   = document.getElementById('profile-nickname')!    as HTMLInputElement;
const profileEmail      = document.getElementById('profile-email')!       as HTMLInputElement;
const profileTeamRole   = document.getElementById('profile-team-role')!   as HTMLInputElement;
const profileTeamRoleBtn = document.getElementById('profile-team-role-btn')! as HTMLButtonElement;
const profileError      = document.getElementById('profile-error')!       as HTMLElement;
const profileSuccess    = document.getElementById('profile-success')!     as HTMLElement;

// Password card
const passwordEditBtn   = document.getElementById('password-edit-btn')!   as HTMLButtonElement;
const passwordCancelBtn = document.getElementById('password-cancel-btn')! as HTMLButtonElement;
const passwordSaveBtn   = document.getElementById('password-save-btn')!   as HTMLButtonElement;
const passwordActionsEl = document.getElementById('password-actions')!    as HTMLElement;
const passwordForm      = document.getElementById('password-form')!       as HTMLFormElement;
const currentPassword   = document.getElementById('current-password')!   as HTMLInputElement;
const newPassword       = document.getElementById('new-password')!        as HTMLInputElement;
const confirmPassword   = document.getElementById('confirm-password')!    as HTMLInputElement;
const passwordError     = document.getElementById('password-error')!      as HTMLElement;
const passwordSuccess   = document.getElementById('password-success')!    as HTMLElement;

// Team card
const createTeamForm    = document.getElementById('create-team-form')     as HTMLFormElement | null;
const createTeamBtn     = document.getElementById('create-team-btn')      as HTMLButtonElement | null;
const teamNameInput     = document.getElementById('team-name')            as HTMLInputElement | null;
const teamError         = document.getElementById('team-error')           as HTMLElement | null;
const teamSuccess       = document.getElementById('team-success')         as HTMLElement | null;
const teamInfo          = document.getElementById('team-info')    as HTMLElement | null;

/**
 * API - update user profile
 */
async function saveProfile(): Promise<void> {
    profileSaveBtn.disabled = true;
    hideMessage(profileError);
    hideMessage(profileSuccess);

    const nickname = profileNickname.value.trim();
    const email    = profileEmail.value.trim();
    const teamRole = profileTeamRole.value.trim();

    // Send only changed fields
    const payload: Record<string, string> = {};
    if (nickname !== profileSnapshot.nickname) payload['nickname'] = nickname;
    if (email    !== profileSnapshot.email)    payload['email']    = email;
    if (teamRole !== profileSnapshot.teamRole) payload['team_role_ident'] = teamRole;

    if (Object.keys(payload).length === 0) {
        setEditMode('profile', false);
        return;
    }

    try {
        const res = await apiFetch<ProfileResponse>('/users/me', {
            method: 'PATCH',
            body:   JSON.stringify(payload),
        });

        if (!res.success || !res.data) {
            showMessage(profileError, res.errorMessage ?? 'Failed to update profile.');
            return;
        }

        profileSnapshot = { nickname: res.data.nickname, email: res.data.email, teamRole: res.data.teamRole };
        profileNickname.value = res.data.nickname;
        profileEmail.value    = res.data.email;
        profileTeamRole.value = res.data.teamRole;

        setEditMode('profile', false);
        showMessage(profileSuccess, 'Profile updated successfully.');
    } finally {
        profileSaveBtn.disabled = false;
    }
}

/**
 * API - update user password
 */
async function savePassword(): Promise<void> {
    passwordSaveBtn.disabled = true;
    hideMessage(passwordError);
    hideMessage(passwordSuccess);

    const payload = {
        current_password: currentPassword.value,
        new_password:     newPassword.value,
        confirm_password: confirmPassword.value,
    };

    try {
        const res = await apiFetch<never>('/users/me/password', {
            method: 'PATCH',
            body:   JSON.stringify(payload),
        });

        if (!res.success) {
            showMessage(passwordError, res.errorMessage ?? 'Failed to change password.');
            return;
        }

        setEditMode('password', false);
        clearPasswordFields();
        showMessage(passwordSuccess, 'Password changed successfully.');
    } finally {
        passwordSaveBtn.disabled = false;
    }
}

/**
 * API - create team
 */
async function createTeam(): Promise<void> {
    if (!createTeamBtn || !teamNameInput || !teamError || !teamSuccess) return;

    createTeamBtn.disabled = true;
    hideMessage(teamError);
    hideMessage(teamSuccess);

    const name = teamNameInput.value.trim();

    if (name.length < 2) {
        showMessage(teamError, 'Team name must be at least 2 characters.');
        createTeamBtn.disabled = false;
        return;
    }

    try {
        const res = await apiFetch<TeamResponse>('/teams', {
            method: 'POST',
            body:   JSON.stringify({ name }),
        });

        if (!res.success || !res.data) {
            showMessage(teamError, res.errorMessage ?? 'Failed to create team.');
            createTeamBtn.disabled = false;
            return;
        }

        // Replace create-team form with team name display — no page reload needed
        showMessage(teamSuccess, `Team "${escapeHtml(res.data.name)}" created successfully.`);

        if (teamInfo) teamInfo.hidden = true;
        createTeamForm?.replaceWith(buildTeamDisplay(res.data.name));
    } finally {
        createTeamBtn.disabled = false;
    }
}

/**
 * Render
 */

/**
 * Replaces the create-team form with a static team name paragraph after creation.
 */
function buildTeamDisplay(name: string): HTMLParagraphElement {
    const p = document.createElement('p');
    p.className = 'settings-team__info';
    p.innerHTML = `You are a member of <strong class="settings-team__name">${escapeHtml(name)}</strong>`;
    return p;
}

/**
 * Edit mode toggle
 */
/**
 * Activates or deactivates edit mode for a given card ('profile' | 'password').
 */
function setEditMode(card: 'profile' | 'password', active: boolean): void {
    if (card === 'profile') {
        profileNickname.disabled  = !active;
        profileEmail.disabled     = !active;
        profileTeamRoleBtn.disabled  = !active;
        profileActionsEl.hidden   = !active;
        profileEditBtn.hidden     = active;
        profileEditBtn.setAttribute('aria-expanded', String(active));

        if (active) {
            profileSnapshot = {
                nickname: profileNickname.value,
                email:    profileEmail.value,
                teamRole: profileTeamRole.value
            };
            profileNickname.focus();
        }
    } else {
        currentPassword.disabled  = !active;
        newPassword.disabled      = !active;
        confirmPassword.disabled  = !active;
        passwordActionsEl.hidden  = !active;
        passwordEditBtn.hidden    = active;
        passwordEditBtn.setAttribute('aria-expanded', String(active));

        if (active) currentPassword.focus();
    }
}

function clearPasswordFields(): void {
    currentPassword.value = '';
    newPassword.value     = '';
    confirmPassword.value = '';
}

/**
 * Event listeners
 */

// Profile card
profileEditBtn.addEventListener('click', () => {
    hideMessage(profileError);
    hideMessage(profileSuccess);
    setEditMode('profile', true);
});

profileCancelBtn.addEventListener('click', () => {
    profileNickname.value = profileSnapshot.nickname;
    profileEmail.value    = profileSnapshot.email;
    profileTeamRole.value = profileSnapshot.teamRole;
    hideMessage(profileError);
    setEditMode('profile', false);
});

profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    await saveProfile();
});

// Password card
passwordEditBtn.addEventListener('click', () => {
    hideMessage(passwordError);
    hideMessage(passwordSuccess);
    setEditMode('password', true);
});

passwordCancelBtn.addEventListener('click', () => {
    clearPasswordFields();
    hideMessage(passwordError);
    setEditMode('password', false);
});

passwordForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    await savePassword();
});

// Team card (COACH without team only)
if (isCoach && createTeamForm && createTeamBtn) {
    createTeamForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await createTeam();
    });
}

/**
 * UI helpers
 */
function showMessage(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden      = false;
}

function hideMessage(el: HTMLElement): void {
    el.textContent = '';
    el.hidden      = true;
}

/**
 * Init
 */
// Capture initial profile values as the baseline snapshot
profileSnapshot = {
    nickname: profileNickname.value,
    email:    profileEmail.value,
    teamRole: profileTeamRole.value
};