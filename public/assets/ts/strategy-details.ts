import {apiFetch} from "./helpers/fetch-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";

export {};

/**
 * Types
 */
interface StrategyPlayer {
    id: number;
    nickname: string;
    teamRole?: string;
}

interface Step {
    order: number;
    description: string;
}

interface Strategy {
    id: number;
    teamId: number;
    mapId: number;
    mapIdent: string;
    strategyTypeId: number;
    strategyTypeIdent: string;
    name: string;
    description: string;
    stepsToDo: Step[];
    players: StrategyPlayer[];
    createdAt: string;
    updatedAt: string;
}

interface DictEntry {
    id: number;
    ident: string;
}

interface Team {
    id: number;
    name: string;
}

/**
 * State
 */
const body = document.body;
const systemRole = body.dataset['systemRole'] ?? '';
const strategyId = parseInt(body.dataset['strategyId'] ?? '0', 10);

// All roles can edit; only COACH / ADMIN can delete
const canEdit = systemRole === 'COACH' || systemRole === 'ADMIN' || systemRole === 'PLAYER';
const canDelete = systemRole === 'COACH' || systemRole === 'ADMIN';
const isAdmin = systemRole === 'ADMIN';

let currentStrategy: Strategy | null = null;
let availableMaps: DictEntry[] = [];
let availableStrategyTypes: DictEntry[] = [];
let availablePlayers: StrategyPlayer[] = [];

// Edit-modal state
let editSelectedPlayerIds: number[] = [];
let editSteps: string[] = [];
let editSelectedTypeId: number | null = null;

// ADMIN only: selected team in edit modal
let editSelectedTeamId: number | null = null;
let availableTeams: Team[] = [];
let editTeamSelect: HTMLSelectElement | null = null;

const TYPE_LABELS: Record<string, string> = {
    ATTACK: 'Attack',
    DEFENSE: 'Defense',
    ECO: 'Eco Round',
    DEFAULT: 'Default Setup',
};

/**
 * DOM elements
 */
const loadingEl = document.getElementById('detail-loading')!;
const contentEl = document.getElementById('detail-content')!;
const detailError = document.getElementById('detail-error')!;
const mapBadge = document.getElementById('detail-map-badge')!;
const typeBadge = document.getElementById('detail-type-badge')!;
const nameEl = document.getElementById('detail-name')!;
const playersCountEl = document.getElementById('detail-players-count')!;
const playersListEl = document.getElementById('detail-players-list')!;
const descriptionEl = document.getElementById('detail-description')!;
const stepsListEl = document.getElementById('detail-steps-list')!;

// Edit strategy modal
const modalEdit = document.getElementById('modal-edit')! as HTMLDialogElement;
const btnEdit = document.getElementById('btn-edit-strategy') as HTMLButtonElement;
const btnEditClose = document.getElementById('modal-edit-close')! as HTMLButtonElement;
const btnEditCancel = document.getElementById('btn-edit-cancel')! as HTMLButtonElement;
const btnEditSave = document.getElementById('btn-edit-save')! as HTMLButtonElement;
const editName = document.getElementById('edit-name')! as HTMLInputElement;
const editMap = document.getElementById('edit-map')! as HTMLSelectElement;
const editDesc = document.getElementById('edit-description')! as HTMLTextAreaElement;
const editError = document.getElementById('edit-error')!;
const editTagsList = document.getElementById('edit-tags-list')!;
const editDropdown = document.getElementById('edit-player-dropdown')! as HTMLDivElement;
const btnEditPlayer = document.getElementById('btn-edit-add-player-tag')! as HTMLButtonElement;
const editStepInput = document.getElementById('edit-step-input')! as HTMLInputElement;
const btnEditStep = document.getElementById('btn-edit-add-step')! as HTMLButtonElement;
const editStepsListEl = document.getElementById('edit-steps-list')!;
const editTypeSelector = document.getElementById('edit-type-selector')!;

// Delete strategy modal
const modalDelete = document.getElementById('modal-delete') as HTMLDialogElement;
const btnDelete = document.getElementById('btn-delete-strategy') as HTMLButtonElement;
const btnDeleteClose = document.getElementById('modal-delete-close') as HTMLButtonElement;
const btnDeleteCancel = document.getElementById('btn-delete-cancel') as HTMLButtonElement;
const btnDeleteConfirm = document.getElementById('btn-delete-confirm') as HTMLButtonElement;
const deleteNameEl = document.getElementById('modal-delete-name')!;
const deleteError = document.getElementById('delete-error');

/**
 * API - strategy details
 */
async function fetchStrategy(): Promise<void> {
    const res = await apiFetch<Strategy>(`/strategies/${strategyId}`);

    if (!res.success || !res.data) {
        showDetailError(res.errorMessage ?? 'Strategy not found.');
        return;
    }

    currentStrategy = res.data;
    renderDetail(currentStrategy);
}

