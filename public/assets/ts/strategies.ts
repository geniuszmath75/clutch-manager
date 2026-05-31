import {apiFetch} from "./helpers/fetch-helpers.js";
import type {PaginationMeta} from "./helpers/fetch-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";
import { renderPagination, bindPaginationButtons } from "./helpers/pagination.js";
import { initCustomSelects }   from "./helpers/custom-select.js";

export {};

/**
 * Types
 */
interface StrategyPlayer {
    id: number;
    nickname: string;
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
    id: number,
    name: string
}

/**
 * State
 */
const body = document.body;
const systemRole = body.dataset['systemRole'] ?? '';
const canWrite = systemRole === 'COACH' || systemRole === 'ADMIN';
const isAdmin = systemRole === 'ADMIN';

let currentPage = 1;
let currentTypeId = '';
const PAGE_SIZE = 5;
let currentMeta: PaginationMeta | null = null;

// Dictionaries (loaded once at init)
let availableStrategyTypes: DictEntry[] = [];
let availableMaps: DictEntry[] = [];

// Add-modal state
let availablePlayers: StrategyPlayer[] = [];
let selectedPlayerIds: number[] = [];
let addSteps: string[] = [];
let selectedTypeId: number | null = null;
// ADMIN only: selected team before loading players
let selectedTeamId: number | null = null;
let availableTeams: Team[] = [];

const TYPE_LABELS: Record<string, string> = {
    ATTACK: 'Attack',
    DEFENSE: 'Defense',
    ECO: 'Eco',
    DEFAULT: 'Default',
};

/**
 * DOM elements
 */
const grid        = document.getElementById('strategy-grid')!;
const loadingState = document.getElementById('strategies-loading')!;
const emptyState  = document.getElementById('empty-state')!;
const errorState  = document.getElementById('error-state')!;
const tabs        = document.querySelectorAll<HTMLButtonElement>('.strategy-tabs__tab');

// Add strategy modal
const modalAdd       = document.getElementById('modal-add')          as HTMLDialogElement | null;
const btnOpenAdd     = document.getElementById('btn-open-add')        as HTMLButtonElement | null;
const btnAddClose    = document.getElementById('modal-add-close')     as HTMLButtonElement | null;
const btnAddCancel   = document.getElementById('btn-add-cancel')      as HTMLButtonElement | null;
const btnAddSave     = document.getElementById('btn-add-save')        as HTMLButtonElement | null;
const addName        = document.getElementById('add-name')            as HTMLInputElement;
const addMapInput    = document.getElementById('add-map')             as HTMLInputElement;        // hidden input
const addMapDropdown = document.getElementById('add-map-dropdown')    as HTMLElement;
const addMapTrigger  = document.getElementById('add-map-trigger')     as HTMLButtonElement;
const addDesc        = document.getElementById('add-description')     as HTMLTextAreaElement;
const addError       = document.getElementById('add-error')!;
const addTagsList    = document.getElementById('add-tags-list')!;
const addDropdown    = document.getElementById('add-player-dropdown') as HTMLDivElement;
const btnAddPlayer   = document.getElementById('btn-add-player-tag')  as HTMLButtonElement;
const addStepInput   = document.getElementById('add-step-input')      as HTMLInputElement;
const btnAddStep     = document.getElementById('btn-add-step')        as HTMLButtonElement;
const addStepsList   = document.getElementById('add-steps-list')!;
const typeSelectorEl = document.getElementById('add-type-selector')!;

// ADMIN-only team selector (injected dynamically)
let addTeamInput: HTMLInputElement | null = null;

/**
 * API - strategies list
 */
async function fetchStrategies(page: number, typeId: string = ''): Promise<void> {
    showLoading();

    const params = new URLSearchParams({page: String(page), pageSize: String(PAGE_SIZE)});
    if (typeId !== '') params.set('strategy_type_id', typeId);

    try {
        const res = await apiFetch<Strategy[]>(`/strategies?${params.toString()}`);

        if (!res.success || !res.data) {
            showGlobalError(res.errorMessage ?? 'Failed to load strategies.');
            return;
        }

        currentMeta = res.meta ?? null;
        currentPage = page;

        renderGrid(res.data);
        renderPagination(currentMeta, "strategies");
    } catch {
        showGlobalError('Server connection error');
    }


}

