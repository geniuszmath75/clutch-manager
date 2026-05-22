import {apiFetch} from "./helpers/fetch-helpers.js";
import type {PaginationMeta} from "./helpers/fetch-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";
import {renderPagination, bindPaginationButtons} from "./helpers/pagination.js";
import {initCustomSelects} from "./helpers/custom-select.js";

export {};

/**
 * Types
 */
interface Player {
    id: number;
    nickname: string;
    email: string;
    teamRoleIdent: string | null;
    teamId: number | null,
    isActive: boolean;
}

const userRole          = document.body.dataset['role'] ?? '';
const isPlayer          = userRole === 'PLAYER';
const isCoach           = userRole === 'COACH';
const isAdmin           = userRole === 'ADMIN';
const canEdit           = isAdmin;
const canManageActivity = isAdmin || isCoach;
const canManageTeam     = isAdmin || isCoach;

/**
 * Module state
 */
let currentPlayers:    Player[]          = [];
let availablePlayers:  Player[]          = [];
let editingPlayerId:   number | null     = null;
let currentPage:       number            = 1;
let currentMeta:       PaginationMeta | null = null;
const pageSize = 5;

/**
 * DOM elements
 */
const loadingEl = document.getElementById('players-loading')!;
const errorEl = document.getElementById('players-error')!;
const listEl = document.getElementById('players-list')!;
const tbodyEl = document.getElementById('players-tbody')!;
const roleFilterEl = document.getElementById('role-filter') as HTMLInputElement;
const statusFilterEl = document.getElementById('status-filter') as HTMLInputElement;
const btnAdd = document.getElementById('btn-add-player') as HTMLButtonElement;

// Edit modal
const modalEdit = document.getElementById('modal-edit-player') as HTMLDialogElement;
const formEdit = document.getElementById('form-edit-player') as HTMLFormElement;
const inputNick = document.getElementById('edit-nickname') as HTMLInputElement;
const selectRole = document.getElementById('edit-team-role') as HTMLInputElement;
const selectRoleTrigger = document.getElementById('edit-team-role-trigger') as HTMLButtonElement;
const btnCancelEdit = document.getElementById('btn-cancel-edit') as HTMLButtonElement;
const btnCloseEdit = document.getElementById('btn-close-edit') as HTMLButtonElement;
const btnSavePlayer      = document.getElementById('btn-save-player')       as HTMLButtonElement;
const editError = document.getElementById('edit-error')!;

// Add-to-team modal
const modalAddPlayerToTeam = document.getElementById('modal-add-player') as HTMLDialogElement;
const selectAvailable = document.getElementById('add-player-select') as HTMLInputElement;
const selectAvailableDropdown = document.getElementById('add-player-select-dropdown') as HTMLElement;
const selectAvailableTrigger = document.getElementById('add-player-select-trigger') as HTMLButtonElement;
const selectTeam = document.getElementById('add-team-select') as HTMLSelectElement;
const btnCancelAdd = document.getElementById('btn-cancel-add') as HTMLButtonElement;
const btnConfirmAdd = document.getElementById('btn-confirm-add') as HTMLButtonElement;
const btnCloseAdd = document.getElementById('btn-close-add') as HTMLButtonElement;
const addError = document.getElementById('add-error')!;

function initUI() {
    if (isPlayer) return;

    if (canManageTeam) {
        btnAdd.hidden = false;
    }
}

/**
 * API — players list
 */
async function fetchPlayers(page: number = 1, roleFilter: string = '', statusFilter: string = ''): Promise<void> {
    showLoading();

    let filters = "";

    if (roleFilter && roleFilter !== 'ALL') {
        filters += `&role=${encodeURIComponent(roleFilter)}`;
    }
    if (statusFilter && statusFilter !== 'ALL') {
        const isActive = encodeURIComponent(statusFilter) === 'ACTIVE';
        filters += `&is_active=${isActive}`;

    }

    const url: string = `/players?page=${page}&pageSize=${pageSize}${filters}`;

    try {
        const res = await apiFetch<Player[]>(url);

        if (!res.success) {
            showError(res.errorMessage ?? 'Fetching players error.');
            return;
        }

        currentPlayers = res.data ?? [];
        currentMeta = res.meta ?? null;
        currentPage = page;

        renderTable(currentPlayers);
        renderPagination(currentMeta);
    } catch {
        showError('Server connection error');
    }
}