/**
 * API - fetch dictionaries for edit modal (maps + strategy types)
 */
async function fetchDictionaries(): Promise<void> {
    const [mapsRes, typesRes] = await Promise.all([
        apiFetch<DictEntry[]>('/game-maps'),
        apiFetch<DictEntry[]>('/strategy-types'),
    ]);

    if (mapsRes.success && mapsRes.data) availableMaps = mapsRes.data;
    if (typesRes.success && typesRes.data) availableStrategyTypes = typesRes.data;
}

/**
 * API - load players for edit modal
 * COACH/PLAYER: own team (no param needed - API scopes by session)
 * ADMIN: for a specific team
 */
async function loadPlayersForEdit(teamId?: number): Promise<void> {
    availablePlayers = [];
    editSelectedPlayerIds = [];
    renderEditTags();

    const url = teamId
        ? `/players?status=active&team_id=${teamId}`
        : '/players?status=active';

    const res = await apiFetch<StrategyPlayer[]>(url);
    if (res.success && res.data) availablePlayers = res.data;
}

/**
 * API - load teams (ADMIN only)
 */
async function loadTeams(): Promise<void> {
    if (availableTeams.length > 0) return;
    const res = await apiFetch<Team[]>('/teams');
    if (res.success && res.data) availableTeams = res.data;
}

/**
 * API - update
 */
async function updateStrategyDetails(data: object): Promise<void> {
    const res = await apiFetch<Strategy>(`/strategies/${strategyId}`, {
        method: 'PUT',
        body: JSON.stringify(data),
    });
    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Failed to update strategy.');
    }
}

/**
 * API - delete
 */
async function deleteStrategy(): Promise<void> {
    const res = await apiFetch(`/strategies/${strategyId}`, {method: 'DELETE'});
    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Failed to delete strategy.');
    }
}

/**
 * Render
 */
function renderDetail(s: Strategy): void {
    loadingEl.hidden = true;
    contentEl.hidden = false;
    detailError.hidden = true;

    // Badges
    mapBadge.textContent = escapeHtml(s.mapIdent);
    typeBadge.textContent = escapeHtml(TYPE_LABELS[s.strategyTypeIdent] ?? s.strategyTypeIdent);

    // Title
    nameEl.textContent = escapeHtml(s.name);

    // Card 1 — Players
    playersCountEl.textContent = `${s.players.length}/5`;
    playersListEl.innerHTML = s.players.length > 0
        ? s.players.map(p => `
            <li class="players-list__item">
                <span class="players-list__nickname">${escapeHtml(p.nickname)}</span>
                ${p.teamRole
            ? `<span class="badge badge--role">${escapeHtml(p.teamRole)}</span>`
            : ''}
            </li>
          `).join('')
        : '<li class="players-list__item players-list__item--empty">No players assigned</li>';

    // Card 2 — Overview
    descriptionEl.textContent = s.description;

    // Card 3 — Steps
    const sorted = [...s.stepsToDo].sort((a, b) => a.order - b.order);
    stepsListEl.innerHTML = sorted.length > 0
        ? sorted.map(step => `
            <li class="steps-list__item">
                <span class="steps-list__text">${escapeHtml(step.description)}</span>
            </li>
          `).join('')
        : '<li class="steps-list__item steps-list__item--empty">No steps defined</li>';

}

/**
 * Render - edit player tags
 */
function renderEditTags(): void {
    editTagsList.innerHTML = editSelectedPlayerIds.map(id => {
        const p = availablePlayers.find(pl => pl.id === id)
            ?? currentStrategy?.players.find(pl => pl.id === id);
        if (!p) return '';
        return `<span class="player-tag">
            ${escapeHtml(p.nickname)}
            <button type="button" class="player-tag__remove" data-id="${p.id}"
                    aria-label="Remove ${escapeHtml(p.nickname)}">&times;</button>
        </span>`;
    }).join('');

    editTagsList.querySelectorAll<HTMLButtonElement>('.player-tag__remove').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset['id'] ?? '0', 10);
            editSelectedPlayerIds = editSelectedPlayerIds.filter(x => x !== id);
            renderEditTags();
        });
    });
}

/**
 * Render - edit player dropdown
 */
function renderEditPlayerDropdown(): void {
    const available = availablePlayers.filter(p => !editSelectedPlayerIds.includes(p.id));

    if (available.length === 0) {
        editDropdown.innerHTML = '<p class="dropdown-empty">All players assigned</p>';
        return;
    }

    editDropdown.innerHTML = available.map(p =>
        `<button type="button" class="dropdown-option" data-id="${p.id}">${escapeHtml(p.nickname)}</button>`
    ).join('');

    editDropdown.querySelectorAll<HTMLButtonElement>('.dropdown-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset['id'] ?? '0', 10);
            editSelectedPlayerIds.push(id);
            renderEditTags();
            editDropdown.hidden = true;
        });
    });
}