/**
 * API - load dictionaries (strategy types + maps)
 */
async function loadDictionaries(): Promise<void> {
    try {
        const [typesRes, mapsRes] = await Promise.all([
            apiFetch<DictEntry[]>('/strategy-types'),
            apiFetch<DictEntry[]>('/game-maps'),
        ]);
        if (typesRes.success && typesRes.data) availableStrategyTypes = typesRes.data;
        if (mapsRes.success && mapsRes.data) availableMaps = mapsRes.data;
    } catch {
        showFormError(addError, "Failed to load strategy types and game maps.");
    }

}

/**
 * API - load teams (ADMIN only)
 */
async function loadTeams(): Promise<void> {
    try {
        if (availableTeams.length > 0) return;
        const res = await apiFetch<Team[]>('/teams');

        if (res.success && res.data) availableTeams = res.data;
    } catch {
        showFormError(addError, "Failed to load team list.");
    }
}


/**
 * API - load players for a given team
 */
async function loadPlayersForTeam(teamId: number): Promise<void> {
    availablePlayers = [];
    selectedPlayerIds = [];
    renderAddTags();

    try {
        const res = await apiFetch<StrategyPlayer[]>(`/players?status=active&team_id=${teamId}`);
        if (res.success && res.data) availablePlayers = res.data;
    } catch {
        showFormError(addError, "Failed to load team players");
    }

}

/**
 * API - add new strategy
 */
async function createStrategy(data: object): Promise<void> {
    const res = await apiFetch<Strategy>('/strategies', {
        method: 'POST',
        body: JSON.stringify(data),
    });
    if (!res.success) {
        throw new Error(res.errorMessage ?? 'Failed to create strategy.');
    }
}

/**
 * API - load team players
 */
async function loadAvailablePlayers(): Promise<void> {
    try {
        const res = await apiFetch<StrategyPlayer[]>('/players?pageSize=6&&status=active');
        if (res.success && res.data) {
            availablePlayers = res.data;
        }
    } catch {
        showFormError(addError, "Failed to load available players");
    }

}

/**
 * Render
 */
function renderGrid(items: Strategy[]): void {
    grid.innerHTML = '';
    loadingState.hidden = true;

    if (items.length === 0) {
        emptyState.hidden = false;
        return;
    }

    emptyState.hidden = true;

    items.forEach(s => {
        const card = document.createElement('article');
        card.className = 'strategy-card';

        const updatedAgo = timeAgo(s.updatedAt);
        const playersHtml = s.players.length > 0
            ? s.players.map(p => `<span class="player-chip">${escapeHtml(p.nickname)}</span>`).join('')
            : '<span class="player-chip player-chip--empty">No players assigned</span>';

        card.innerHTML = `
            <div class="strategy-card__map-badge">
                <i class="fa-solid fa-map" style="font-size:0.65rem"></i>
                ${escapeHtml(s.mapIdent)}
            </div>
            <div class="strategy-card__body">
                <h3 class="strategy-card__name">${escapeHtml(s.name)}</h3>
                <p class="strategy-card__desc">${escapeHtml(s.description)}</p>
                <p class="strategy-card__players-label">Assigned Players</p>
                <div class="strategy-card__players">${playersHtml}</div>
            </div>
            <hr class="strategy-card__divider">
            <div class="strategy-card__footer">
                <span class="strategy-card__updated">
                    <i class="fa-regular fa-clock"></i>
                    Updated ${escapeHtml(updatedAgo)}
                </span>
                <a class="strategy-card__link" href="/dashboard/strategies/${s.id}">
                    View Details <i class="fa-solid fa-arrow-right" style="font-size:0.75rem"></i>
                </a>
            </div>
        `;

        grid.appendChild(card);
    });
}