/**
 * API — team management
 */

async function fetchAvailablePlayers(): Promise<void> {
    const res = await apiFetch<Player[]>('/players/available');

    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Failed to fetch available players.');
    }

    availablePlayers = res.data ?? [];
}

async function addPlayerToTeam(playerId: number, teamId: number): Promise<void> {
    const res = await apiFetch<Player>(`/players/${playerId}/team`, {
        method: 'POST',
        body: JSON.stringify({team_id: teamId}),
    });

    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Failed to assign player to team.');
    }
}

async function removePlayerFromTeam(playerId: number): Promise<void> {
    const res = await apiFetch(`/players/${playerId}/team`, {
        method: 'DELETE',
    });

    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Failed to assign player to team.');
    }
}

/**
 * API — player CRUD
 */
async function updatePlayer(id: number, data: Partial<Pick<Player, 'nickname' | 'teamRoleIdent'>>): Promise<Player> {
    const payload: Record<string, unknown> = {};
    if (data.nickname !== undefined) payload['nickname'] = data.nickname;
    if (data.teamRoleIdent !== undefined) payload['team_role_ident'] = data.teamRoleIdent;

    const res = await apiFetch<Player>(`/players/${id}`, {
        method: 'PUT',
        body: JSON.stringify(payload),
    });

    if (!res.success || !res.data) {
        throw new Error(res.errorMessage ?? 'Failed to update player.');
    }

    return res.data;
}

async function setPlayerActivity(id: number, active: boolean): Promise<void> {
    const action = active ? 'activate' : 'deactivate';
    const res = await apiFetch(`/players/${id}/${action}`, { method: 'PATCH' });
    if (!res.success) throw new Error(res.errorMessage ?? `Failed to ${action} player.`);
}

async function deletePlayer(id: number): Promise<void> {
    const res = await apiFetch(`/players/${id}`, { method: 'DELETE' });
    if (!res.success) throw new Error(res.errorMessage ?? 'Failed to delete player.');
}

/**
 * Render
 */
function roleBadge(ident: string | null): string {
    const cls = ident ? `role-badge--${ident.toLowerCase()}` : 'role-badge--unknown';
    return `<span class="role-badge ${cls}">${escapeHtml(ident ?? '—')}</span>`;
}

function statusBadge(isActive: boolean): string {
    const cls   = isActive ? 'status-indicator--active' : 'status-indicator--inactive';
    const label = isActive ? 'Active' : 'Inactive';
    return `<span class="status-indicator ${cls}">
                <span class="status-indicator__dot"></span>
                ${label}
            </span>`;
}

/**
 * Build a btn-icon button so that clicks on the inner <i> icon also carry the data-id.
 * Using pointer-events:none on the icon is the CSS fix, but here we pass data-id to both
 * the button AND the icon so the event delegation works regardless.
 */
function iconBtn(classes: string, id: number, iconClass: string): string {
    return `<button class="btn-icon ${classes}" data-id="${id}">
                <i class="${iconClass}" data-id="${id}"></i>
            </button>`;
}

