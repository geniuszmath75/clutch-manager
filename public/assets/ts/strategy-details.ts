import {apiFetch} from "./helpers/fetch-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";
import { initCustomSelects } from "./helpers/custom-select.js";

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

/**
 * State
 */
const body = document.body;
const systemRole = body.dataset['systemRole'] ?? '';
const strategyId = parseInt(body.dataset['strategyId'] ?? '0', 10);

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

const TYPE_LABELS: Record<string, string> = {
    ATTACK: 'Attack',
    DEFENSE: 'Defense',
    ECO: 'Eco',
    DEFAULT: 'Default',
};

/**
 * DOM elements
 */
const loadingEl      = document.getElementById('detail-loading')!;
const contentEl      = document.getElementById('detail-content')!;
const detailError    = document.getElementById('detail-error')!;
const mapBadge       = document.getElementById('detail-map-badge')!;
const typeBadge      = document.getElementById('detail-type-badge')!;
const nameEl         = document.getElementById('detail-name')!;
const playersCountEl = document.getElementById('detail-players-count')!;
const playersListEl  = document.getElementById('detail-players-list')!;
const descriptionEl  = document.getElementById('detail-description')!;
const stepsListEl    = document.getElementById('detail-steps-list')!;

// Edit strategy modal
const modalEdit      = document.getElementById('modal-edit')!       as HTMLDialogElement;
const btnEdit        = document.getElementById('btn-edit-strategy') as HTMLButtonElement | null;
const btnEditClose   = document.getElementById('modal-edit-close')  as HTMLButtonElement;
const btnEditCancel  = document.getElementById('btn-edit-cancel')   as HTMLButtonElement;
const btnEditSave    = document.getElementById('btn-edit-save')     as HTMLButtonElement;
const editName       = document.getElementById('edit-name')         as HTMLInputElement;
const editMapInput   = document.getElementById('edit-map')          as HTMLInputElement;  // hidden
const editMapDropdown= document.getElementById('edit-map-dropdown') as HTMLElement;
const editMapTrigger = document.getElementById('edit-map-trigger')  as HTMLButtonElement;
const editDesc       = document.getElementById('edit-description')  as HTMLTextAreaElement;
const editError      = document.getElementById('edit-error')!;
const editTagsList   = document.getElementById('edit-tags-list')!;
const editDropdown   = document.getElementById('edit-player-dropdown') as HTMLDivElement;
const btnEditPlayer  = document.getElementById('btn-edit-add-player-tag') as HTMLButtonElement;
const editStepInput  = document.getElementById('edit-step-input')   as HTMLInputElement;
const btnEditStep    = document.getElementById('btn-edit-add-step') as HTMLButtonElement;
const editStepsListEl= document.getElementById('edit-steps-list')!;
const editTypeSelector = document.getElementById('edit-type-selector')!;

// Delete strategy modal
const modalDelete      = document.getElementById('modal-delete')      as HTMLDialogElement | null;
const btnDelete        = document.getElementById('btn-delete-strategy') as HTMLButtonElement | null;
const btnDeleteClose   = document.getElementById('modal-delete-close') as HTMLButtonElement | null;
const btnDeleteCancel  = document.getElementById('btn-delete-cancel')  as HTMLButtonElement | null;
const btnDeleteConfirm = document.getElementById('btn-delete-confirm') as HTMLButtonElement | null;
const deleteNameEl     = document.getElementById('modal-delete-name')!;
const deleteError      = document.getElementById('delete-error');

/**
 * API - strategy details
 */
async function fetchStrategy(): Promise<void> {
    try {
        const res = await apiFetch<Strategy>(`/strategies/${strategyId}`);

        if (!res.success || !res.data) {
            showDetailError(res.errorMessage ?? 'Strategy not found.');
            return;
        }

        currentStrategy = res.data;
        renderDetail(currentStrategy);
    } catch {
        showDetailError('Server connection error.');
    }
}

/**
 * API - fetch dictionaries for edit modal
 */
async function fetchDictionaries(): Promise<void> {
    try {
        const [mapsRes, typesRes] = await Promise.all([
            apiFetch<DictEntry[]>('/game-maps'),
            apiFetch<DictEntry[]>('/strategy-types'),
        ]);


        if (mapsRes.success && mapsRes.data) availableMaps = mapsRes.data;
        if (typesRes.success && typesRes.data) availableStrategyTypes = typesRes.data;
    } catch {
        showFormError(editError, "Failed to load game maps and strategy types");
    }

}

/**
 * API - load players for edit modal
 */
