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

interface PaginationMeta {
    total: number,
    page: number,
    pageSize: number;
    totalPages: number
}

interface ApiResponse<T> {
    success: boolean;
    data?: T;
    statusCode?: number;
    errorMessage?: string;
}

interface PaginatedApiResponse<T> extends ApiResponse<T> {
    meta: PaginationMeta
}

const userRole = document.body.dataset['role'] ?? '';
const isPlayer = userRole === 'PLAYER';
const isCoach = userRole === 'COACH';
const isAdmin = userRole === 'ADMIN';

const canEdit = isAdmin;
const canManageActivity = isAdmin || isCoach;
const canManageTeam = isAdmin || isCoach;

/**
 * Module state
 */
let currentPlayers: Player[] = [];
let availablePlayers: Player[] = [];
let editingPlayerId: number | null = null;
let currentPage = 1;
let currentMeta: PaginationMeta | null = null;
const pageSize = 5;

/**
 * DOM elements
 */
const loadingEl = document.getElementById('players-loading')!;
const errorEl = document.getElementById('players-error')!;
const listEl = document.getElementById('players-list')!;
const tbodyEl = document.getElementById('players-tbody')!;
const roleFilterEl = document.getElementById('role-filter') as HTMLSelectElement;
const statusFilterEl = document.getElementById('status-filter') as HTMLSelectElement;
const paginationEl = document.getElementById('pagination')!;
const paginInfo = document.getElementById('pagination-info')!;
const btnPrev = document.getElementById('btn-prev')! as HTMLButtonElement;
const btnNext = document.getElementById('btn-next')! as HTMLButtonElement;
const btnAdd = document.getElementById('btn-add-player') as HTMLButtonElement;

// Edit modal
const modalEdit = document.getElementById('modal-edit-player') as HTMLDialogElement;
const formEdit = document.getElementById('form-edit-player') as HTMLFormElement;
const inputNick = document.getElementById('edit-nickname') as HTMLInputElement;
const selectRole = document.getElementById('edit-team-role') as HTMLSelectElement;
const btnCancelEdit = document.getElementById('btn-cancel-edit') as HTMLButtonElement;
const editError = document.getElementById('edit-error')!;

// Add-to-team modal
const modalAddPlayerToTeam = document.getElementById('modal-add-player') as HTMLDialogElement;
const selectAvailable = document.getElementById('add-player-select') as HTMLSelectElement;
const selectTeam = document.getElementById('add-team-select') as HTMLSelectElement;
const teamSelectWrapper = document.getElementById('team-select-wrapper')!;
const btnCancelAdd = document.getElementById('btn-cancel-add') as HTMLButtonElement;
const btnConfirmAdd = document.getElementById('btn-confirm-add') as HTMLButtonElement;
const addError = document.getElementById('add-error')!;

function initUI() {
    if (isPlayer) return;

    if (canManageTeam) {
        btnAdd.hidden = false;
    }

    // Team selector in modal only visible for ADMIN
    teamSelectWrapper.hidden = !isAdmin;
}

/**
 * API — players list
 */
async function fetchPlayers(page: number = 1, roleFilter: string = '', statusFilter: string = ''): Promise<void> {
    showLoading();

    let filters = "";

    if (roleFilter) {
        filters += `&role=${encodeURIComponent(roleFilter)}`;
    }
    if (statusFilter) {
        const isActive = encodeURIComponent(statusFilter) === 'ACTIVE';
        filters += `&is_active=${isActive}`;

    }

    const url: string = `/players?page=${page}&pageSize=${pageSize}${filters}`;

    try {
        const res = await fetch(url, {
            headers: {'Accept': 'application/json'}
        });

        const json: PaginatedApiResponse<Player[]> = await res.json();

        if (!res.ok || !json.success) {
            showError(json.errorMessage ?? 'Fetching players error.');
            return;
        }

        currentPlayers = json.data ?? [];
        currentMeta = json.meta ?? null;
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
    const res = await fetch('/players/available', {headers: {'Accept': 'application/json'}});
    const json: ApiResponse<Player[]> = await res.json();

    if (!res.ok || !json.success) {
        throw new Error(json.errorMessage ?? 'Failed to fetch available players.');
    }

    availablePlayers = json.data ?? [];
}

async function addPlayerToTeam(playerId: number, teamId: number): Promise<void> {
    const res = await fetch(`/players/${playerId}/team`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({team_id: teamId}),
    });
    const json: ApiResponse<never> = await res.json();

    if (!res.ok || !json.success) {
        throw new Error(json.errorMessage ?? 'Failed to assign player to team.');
    }
}