/**
 * Render - player tags (add modal)
 */
function renderAddTags(): void {
    if (!addTagsList) return;

    addTagsList.innerHTML = selectedPlayerIds.map(id => {
        const p = availablePlayers.find(pl => pl.id === id);
        if (!p) return '';
        return `<span class="player-tag">
                    ${escapeHtml(p.nickname)}
                    <button type="button" class="player-tag__remove" data-id="${p.id}"
                            aria-label="Remove ${escapeHtml(p.nickname)}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </span>`;
    }).join('');

    addTagsList.querySelectorAll<HTMLButtonElement>('.player-tag__remove').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset['id'] ?? '0', 10);
            selectedPlayerIds = selectedPlayerIds.filter(x => x !== id);
            renderAddTags();
        });
    });
}

/**
 * Render - steps list (add modal)
 */
function renderAddStepsList(): void {
    if (!addStepsList) return;

    addStepsList.innerHTML = addSteps.map((s, i) => `
        <li class="steps-list__item">
            <span class="steps-list__num">${i + 1}.</span>
            <span class="steps-list__text">${escapeHtml(s)}</span>
            <button type="button" class="steps-list__remove" data-index="${i}" aria-label="Remove step">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </li>
    `).join('');

    addStepsList.querySelectorAll<HTMLButtonElement>('.steps-list__remove').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = parseInt(btn.dataset['index'] ?? '0', 10);
            addSteps.splice(idx, 1);
            renderAddStepsList();
        });
    });
}

/**
 * Render - player dropdown (add modal)
 */
function renderAddPlayerDropdown(): void {
    if (!addDropdown) return;

    const available = availablePlayers.filter(p => !selectedPlayerIds.includes(p.id));

    if (available.length === 0) {
        addDropdown.innerHTML = '<p class="dropdown-empty">All players assigned</p>';
        return;
    }

    addDropdown.innerHTML = available.map(p =>
        `<button type="button" class="dropdown-option" data-id="${p.id}">${escapeHtml(p.nickname)}</button>`
    ).join('');

    addDropdown.querySelectorAll<HTMLButtonElement>('.dropdown-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset['id'] ?? '0', 10);
            selectedPlayerIds.push(id);
            renderAddTags();
            addDropdown.hidden = true;
        });
    });
}

/**
 * Populate map custom-select from loaded dictionaries
 */
function populateAddMapSelect(): void {
    addMapDropdown.innerHTML = availableMaps.map(m =>
        `<button type="button" class="custom-select__option" data-value="${m.id}">
            ${escapeHtml(m.ident.charAt(0) + m.ident.slice(1).toLowerCase())}
        </button>`
    ).join('');

    // Re-init the map custom-select after injecting options
    if (modalAdd) initCustomSelects(modalAdd);
}

/**
 * Populate type selector from loaded dictionaries
 */
function populateAddTypeSelector(): void {
    if (!typeSelectorEl) return;

    typeSelectorEl.innerHTML = availableStrategyTypes.map(t =>
        `<button type="button"
                 class="type-selector__option"
                 data-type-id="${t.id}"
                 aria-pressed="false">
            ${escapeHtml(TYPE_LABELS[t.ident] ?? t.ident)}
         </button>`
    ).join('');

    typeSelectorEl.addEventListener('click', (e) => {
        const btn = (e.target as HTMLElement).closest<HTMLButtonElement>('.type-selector__option');
        if (!btn) return;

        typeSelectorEl.querySelectorAll<HTMLButtonElement>('.type-selector__option').forEach(b => {
            b.setAttribute('aria-pressed', 'false');
            b.classList.remove('is-active');
        });

        btn.setAttribute('aria-pressed', 'true');
        btn.classList.add('is-active');
        selectedTypeId = parseInt(btn.dataset['typeId'] ?? '0', 10);
    });
}

/**
 * ADMIN — inject team selector into modal and wire player reload on change
 * The select is inserted once, before the player-tag row.
 */