/**
 * Render - edit steps list
 */
function renderEditStepsList(): void {
    editStepsListEl.innerHTML = editSteps.map((s, i) => `
        <li class="steps-list__item">
            <span class="steps-list__num">${i + 1}.</span>
            <span class="steps-list__text">${escapeHtml(s)}</span>
            <button type="button" class="steps-list__remove" data-index="${i}"
                    aria-label="Remove step">&times;</button>
        </li>
    `).join('');

    editStepsListEl.querySelectorAll<HTMLButtonElement>('.steps-list__remove').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = parseInt(btn.dataset['index'] ?? '0', 10);
            editSteps.splice(idx, 1);
            renderEditStepsList();
        });
    });
}

/**
 * MODAL
 */

/**
 * Edit modal - populate map select
 */
function populateEditMapSelect(): void {
    editMap.innerHTML = '<option value="">Select map...</option>';
    availableMaps.forEach(m => {
        const opt = document.createElement('option');
        opt.value = String(m.id);
        opt.textContent = m.ident.charAt(0) + m.ident.slice(1).toLowerCase();
        editMap.appendChild(opt);
    });
}

/**
 * Edit modal - populate type selector
 */
function populateEditTypeSelector(): void {
    editTypeSelector.innerHTML = availableStrategyTypes.map(t =>
        `<button type="button"
                 class="type-selector__option"
                 data-type-id="${t.id}"
                 aria-pressed="false">
            ${escapeHtml(TYPE_LABELS[t.ident] ?? t.ident)}
         </button>`
    ).join('');

    editTypeSelector.addEventListener('click', (e) => {
        const btn = (e.target as HTMLElement).closest<HTMLButtonElement>('.type-selector__option');
        if (!btn) return;
        editTypeSelector.querySelectorAll<HTMLButtonElement>('.type-selector__option').forEach(b => {
            b.setAttribute('aria-pressed', 'false');
            b.classList.remove('is-active');
        });
        btn.setAttribute('aria-pressed', 'true');
        btn.classList.add('is-active');
        editSelectedTypeId = parseInt(btn.dataset['typeId'] ?? '0', 10);
    });
}

/**
 * Edit modal step handler
 */
function handleEditStep(): void {
    const val = editStepInput.value.trim();
    if (!val) return;
    editSteps.push(val);
    editStepInput.value = '';
    renderEditStepsList();
}

/**
 * ADMIN — inject team selector into edit modal (once) and wire player reload
 */
function ensureAdminEditTeamSelector(strategy: Strategy): void {
    if (editTeamSelect) {
        // Already injected — just update value and reset players
        editTeamSelect.value = String(strategy.teamId);
        editSelectedTeamId = strategy.teamId;
        return;
    }

    const playerRow = editTagsList.closest('.form-field');
    if (!playerRow) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'form-field';
    wrapper.id = 'edit-team-field';
    wrapper.innerHTML = `
        <label class="form-label" for="edit-team">Team <span class="required">*</span></label>
        <select class="form-select" id="edit-team">
            <option value="">Select team…</option>
            ${availableTeams.map(t =>
        `<option value="${t.id}">${escapeHtml(t.name)}</option>`
    ).join('')}
        </select>
    `;

    playerRow.parentElement!.insertBefore(wrapper, playerRow);
    editTeamSelect = wrapper.querySelector<HTMLSelectElement>('#edit-team')!;

    editTeamSelect.addEventListener('change', async () => {
        const teamId = parseInt(editTeamSelect!.value, 10);
        if (!teamId) {
            availablePlayers = [];
            editSelectedPlayerIds = [];
            renderEditTags();
            editSelectedTeamId = null;
            return;
        }
        editSelectedTeamId = teamId;
        await loadPlayersForEdit(teamId);
    });
}

/**
 * Edit modal - prefill all fields from strategy data
 */