async function removePlayerFromTeam(playerId: number): Promise<void> {
    const res = await fetch(`/players/${playerId}/team`, {
        method: 'DELETE',
        headers: {'Accept': 'application/json'},
    });
    const json: ApiResponse<never> = await res.json();

    if (!res.ok || !json.success) {
        throw new Error(json.errorMessage ?? 'Failed to assign player to team.');
    }
}

/**
 * API — player CRUD
 */
async function updatePlayer(id: number, data: Partial<Pick<Player, 'nickname' | 'teamRoleIdent'>>): Promise<Player> {
    const payload: Record<string, unknown> = {};
    if (data.nickname !== undefined) payload['nickname'] = data.nickname;
    if (data.teamRoleIdent !== undefined) payload['team_role_ident'] = data.teamRoleIdent;

    const res = await fetch(`/players/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    });

    const json: ApiResponse<Player> = await res.json();

    if (!res.ok || !json.success || !json.data) {
        throw new Error(json.errorMessage ?? 'Failed to update player.');
    }

    return json.data;
}

async function setPlayerActivity(id: number, active: boolean): Promise<void> {
    try {
        const action = active ? 'activate' : 'deactivate';
        const res = await fetch(`/players/${id}/${action}`, {
            method: 'PATCH',
            headers: {'Accept': 'application/json'},
        });
        const json: ApiResponse<Player> = await res.json();

        if (!res.ok || !json.success) {
            showError(json.errorMessage ?? `Failed to ${active ? 'activate' : 'deactivate'} player.`);
        }
    } catch {
        showError('Server connection error');
    }
}

async function deletePlayer(id: number): Promise<void> {
    try {
        const res = await fetch(`/players/${id}`, {
            method: 'DELETE',
            headers: {'Accept': 'application/json'},
        });
        const json: ApiResponse<Player> = await res.json();

        if (!res.ok || !json.success) {
            showError(json.errorMessage ?? 'Failed to delete player.');
        }
    } catch {
        showError('Server connection error');
    }
}

/**
 * Render
 */
function renderTable(players: Player[]): void {
    tbodyEl.innerHTML = '';

    if (players.length === 0) {
        const colspan = isPlayer ? 3 : 4;
        tbodyEl.innerHTML = `<tr><td colspan="${colspan}">No players found.</td></tr>`;
        showList();
        return;
    }

    for (const player of players) {
        const tr = document.createElement('tr');
        tr.dataset['id'] = String(player.id);

        const statusLabel = player.isActive ? 'Active' : 'Inactive';

        if (isPlayer) {
            tr.innerHTML = `
                <td>${escapeHtml(player.nickname)}</td>
                <td>${escapeHtml(player.teamRoleIdent ?? '—')}</td>
                <td>${statusLabel}</td>
            `;
            tbodyEl.appendChild(tr);
            continue;
        }

        const editBtn = canEdit
            ? `<button class="btn-edit" data-id="${player.id}">Edit</button>`
            : '';

        const toggleBtn = canManageActivity
            ? player.isActive
                ? `<button class="btn-deactivate" data-id="${player.id}">Deactivate</button>`
                : `<button class="btn-activate"   data-id="${player.id}">Activate</button>`
            : '';

        const removeTeamBtn = canManageTeam && player.teamId !== null
            ? `<button class="btn-remove-team" data-id="${player.id}">Remove from team</button>`
            : '';

        const deleteBtn = canEdit
            ? `<button class="btn-delete" data-id="${player.id}">Delete</button>`
            : '';

        const actionsHtml = `${editBtn}${toggleBtn}${removeTeamBtn}${deleteBtn}` || '-';

        tr.innerHTML = `
            <td>${escapeHtml(player.nickname)}</td>
            <td>${escapeHtml(player.teamRoleIdent ?? '—')}</td>
            <td>${statusLabel}</td>
            <td class="actions-col">${actionsHtml}</td>
        `;

        tbodyEl.appendChild(tr);
    }

    showList();
}

/**
 * Render - pagination
 */
function renderPagination(meta: PaginationMeta | null): void {
    if (meta === null) {
        paginationEl.hidden = true;
        return;
    }

    paginationEl.hidden = false;
    paginInfo.textContent = `Showing ${meta.page} of ${meta.totalPages} (${meta.total} players)`;
    btnPrev.disabled = meta.page <= 1;
    btnNext.disabled = meta.page >= meta.totalPages;
}

/**
 * Modal - edit player (ADMIN only)
 */
function openEditModal(player: Player): void {
    editingPlayerId = player.id;
    inputNick.value = player.nickname;
    selectRole.value = player.teamRoleIdent ?? '';
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
    addError.hidden = true;
    addError.textContent = '';
    selectAvailable.innerHTML = '<option value="">Loading...</option>';
    btnConfirmAdd.disabled = true;
    modalAddPlayerToTeam.showModal();

    try {
        await fetchAvailablePlayers();

        selectAvailable.innerHTML = availablePlayers.length > 0
            ? availablePlayers.map(p => `<option value="${p.id}">${escapeHtml(p.nickname)}</option>`).join('')
            : '<option value="" disabled>No available players</option>';

        btnConfirmAdd.disabled = availablePlayers.length === 0;
    } catch (err: unknown) {
        addError.textContent = err instanceof Error ? err.message : 'Failed to load players.';
        addError.hidden = false;
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

// Pagination
btnPrev.addEventListener('click', async () => {
    if (currentPage > 1) {
        await fetchPlayers(currentPage - 1, roleFilterEl.value, statusFilterEl.value);
    }
});

btnNext.addEventListener('click', async () => {
    if (currentMeta && currentPage < currentMeta.totalPages) {
        await fetchPlayers(currentPage + 1, roleFilterEl.value, statusFilterEl.value);
    }
});

btnAdd.addEventListener('click', async () => {
    await openAddPlayerToTeamModal();
})

tbodyEl.addEventListener('click', (e: Event) => {
    const target = e.target as HTMLElement;
    const id = Number(target.dataset['id']);
    if (!id) return;

    if (target.classList.contains('btn-edit')) {
        const player = currentPlayers.find(p => p.id === id);
        if (player) openEditModal(player);
        return;
    }

    if (target.classList.contains('btn-activate')) {
        if (!confirm(`Activate player #${id}?`)) return;

        setPlayerActivity(id, true)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
        return;
    }

    if (target.classList.contains('btn-deactivate')) {
        if (!confirm(`Deactivate player #${id}?`)) return;

        setPlayerActivity(id, false)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
        return;
    }

    if (target.classList.contains('btn-remove-team')) {
        if (!confirm(`Remove player #${id} from their team?`)) return;

        removePlayerFromTeam(id)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
        return;
    }

    if (target.classList.contains('btn-delete')) {
        if (!confirm(`Delete player #${id}? This operation cannot be undone.`)) return;
        deletePlayer(id)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
        return;
    }
});