function ensureAdminTeamSelector(): void {
    if (!modalAdd) return;

    if (!addTeamInput) {
        // First call — build the custom-select and insert it
        const playerField = document.getElementById('player-field');
        if (!playerField) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'form-field';
        wrapper.id = 'admin-team-field';
        wrapper.innerHTML = `
            <label class="form-field__label">Team <span class="required">*</span></label>
            <div class="custom-select" id="add-team-select">
                <input type="hidden" id="add-team" name="team_id">
                <button type="button" id="add-team-trigger" class="custom-select__trigger" aria-expanded="false">
                    Select team…
                    <span class="custom-select__arrow">
                        <i class="fa-solid fa-chevron-down"></i>
                    </span>
                </button>
                <div id="add-team-dropdown" class="custom-select__dropdown"></div>
            </div>
        `;

        playerField.parentElement!.insertBefore(wrapper, playerField);
        addTeamInput = wrapper.querySelector<HTMLInputElement>('#add-team')!;

        // Listen for changes on the hidden input (dispatched by custom-select.ts)
        addTeamInput.addEventListener('change', async () => {
            const teamId = parseInt(addTeamInput!.value, 10);
            if (!teamId) {
                availablePlayers  = [];
                selectedPlayerIds = [];
                renderAddTags();
                selectedTeamId = null;
                return;
            }
            selectedTeamId = teamId;
            await loadPlayersForTeam(teamId);
            if (addDropdown) addDropdown.hidden = true;
        });
    }

    // Refresh options (teams may have changed)
    const teamDropdown = document.getElementById('add-team-dropdown')!;
    const teamTrigger  = document.getElementById('add-team-trigger') as HTMLButtonElement;

    teamDropdown.innerHTML = availableTeams.map(t =>
        `<button type="button" class="custom-select__option" data-value="${t.id}">${escapeHtml(t.name)}</button>`
    ).join('');

    // Reset trigger label
    if (teamTrigger.firstChild) teamTrigger.firstChild.textContent = 'Select team…';
    addTeamInput.value = '';
    selectedTeamId = null;

    // Re-init the team custom-select after refreshing options
    initCustomSelects(modalAdd!);
}

/**
* MODAL — reset state
*/
function resetAddModal(): void {
    addName.value      = '';
    addMapInput.value  = '';
    addDesc.value      = '';
    addStepInput.value = '';
    addError.hidden    = true;
    addError.textContent = '';

    // Reset map trigger label
    if (addMapTrigger.firstChild) addMapTrigger.firstChild.textContent = 'Select map';

    selectedPlayerIds = [];
    addSteps          = [];
    selectedTypeId    = null;
    availablePlayers  = [];

    renderAddTags();
    renderAddStepsList();

    typeSelectorEl.querySelectorAll<HTMLButtonElement>('.type-selector__option').forEach(btn => {
        btn.setAttribute('aria-pressed', 'false');
        btn.classList.remove('is-active');
    });

    if (isAdmin && addTeamInput) {
        addTeamInput.value = '';
        selectedTeamId     = null;
        const teamTrigger  = document.getElementById('add-team-trigger') as HTMLButtonElement | null;
        if (teamTrigger?.firstChild) teamTrigger.firstChild.textContent = 'Select team…';
    }
}

/**
 * Event listeners
 */