async function openEditModal(s: Strategy): Promise<void> {
    // Load dictionaries once
    if (availableMaps.length === 0 || availableStrategyTypes.length === 0) {
        await fetchDictionaries();
        populateEditMapSelect();
        populateEditTypeSelector();
    }

    if (isAdmin) {
        await loadTeams();
        ensureAdminEditTeamSelector(s);
        // Set current team, then load players for that team
        editTeamSelect!.value = String(s.teamId);
        editSelectedTeamId = s.teamId;
        await loadPlayersForEdit(s.teamId);
    } else {
        // COACH/PLAYER: load own team players once
        if (availablePlayers.length === 0) await loadPlayersForEdit();
    }

    // Prefill scalar fields
    editName.value = s.name;
    editMap.value = String(s.mapId);
    editDesc.value = s.description;
    editError.hidden = true;

    // Type selector
    editSelectedTypeId = s.strategyTypeId;
    editTypeSelector.querySelectorAll<HTMLButtonElement>('.type-selector__option').forEach(btn => {
        const isActive = parseInt(btn.dataset['typeId'] ?? '0', 10) === s.strategyTypeId;
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        btn.classList.toggle('is-active', isActive);
    });

    // Players — keep current strategy's players selected even if they aren't
    // in the freshly loaded list (e.g. deactivated between sessions)
    editSelectedPlayerIds = s.players.map(p => p.id);
    renderEditTags();

    // Steps
    const sorted = [...s.stepsToDo].sort((a, b) => a.order - b.order);
    editSteps = sorted.map(st => st.description);
    renderEditStepsList();

    editStepInput.value = '';
    modalEdit.showModal();
}

const closeEditModal = () => modalEdit.close();

/**
 * MODAL - Delete Strategy (COACH / ADMIN only)
 */
const closeDeleteModal = () => modalDelete.close();

/**
 * Event listeners
 */

// Edit modal internals
btnEditStep.addEventListener('click', handleEditStep);
editStepInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleEditStep();
    }
});

btnEditPlayer.addEventListener('click', () => {
    if (editDropdown.hidden) {
        renderEditPlayerDropdown();
        editDropdown.hidden = false;
    } else {
        editDropdown.hidden = true;
    }
});

document.addEventListener('click', (e) => {
    if (
        !btnEditPlayer.contains(e.target as Node) &&
        !editDropdown.contains(e.target as Node)
    ) {
        editDropdown.hidden = true;
    }
});

btnEditClose.addEventListener('click', closeEditModal);
btnEditCancel.addEventListener('click', closeEditModal);
modalEdit.addEventListener('click', (e) => {
    if (e.target === modalEdit) closeEditModal();
});

btnEditSave.addEventListener('click', async () => {
    hideError(editError);

    const name = editName.value.trim();
    const mapId = parseInt(editMap.value, 10);

    if (!name) {
        showFormError(editError, 'Strategy name is required.');
        return;
    }
    if (!mapId) {
        showFormError(editError, 'Please select a map.');
        return;
    }
    if (!editSelectedTypeId) {
        showFormError(editError, 'Please select a strategy type.');
        return;
    }
    if (!editDesc.value.trim()) {
        showFormError(editError, 'Description is required.');
        return;
    }

    btnEditSave.disabled = true;
    try {
        await updateStrategyDetails({
            name,
            map_id: mapId,
            strategy_type_id: editSelectedTypeId,
            description: editDesc.value.trim(),
            steps_to_do: editSteps.map((s, i) => ({order: i + 1, description: s})),
            player_ids: editSelectedPlayerIds,
        });
        closeEditModal();
        await fetchStrategy();
    } catch (err: unknown) {
        showFormError(editError, err instanceof Error ? err.message : 'An error occurred.');
    } finally {
        btnEditSave.disabled = false;
    }
});

// Open edit modal
if (canEdit && btnEdit) {
    btnEdit.addEventListener('click', async () => {
        if (currentStrategy) await openEditModal(currentStrategy);
    })
}

// Delete modal
if (canDelete && modalDelete && btnDelete) {
    btnDelete.addEventListener('click', () => {
        modalDelete.showModal();
        modalDelete.hidden = false;
        deleteNameEl.textContent = currentStrategy ? currentStrategy.name : "";
    });
    btnDeleteClose.addEventListener('click', closeDeleteModal);
    btnDeleteCancel.addEventListener('click', closeDeleteModal);
    modalDelete.addEventListener('click', (e) => {
        if (e.target === modalDelete) closeDeleteModal();
    });

    btnDeleteConfirm.addEventListener('click', async () => {
        if (deleteError) hideError(deleteError);
        btnDeleteConfirm.disabled = true;
        try {
            await deleteStrategy();
            window.location.href = '/dashboard/strategies';
        } catch (err: unknown) {
            if (deleteError) {
                showFormError(deleteError, err instanceof Error ? err.message : 'An error occurred.');
            }
        } finally {
            btnDeleteConfirm.disabled = false;
        }
    });
}

/**
 * UI helpers
 */
function showFormError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden = false;
}

function hideError(el: HTMLElement): void {
    el.textContent = '';
    el.hidden = true;
}

function showDetailError(msg: string): void {
    loadingEl.hidden = true;
    contentEl.hidden = true;
    detailError.hidden = false;
    detailError.textContent = msg;
}

/**
 * Init
 */
// bindDeleteModal();
await fetchStrategy();