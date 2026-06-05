import { apiFetch } from './helpers/fetch-helpers.js';
import { escapeHtml } from './helpers/string-helpers.js';
import { initCustomSelects } from './helpers/custom-select.js';

export {}

/**
 * Types
 */
interface ProfileResponse {
    id:       number;
    nickname: string;
    email:    string;
    teamRole: string | null;
}

type ProfileSnapshot = Omit<ProfileResponse, "id">;

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
let profileSnapshot: ProfileSnapshot = { nickname: '', email: '', teamRole: null };

/**
 * DOM elements
 */
// Profile card
const profileEditBtn   = document.getElementById('profile-edit-btn')!   as HTMLButtonElement;
const profileCancelBtn = document.getElementById('profile-cancel-btn')  as HTMLButtonElement | null;
const profileSaveBtn   = document.getElementById('profile-save-btn')!   as HTMLButtonElement;
const profileActionsEl = document.getElementById('profile-actions')!;
const profileForm      = document.getElementById('profile-form')!       as HTMLFormElement;
const profileNickname  = document.getElementById('profile-nickname')!   as HTMLInputElement;
const profileEmail     = document.getElementById('profile-email')!      as HTMLInputElement;
const profileError     = document.getElementById('profile-error')!;
const profileSuccess   = document.getElementById('profile-success')!;

// Password card
const passwordEditBtn   = document.getElementById('password-edit-btn')!   as HTMLButtonElement;
const passwordCancelBtn = document.getElementById('password-cancel-btn')! as HTMLButtonElement;
const passwordSaveBtn   = document.getElementById('password-save-btn')!   as HTMLButtonElement;
const passwordActionsEl = document.getElementById('password-actions')!;
const passwordForm      = document.getElementById('password-form')!       as HTMLFormElement;
const currentPassword   = document.getElementById('current-password')!   as HTMLInputElement;
const newPassword       = document.getElementById('new-password')!        as HTMLInputElement;
const confirmPassword   = document.getElementById('confirm-password')!    as HTMLInputElement;
const passwordError     = document.getElementById('password-error')!;
const passwordSuccess   = document.getElementById('password-success')!;

// Team card
const createTeamForm = document.getElementById('create-team-form') as HTMLFormElement   | null;
const createTeamBtn  = document.getElementById('create-team-btn')  as HTMLButtonElement | null;
const teamNameInput  = document.getElementById('team-name')        as HTMLInputElement  | null;
const teamError      = document.getElementById('team-error')       as HTMLElement       | null;
const teamSuccess    = document.getElementById('team-success')     as HTMLElement       | null;
const teamInfo       = document.getElementById('team-info')        as HTMLElement       | null;

// TEAM ROLE FIELD OBJECT
// Encapsulates nullability of the team-role select — present only for PLAYER.
// All access goes through this object; no scattered `if (profileTeamRole)` guards.
const teamRoleField = {
    el: document.getElementById('profile-team-role')   as HTMLInputElement | null,
    btn: document.getElementById('profile-team-role-btn') as HTMLButtonElement | null,

    getValue(): string | null {
        return this.el ? this.el.value.trim() : null;
    },

    setValue(val: string | null): void {
        if (!this.el || !this.btn) return;
        this.el.value = val ?? '';
        // Update visible trigger label (first text node of button)
        if (this.btn.firstChild) {
            this.btn.firstChild.textContent = val ?? 'Select role';
        }
    },

    setDisabled(disabled: boolean): void {
        if (this.el)  this.el.disabled  = disabled;
        if (this.btn) this.btn.disabled = disabled;
    },
}

/**
 * API - update user profile
 */
async function saveProfile(): Promise<void> {
    profileSaveBtn.disabled = true;
    hideMessage(profileError);
    hideMessage(profileSuccess);

    const nickname = profileNickname.value.trim();
    const email    = profileEmail.value.trim();
    const teamRole = teamRoleField.getValue();

    // Send only changed fields
    const payload: Record<string, string> = {};
    if (nickname !== profileSnapshot.nickname) payload['nickname'] = nickname;
    if (email    !== profileSnapshot.email)    payload['email']    = email;
    if (teamRole && teamRole !== profileSnapshot.teamRole) payload['team_role_ident'] = teamRole;

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

        profileSnapshot = {
            nickname: res.data.nickname,
            email: res.data.email,
            teamRole: res.data.teamRole ?? null
        };

        profileNickname.value = res.data.nickname;
        profileEmail.value    = res.data.email;
        teamRoleField.setValue(res.data.teamRole ?? null);

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
        teamRoleField.setDisabled(!active);
        profileActionsEl.hidden   = !active;
        profileEditBtn.hidden     = active;
        profileEditBtn.setAttribute('aria-expanded', String(active));

        profileActionsEl.classList.add("settings-card__actions--hidden");
        profileEditBtn.classList.remove("settings-card__edit-btn--hidden");

        if (active) {
            profileSnapshot = {
                nickname: profileNickname.value,
                email:    profileEmail.value,
                teamRole: teamRoleField.getValue()
            };
            profileNickname.focus();
            profileActionsEl.classList.remove("settings-card__actions--hidden");
            profileEditBtn.classList.add("settings-card__edit-btn--hidden");
        }
    } else {
        currentPassword.disabled  = !active;
        newPassword.disabled      = !active;
        confirmPassword.disabled  = !active;
        passwordActionsEl.hidden  = !active;
        passwordEditBtn.hidden    = active;
        passwordEditBtn.setAttribute('aria-expanded', String(active));

        passwordActionsEl.classList.add("settings-card__actions--hidden");
        passwordEditBtn.classList.remove("settings-card__edit-btn--hidden");

        if (active) {
            currentPassword.focus();
            passwordActionsEl.classList.remove("settings-card__actions--hidden");
            passwordEditBtn.classList.add("settings-card__edit-btn--hidden");
        }
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

profileCancelBtn?.addEventListener('click', () => {
    profileNickname.value = profileSnapshot.nickname;
    profileEmail.value    = profileSnapshot.email;
    teamRoleField.setValue(profileSnapshot.teamRole);
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

// Initialize custom-select components
initCustomSelects();

// Capture initial profile values as the baseline snapshot
profileSnapshot = {
    nickname: profileNickname.value,
    email:    profileEmail.value,
    teamRole: teamRoleField.getValue(),
};