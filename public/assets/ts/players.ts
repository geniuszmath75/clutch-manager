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

interface ApiResponse<T> {
    success: boolean;
    data?: T;
    error?: string;
    message?: string;
}

/**
 * Module state
 */
let currentPlayers: Player[] = [];
let editingPlayerId: number | null = null;

/**
 * DOM elements
 */
const loadingEl = document.getElementById('players-loading')!;
const errorEl = document.getElementById('players-error')!;
const listEl = document.getElementById('players-list')!;
const tbodyEl = document.getElementById('players-tbody')!;
const filterEl = document.getElementById('role-filter') as HTMLSelectElement;
const modal = document.getElementById('modal-edit-player') as HTMLDialogElement;
const formEdit = document.getElementById('form-edit-player') as HTMLFormElement;
const inputNick = document.getElementById('edit-nickname') as HTMLInputElement;
const selectRole = document.getElementById('edit-team-role') as HTMLSelectElement;
const btnCancel = document.getElementById('btn-cancel-edit') as HTMLButtonElement;
const editError = document.getElementById('edit-error')!;

async function fetchPlayers(roleFilter: string = ''): Promise<void> {
    showLoading();

    const url = roleFilter ? `/players?role=${encodeURIComponent(roleFilter)}` : '/players';

    try {
        const res = await fetch(url, {
            headers: {'Accept': 'application/json'}
        });

        const json: ApiResponse<Player[]> = await res.json();

        if (!res.ok || !json.success) {
            showError(json.error ?? 'Fetching players error.');
            return;
        }

        currentPlayers = json.data ?? [];
        renderTable(currentPlayers);
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
        throw new Error(json.error ?? 'Failed to update player.');
    }

    return json.data;
}

async function deactivatePlayer(id: number): Promise<void> {
    try {
        const res = await fetch(`/players/${id}`, {
            method: 'PATCH',
            headers: {
                'ContentType': 'application/json',
                'Accept': 'application/json'
            }
        });

        const json: ApiResponse<never> = await res.json();

        if (!res.ok || !json.success) {
            showError(json.error ?? 'Deactivating player error.');
            return;
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

        tr.innerHTML = `
            <td>${escapeHtml(player.nickname)}</td>
            <td>${escapeHtml(player.teamRoleIdent ?? '—')}</td>
            <td>${player.isActive ? 'Active' : 'Inactive'}</td>
            <td class="actions-col">
                <button class="btn-edit" data-id="${player.id}">Edit player</button>
                <button class="btn-deactivate" data-id="${player.id}"
                    ${!player.isActive ? 'disabled' : ''}>
                    Deactivate player
                </button>
            </td>
        `;

        tbodyEl.appendChild(tr);
    }

    showList();
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
filterEl.addEventListener('change', () => {
    fetchPlayers(filterEl.value);
})

tbodyEl.addEventListener('click', (e: Event) => {
    const target = e.target as HTMLElement;
    const id = Number(target.dataset['id']);
    if (!id) return;

    if (target.classList.contains('btn-edit')) {
        const player = currentPlayers.find(p => p.id === id);
        if (player) openEditModal(player);
    }

    if (target.classList.contains('btn-deactivate')) {
        if (!confirm(`Deactivate player #${id}?`)) return;

        deactivatePlayer(id)
            .then(() => fetchPlayers(filterEl.value))
            .catch(err => alert(err instanceof Error ? err.message : 'An error occurred'));
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
        await fetchPlayers(filterEl.value);
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

fetchPlayers();