async function loadPlayersForEdit(teamId?: number): Promise<void> {
    availablePlayers = [];
    editSelectedPlayerIds = [];
    renderEditTags();

    const url = teamId
        ? `/players?status=active&team_id=${teamId}`
        : '/players?status=active';

    try {
        const res = await apiFetch<StrategyPlayer[]>(url);
        if (res.success && res.data) availablePlayers = res.data;
    } catch {
        showFormError(editError, "Failed to load players list");
    }


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
    loadingEl.hidden  = true;
    contentEl.hidden  = false;
    detailError.hidden = true;

    // Badges — keep icon, append text node after it
    mapBadge.innerHTML  = `<i class="fa-solid fa-map" style="font-size:0.65rem"></i> ${escapeHtml(s.mapIdent)}`;
    typeBadge.innerHTML = `<i class="fa-solid fa-crosshairs" style="font-size:0.65rem"></i> ${escapeHtml(TYPE_LABELS[s.strategyTypeIdent] ?? s.strategyTypeIdent)}`;

    // Title
    nameEl.textContent = escapeHtml(s.name);

    // Card 1 — Players
    playersCountEl.textContent = `${s.players.length}/5`;
    playersListEl.innerHTML    = s.players.length > 0
        ? s.players.map(p => `
            <li class="players-list__item">
                <span class="players-list__nickname">${escapeHtml(p.nickname)}</span>
                ${p.teamRole ? `<span class="badge badge--role">${escapeHtml(p.teamRole)}</span>` : ''}
            </li>
          `).join('')
        : '<li class="players-list__item players-list__item--empty">No players assigned</li>';

    // Card 2 — Overview
    descriptionEl.textContent = s.description;

    // Card 3 — Steps
    const sorted = [...s.stepsToDo].sort((a, b) => a.order - b.order);
    stepsListEl.innerHTML = sorted.length > 0
        ? sorted.map((step, i) => `
            <li class="steps-list__item">
                <span class="steps-list__num">${i + 1}.</span>
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
                            aria-label="Remove ${escapeHtml(p.nickname)}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
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
            <button type="button" class="steps-list__remove" data-index="${i}" aria-label="Remove step">
                <i class="fa-solid fa-xmark"></i>
            </button>
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
 * Populate map custom-select and pre-select current map
 */
function populateEditMapSelect(selectedMapId: number): void {
    editMapDropdown.innerHTML = availableMaps.map(m =>
        `<button type="button" class="custom-select__option" data-value="${m.id}">
            ${escapeHtml(m.ident.charAt(0) + m.ident.slice(1).toLowerCase())}
        </button>`
    ).join('');

    const selected = availableMaps.find(m => m.id === selectedMapId);
    if (selected && editMapTrigger.firstChild) {
        editMapTrigger.firstChild.textContent = escapeHtml(
            selected.ident.charAt(0) + selected.ident.slice(1).toLowerCase()
        );
        editMapInput.value = String(selected.id);
    }

    // Re-init so injected options get listeners
    initCustomSelects(modalEdit);
}

/**
 * Populate type selector and mark current type active
 */
function populateEditTypeSelector(selectedTypeId: number): void {
    editTypeSelector.innerHTML = availableStrategyTypes.map(t =>
        `<button type="button"
                 class="type-selector__option${t.id === selectedTypeId ? ' is-active' : ''}"
                 data-type-id="${t.id}"
                 aria-pressed="${t.id === selectedTypeId ? 'true' : 'false'}">
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
 * MODAL - Edit strategy
 */

/**
 * Edit modal - open and prefill
 */
async function openEditModal(s: Strategy): Promise<void> {
    hideError(editError);

    // Load dictionaries once
    if (availableMaps.length === 0 || availableStrategyTypes.length === 0) {
        await fetchDictionaries();
    }

    // Always re-populate (selected value changes per strategy)
    populateEditMapSelect(s.mapId);
    populateEditTypeSelector(s.strategyTypeId);

    if (isAdmin) {
        await loadPlayersForEdit(s.teamId);
    } else {
        if (availablePlayers.length === 0) await loadPlayersForEdit();
    }

    // Prefill scalar fields
    editName.value  = s.name;
    editDesc.value  = s.description;
    editSelectedTypeId = s.strategyTypeId;

    // Players — keep current strategy's players selected
    editSelectedPlayerIds = s.players.map(p => p.id);
    renderEditTags();

    // Steps
    const sorted = [...s.stepsToDo].sort((a, b) => a.order - b.order);
    editSteps = sorted.map(st => st.description);
    renderEditStepsList();

    editStepInput.value = '';
    modalEdit.showModal();
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

const closeEditModal = () => modalEdit.close();

/**
 * MODAL - Delete Strategy (COACH / ADMIN only)
 */
const closeDeleteModal = () => modalDelete?.close();

/**
 * Event listeners
 */

// Edit modal - always-present elements (modal always in DOM)
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

btnEditClose.addEventListener('click', closeEditModal);
btnEditCancel.addEventListener('click', closeEditModal);

btnEditSave.addEventListener('click', async () => {
    hideError(editError);

    const name  = editName.value.trim();
    const mapId = parseInt(editMapInput.value, 10);

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
        deleteNameEl.textContent = currentStrategy?.name ?? '';
        modalDelete.showModal();
    });
    btnDeleteClose?.addEventListener('click', closeDeleteModal);
    btnDeleteCancel?.addEventListener('click', closeDeleteModal);

    btnDeleteConfirm?.addEventListener('click', async () => {
        if (deleteError) hideError(deleteError);
        if (btnDeleteConfirm) btnDeleteConfirm.disabled = true;

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
await fetchStrategy();