function renderTable(players: Player[]): void {
    tbodyEl.innerHTML = '';

    if (players.length === 0) {
        const colspan = isPlayer ? 3 : 4;
        tbodyEl.innerHTML = `<tr><td class="empty-state" colspan="${colspan}">No players found.</td></tr>`;
        showList();
        return;
    }

    for (const player of players) {
        const tr = document.createElement('tr');
        tr.dataset['id'] = String(player.id);
        if (!player.isActive) tr.classList.add('is-inactive');

        if (isPlayer) {
            tr.innerHTML = `
                <td>${escapeHtml(player.nickname)}</td>
                <td>${roleBadge(player.teamRoleIdent)}</td>
                <td>${statusBadge(player.isActive)}</td>
            `;
            tbodyEl.appendChild(tr);
            continue;
        }

        const editBtn       = canEdit
            ? iconBtn('btn-icon--edit',        player.id, 'fa-solid fa-pen')
            : '';
        const toggleBtn     = canManageActivity
            ? player.isActive
                ? iconBtn('btn-icon--toggle', player.id, 'fa-solid fa-toggle-off')
                : iconBtn('btn-icon--toggle',   player.id, 'fa-solid fa-toggle-on')
            : '';
        const removeTeamBtn = canManageTeam && player.teamId !== null
            ? iconBtn('btn-icon--danger', player.id, 'fa-solid fa-person-circle-xmark')
            : '';
        const deleteBtn     = canEdit
            ? iconBtn('btn-icon--danger',      player.id, 'fa-solid fa-trash')
            : '';

        const actionsHtml = `${editBtn}${toggleBtn}${removeTeamBtn}${deleteBtn}` || '–';

        tr.innerHTML = `
            <td>${escapeHtml(player.nickname)}</td>
            <td>${roleBadge(player.teamRoleIdent)}</td>
            <td>${statusBadge(player.isActive)}</td>
            <td class="col-actions">
                <div class="actions-group">${actionsHtml}</div>
            </td>
        `;
        tbodyEl.appendChild(tr);
    }

    showList();
}

/**
 * Modal - edit player (ADMIN only)
 */
function openEditModal(player: Player): void {
    editingPlayerId = player.id;
    inputNick.value = player.nickname;
    selectRole.value = player.teamRoleIdent ?? '';
    if (selectRoleTrigger.firstChild) {
        selectRoleTrigger.firstChild.textContent = player.teamRoleIdent ? player.teamRoleIdent : 'Select role';
    }
    editError.hidden = true;
    editError.textContent = '';
    modalEdit.showModal();
}

function closeEditModal(): void {
    modalEdit.close();
    editingPlayerId = null;
}

/**
 * Modal - add player to team (ADMIN, COACH)
 */

async function openAddPlayerToTeamModal(): Promise<void> {
    addError.hidden        = true;
    addError.textContent   = '';
    btnConfirmAdd.disabled = true;

    if (selectAvailableTrigger.firstChild) {
        selectAvailableTrigger.firstChild.textContent = 'Loading...';
    }

    modalAddPlayerToTeam.showModal();

    try {
        await fetchAvailablePlayers();

        // Rebuild dropdown options after async load, then re-init custom-select on this element
        selectAvailableDropdown.innerHTML = availablePlayers.length > 0
            ? availablePlayers
                .map(p => `<button type="button" class="custom-select__option" data-value="${p.id}">${escapeHtml(p.nickname)}</button>`)
                .join('')
            : '<button type="button" class="custom-select__option" data-value="" disabled>No available players</button>';

        // Re-initialize only the add-player custom-select so the new options get event listeners
        initCustomSelects(modalAddPlayerToTeam);

        const firstLabel = availablePlayers.length > 0
            ? escapeHtml(availablePlayers[0].nickname)
            : 'No available players';

        if (selectAvailableTrigger.firstChild) {
            selectAvailableTrigger.firstChild.textContent = firstLabel;
        }

        // Pre-select first available player value in the hidden input
        if (availablePlayers.length > 0) {
            selectAvailable.value = String(availablePlayers[0].id);
        }

        btnConfirmAdd.disabled = availablePlayers.length === 0;
    } catch (err: unknown) {
        addError.textContent = err instanceof Error ? err.message : 'Failed to load players.';
        addError.hidden      = false;
        btnConfirmAdd.disabled = true;
    }
}

function closeAddPlayerToTeamModal(): void {
    modalAddPlayerToTeam.close();
}

/**
 * Event listeners
 */
roleFilterEl.addEventListener('change', async () => {
    currentPage = 1;
    await fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
})

statusFilterEl.addEventListener('change', async () => {
    currentPage = 1;
    await fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
})

