/**
 * Types
 */
interface Player {
    id: number;
    nickname: string;
    email: string;
    teamRoleIdent: string | null;
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

/**
 * Module state
 */
let currentPlayers: Player[] = [];
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
const modal = document.getElementById('modal-edit-player') as HTMLDialogElement;
const formEdit = document.getElementById('form-edit-player') as HTMLFormElement;
const inputNick = document.getElementById('edit-nickname') as HTMLInputElement;
const selectRole = document.getElementById('edit-team-role') as HTMLSelectElement;
const btnCancel = document.getElementById('btn-cancel-edit') as HTMLButtonElement;
const editError = document.getElementById('edit-error')!;
const paginationEl = document.getElementById('pagination')!;
const paginInfo = document.getElementById('pagination-info')!;
const btnPrev = document.getElementById('btn-prev')! as HTMLButtonElement;
const btnNext = document.getElementById('btn-next')! as HTMLButtonElement;
const btnAdd = document.getElementById('btn-add-player') as HTMLButtonElement;
const pageTitle = document.getElementById('page-title')!;

function initUI() {
    if (isPlayer) return;

    if (canManageActivity) {
        btnAdd.hidden = false;
    }
}

async function fetchPlayers(page: number = 1, roleFilter: string = '', statusFilter: string = ''): Promise<void> {
    showLoading();

    let filters: string = "";

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
        tbodyEl.innerHTML = '<tr><td colspan="4">No users found.</td></tr>';
        showList();
        return;
    }

    for (const player of players) {
        const tr = document.createElement('tr');
        tr.dataset['id'] = String(player.id);

        const statusLabel = player.isActive ? 'Active' : 'Inactive';
        let actionsHtml: string;

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
            ? `<button class="btn-edit" data-id="${player.id}">Edit player</button>`
            : '';

        const toggleBtn = canManageActivity
            ? player.isActive
                ? `<button class="btn-deactivate" data-id="${player.id}">Deactivate player</button>`
                : `<button class="btn-activate"   data-id="${player.id}">Activate player</button>`
            : '';

        const deleteBtn = canEdit
            ? `<button class="btn-delete" data-id="${player.id}">Delete player</button>`
            : '';

        actionsHtml = `${editBtn}${toggleBtn}${deleteBtn}` || '-';

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
 * Modal
 */
function openEditModal(player: Player): void {
    editingPlayerId = player.id;
    inputNick.value = player.nickname;
    selectRole.value = player.teamRoleIdent ?? '';
    editError.hidden = true;
    editError.textContent = '';
    modal.showModal();
}

function closeModal(): void {
    modal.close();
    editingPlayerId = null;
}

/**
 * Event listeners
 */
roleFilterEl.addEventListener('change', () => {
    currentPage = 1;
    fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
})

statusFilterEl.addEventListener('change', () => {
    currentPage = 1;
    fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
})

// Pagination
btnPrev.addEventListener('click', () => {
    if (currentPage > 1) {
        fetchPlayers(currentPage - 1, roleFilterEl.value, statusFilterEl.value);
    }
});

btnNext.addEventListener('click', () => {
    if (currentMeta && currentPage < currentMeta.totalPages) {
        fetchPlayers(currentPage + 1, roleFilterEl.value, statusFilterEl.value);
    }
});

tbodyEl.addEventListener('click', (e: Event) => {
    const target = e.target as HTMLElement;
    const id = Number(target.dataset['id']);
    if (!id) return;

    if (target.classList.contains('btn-edit')) {
        const player = currentPlayers.find(p => p.id === id);
        if (player) openEditModal(player);
    }

    if (target.classList.contains('btn-activate')) {
        if (!confirm(`Activate player #${id}?`)) return;

        setPlayerActivity(id, true)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
    }

    if (target.classList.contains('btn-deactivate')) {
        if (!confirm(`Deactivate player #${id}?`)) return;

        setPlayerActivity(id, false)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
    }

    if (target.classList.contains('btn-delete')) {
        if (!confirm(`Delete player #${id}? This operation cannot be undone.`)) return;
        deletePlayer(id)
            .then(() => fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
        return;
    }
});

btnCancel.addEventListener('click', closeModal);

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

        closeModal();
        await fetchPlayers(currentPage, roleFilterEl.value, statusFilterEl.value);
    } catch (err: unknown) {
        editError.textContent = err instanceof Error ? err.message : 'An error occurred';
        editError.hidden = false;
    } finally {
        btnSave.disabled = false;
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