// Edit modal events
btnCancelEdit.addEventListener('click', closeEditModal);

formEdit.addEventListener('submit', async (e: Event) => {
    e.preventDefault();
    if (editingPlayerId === null) return;

    const btnSave = document.getElementById('btn-save-player') as HTMLButtonElement;
    btnSave.disabled = true;
    editError.hidden = true;

    try {
        await updatePlayer(editingPlayerId, {
            nickname: inputNick.value.trim(),
            teamRoleIdent: selectRole.value || null,
        });

        closeEditModal();
        await fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
    } catch (err: unknown) {
        editError.textContent = err instanceof Error ? err.message : 'An error occurred';
        editError.hidden = false;
    } finally {
        btnSave.disabled = false;
    }
});

// Add-to-team modal events
btnCancelAdd.addEventListener('click', closeAddPlayerToTeamModal);

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

/**
 * UI helpers
 */
function showLoading(): void {
    loadingEl.hidden = true;
    errorEl.hidden = true;
    listEl.hidden = true;
}

function showError(msg: string): void {
    loadingEl.hidden = true;
    errorEl.hidden = false;
    errorEl.textContent = msg;
    listEl.hidden = true;
}

function showList(): void {
    loadingEl.hidden = true;
    errorEl.hidden = true;
    listEl.hidden = false;
}

function escapeHtml(str: string): string {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

initUI();
fetchPlayers();