// Edit modal events
if (canEdit) {
    btnCancelEdit.addEventListener('click', closeEditModal);
    btnCloseEdit.addEventListener('click', closeEditModal);

    formEdit.addEventListener('submit', async (e: Event) => {
        e.preventDefault();
        if (editingPlayerId === null) return;

        btnSavePlayer.disabled = true;
        editError.hidden       = true;

        try {
            await updatePlayer(editingPlayerId, {
                nickname:      inputNick.value.trim(),
                teamRoleIdent: selectRole.value || null,
            });
            closeEditModal();
            await fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
        } catch (err: unknown) {
            editError.textContent = err instanceof Error ? err.message : 'An error occurred';
            editError.hidden      = false;
        } finally {
            btnSavePlayer.disabled = false;
        }
    });
}

if (canManageTeam) {
    btnAdd.addEventListener('click', async () => {
        await openAddPlayerToTeamModal();
    })

    // Table action delegation — resolves icon clicks by walking up to the btn-icon wrapper
    tbodyEl.addEventListener('click', (e: Event) => {
        const btn = (e.target as HTMLElement).closest<HTMLElement>('[data-id]');

        if (!btn) return;
        const id = Number(btn.dataset['id']);
        if (!id) return;

        if (btn.classList.contains('btn-icon--edit') || btn.classList.contains('fa-pen')) {
            const player = currentPlayers.find(p => p.id === id);
            if (player) openEditModal(player);
            return;
        }

        if (btn.classList.contains('btn-icon--toggle') || btn.classList.contains('fa-toggle-on')) {
            if (!confirm(`Activate player #${id}?`)) return;
            setPlayerActivity(id, true)
                .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
                .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
            return;
        }

        if (btn.classList.contains('btn-icon--toggle') || btn.classList.contains('fa-toggle-off')) {
            if (!confirm(`Deactivate player #${id}?`)) return;
            setPlayerActivity(id, false)
                .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
                .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
            return;
        }

        if (btn.classList.contains('btn-icon--danger') || btn.classList.contains('fa-person-circle-xmark')) {
            if (!confirm(`Remove player #${id} from their team?`)) return;
            removePlayerFromTeam(id)
                .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
                .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
            return;
        }

        if (btn.classList.contains('btn-icon--danger') || btn.classList.contains('fa-trash')) {
            if (!confirm(`Delete player #${id}? This operation cannot be undone.`)) return;
            deletePlayer(id)
                .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
                .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
            return;
        }
    });

    btnCancelAdd.addEventListener('click', closeAddPlayerToTeamModal);
    btnCloseAdd.addEventListener('click', closeAddPlayerToTeamModal);

    // Add-to-team modal events
    btnConfirmAdd.addEventListener('click', async () => {
        const playerId = Number(selectAvailable.value);

        // COACH — team_id comes from session (enforced on backend); ADMIN picks from selector
        const teamId = isAdmin
            ? Number(selectTeam.value)
            : Number(document.body.dataset['teamId'] ?? 0);

        if (!playerId || !teamId) {
            addError.textContent = 'Please select a player and a team.';
            addError.hidden = false;
            return;
        }

        btnConfirmAdd.disabled = true;
        addError.hidden = true;

        try {
            await addPlayerToTeam(playerId, teamId);
            closeAddPlayerToTeamModal();
            await fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
        } catch (err: unknown) {
            addError.textContent = err instanceof Error ? err.message : 'An error occurred';
            addError.hidden = false;
            btnConfirmAdd.disabled = false;
        }
    });
}

// Pagination buttons wired via shared helper
bindPaginationButtons(
    (page) => fetchPlayers(page, roleFilterEl.value, statusFilterEl.value),
    () => currentPage,
    () => currentMeta
);

/**
 * UI helpers
 */
function showLoading(): void {
    loadingEl.hidden = false;
    loadingEl.classList.remove("state-loading--hidden");
    errorEl.hidden = true;
    errorEl.classList.add("state-error--hidden");
    listEl.hidden = true;
}

function showError(msg: string): void {
    loadingEl.hidden = true;
    loadingEl.classList.add("state-loading--hidden");
    errorEl.hidden = false;
    errorEl.classList.remove("state-error--hidden");
    errorEl.textContent = msg;
    listEl.hidden = true;
}

function showList(): void {
    loadingEl.hidden = true;
    loadingEl.classList.add("state-loading--hidden");
    errorEl.hidden = true;
    errorEl.classList.add("state-error--hidden");
    listEl.hidden = false;
}

initUI();
await fetchPlayers();