tabs.forEach(tab => {
    tab.addEventListener('click', async () => {
        tabs.forEach(t => {
            t.classList.remove('is-active');
            t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('is-active');
        tab.setAttribute('aria-selected', 'true');
        currentTypeId = tab.dataset['type'] ?? '';
        currentPage = 1;
        await fetchStrategies(currentPage, currentTypeId);
    });
});

// Pagination wired via shared helper
bindPaginationButtons(
    (page) => fetchStrategies(page, currentTypeId),
    () => currentPage,
    () => currentMeta
);

if (canWrite && modalAdd && btnOpenAdd && btnAddClose && btnAddCancel && btnAddSave) {
    // Player tag dropdown toggle
    btnAddPlayer.addEventListener('click', () => {
        // ADMIN must select a team first
        if (isAdmin && !selectedTeamId) {
            if (addError) showFormError(addError, 'Please select a team before adding players.');
            return;
        }

        if (addDropdown.hidden) {
            renderAddPlayerDropdown();
            addDropdown.hidden = false;
        } else {
            addDropdown.hidden = true;
        }
    });

    // Close player dropdown on outside click
    document.addEventListener('click', (e) => {
        if (
            addDropdown &&
            !btnAddPlayer.contains(e.target as Node) &&
            !addDropdown.contains(e.target as Node)
        ) {
            addDropdown.hidden = true;
        }
    });

    // Steps
    btnAddStep.addEventListener('click', handleAddStep);
    addStepInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAddStep();
        }
    });

    // Open modal
    btnOpenAdd.addEventListener('click', async () => {
        resetAddModal();

        // Load dictionaries once
        if (availableStrategyTypes.length === 0) {
            await loadDictionaries();
            populateAddMapSelect();
            populateAddTypeSelector();
        }

        if (isAdmin) {
            await loadTeams();
            ensureAdminTeamSelector();
        } else {
            // COACH: load own team players once
            if (availablePlayers.length === 0) await loadAvailablePlayers();
        }

        modalAdd.showModal();
    });

    const closeAddModal = () => modalAdd.close();
    btnAddClose.addEventListener('click', closeAddModal);
    btnAddCancel.addEventListener('click', closeAddModal);
    modalAdd.addEventListener('click', (e) => {
        if (e.target === modalAdd) closeAddModal();
    });

    // Save
    btnAddSave.addEventListener('click', async () => {
        hideError(addError);

        const name = addName.value.trim();
        const mapId = parseInt(addMapInput.value, 10);

        if (isAdmin && !selectedTeamId) {
            showFormError(addError, 'Please select a team.');
            return;
        }
        if (!name) {
            showFormError(addError, 'Strategy name is required.');
            return;
        }
        if (!mapId) {
            showFormError(addError, 'Please select a map.');
            return;
        }
        if (!selectedTypeId) {
            showFormError(addError, 'Please select a strategy type.');
            return;
        }
        if (!addDesc.value.trim()) {
            showFormError(addError, 'Description is required.');
            return;
        }

        const payload: Record<string, unknown> = {
            name,
            map_id: mapId,
            strategy_type_id: selectedTypeId,
            description: addDesc.value.trim(),
            steps_to_do: addSteps.map((s, i) => ({order: i + 1, description: s})),
            player_ids: selectedPlayerIds,
        };

        // ADMIN must send team_id; COACH's team_id is derived server-side from session
        if (isAdmin) payload['team_id'] = selectedTeamId;

        btnAddSave.disabled = true;
        try {
            await createStrategy(payload);
            closeAddModal();
            currentPage = 1;
            await fetchStrategies(currentPage, currentTypeId);
        } catch (err: unknown) {
            showFormError(addError, err instanceof Error ? err.message : 'An error occurred.');
        } finally {
            btnAddSave.disabled = false;
        }
    });
}

/**
 * UI helpers
 */
function showLoading(): void {
    loadingState.hidden = false;
    errorState.hidden = true;
    emptyState.hidden = true;
    grid.innerHTML = '';
}

function showGlobalError(msg: string): void {
    errorState.textContent = msg;
    errorState.hidden = false;
    loadingState.hidden = true;
    grid.innerHTML = '';
}

function showFormError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden = false;
}

function hideError(el: HTMLElement): void {
    el.textContent = '';
    el.hidden = true;
}

function handleAddStep(): void {
    const val = addStepInput.value.trim();
    if (!val) return;
    addSteps.push(val);
    addStepInput.value = '';
    renderAddStepsList();
}

function timeAgo(dateStr: string): string {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)} days ago`;
}

/**
 * Init
 */
await fetchStrategies(1);