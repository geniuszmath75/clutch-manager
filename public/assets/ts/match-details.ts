import {apiFetch} from "./helpers/fetch-helpers.js";
import {formatDate} from "./helpers/date-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";
import { initCustomSelects } from "./helpers/custom-select.js";

export {};

/**
 * Types
 */

interface GameMatch {
    id: number;
    teamId: number;
    teamName: string;
    opponentName: string;
    teamScore: number;
    opponentScore: number;
    mapIdent: string;
    mapId: number;
    gameModeIdent: string;
    gameModeId: number;
    duration: number;
    playedAt: string;
    result: 'WIN' | 'LOSS' | 'DRAW';
    createdAt: string;
    updatedAt: string;
}

interface PlayerStats {
    id: number;
    playerId: number;
    playerNickname: string;
    killsNumber: number;
    deathsNumber: number;
    assistsNumber: number;
    flashAssistsNumber: number;
    totalDamage: number;
    hsPercent: number;
    rkastNumber: number;
    kd: number;
    plusMinus: number;
}

type BasicPlayerStats = Omit<PlayerStats, "id" | "playerNickname" | "kd" | "plusMinus">;

interface DictEntry {
    id: number;
    ident: string;
}

/**
 * State
 */
const body      = document.body;
const matchId   = body.dataset['matchId'] ? parseInt(body.dataset['matchId'] ?? '0', 10) : null;
const canWrite  = body.dataset['canWrite'] === 'true';

let currentMatch: GameMatch | null = null;
let currentStats: PlayerStats[]    = [];
let mapsCache:    DictEntry[]      = [];
let modesCache:   DictEntry[]      = [];

/**
 * DOM elements
 */
const loadingEl    = document.getElementById('details-loading')!;
const errorEl      = document.getElementById('details-error')!;
const headerEl     = document.getElementById('match-header')!;
const statsSection = document.getElementById('stats-section')!;
const actionsEl    = document.getElementById('match-actions') as HTMLElement | null;

// Header fields
const metaResultBadge = document.getElementById('meta-result-badge')!;
const metaPlayedAt    = document.getElementById('meta-played-at')!;
const metaGameMode    = document.getElementById('meta-game-mode')!;
const metaScore       = document.getElementById('meta-score')!;
const metaTitle    = document.getElementById('meta-title')!;
const metaMap         = document.getElementById('meta-map')!;
const metaDuration    = document.getElementById('meta-duration')!;
const statsTbody      = document.getElementById('stats-tbody')!;

// Action buttons
const btnDelete       = document.getElementById('btn-delete-match')   as HTMLButtonElement | null;
const btnEdit         = document.getElementById('btn-edit-match')      as HTMLButtonElement | null;

// Delete modal
const modalConfirm    = document.getElementById('modal-confirm-delete') as HTMLDialogElement | null;
const btnDeleteCancel = document.getElementById('btn-delete-cancel')    as HTMLButtonElement | null;
const btnDeleteConfirm= document.getElementById('btn-delete-confirm')   as HTMLButtonElement | null;

// Edit modal
const modalEdit       = document.getElementById('modal-edit-match')    as HTMLDialogElement | null;
const btnEditClose    = document.getElementById('btn-edit-modal-close') as HTMLButtonElement | null;
const btnEditCancel   = document.getElementById('btn-edit-cancel')      as HTMLButtonElement | null;
const btnUpdate       = document.getElementById('btn-update-match')     as HTMLButtonElement | null;
const editModalError  = document.getElementById('edit-modal-error')!;
const editStatsTbody  = document.getElementById('edit-stats-tbody')!;

// Edit modal inputs
const editOpponent      = document.getElementById('edit-opponent')       as HTMLInputElement;
const editTeamScore     = document.getElementById('edit-team-score')     as HTMLInputElement;
const editOpponentScore = document.getElementById('edit-opponent-score') as HTMLInputElement;
const editDuration      = document.getElementById('edit-duration')       as HTMLInputElement;
const editPlayedAt      = document.getElementById('edit-played-at')      as HTMLInputElement;

// Custom-select hidden inputs + dropdowns + triggers
const editMapInput      = document.getElementById('edit-map')           as HTMLInputElement;
const editMapDropdown   = document.getElementById('edit-map-dropdown')  as HTMLElement;
const editMapTrigger    = document.getElementById('edit-map-trigger')   as HTMLButtonElement;
const editModeInput     = document.getElementById('edit-game-mode')     as HTMLInputElement;
const editModeDropdown  = document.getElementById('edit-mode-dropdown') as HTMLElement;
const editModeTrigger   = document.getElementById('edit-mode-trigger')  as HTMLButtonElement;

/**
 * API - match details with players' stats
 */
async function fetchMatch(): Promise<void> {
    loadingEl.hidden = false;
    headerEl.hidden = true;
    statsSection.hidden = true;
    if (actionsEl) actionsEl.hidden = true;

    try {
        const res = await apiFetch<{ match: GameMatch; stats: PlayerStats[] }>(`/matches/${matchId}`);

        loadingEl.hidden = true;

        if (!res.success) {
            showError(errorEl, res.errorMessage ?? 'Failed to fetch match.');
            return;
        }

        const payload  = res.data as { match: GameMatch; stats: PlayerStats[] };
        currentMatch   = payload.match;
        currentStats   = payload.stats;

        renderHeader(currentMatch);
        renderStats(currentStats, currentMatch);

        headerEl.hidden    = false;
        statsSection.hidden = false;
        if (actionsEl) actionsEl.hidden = false;
    } catch {
        loadingEl.hidden = true;
        showError(errorEl, 'Server connection error.');
    }
}

/**
 * API - update match
 */
async function updateMatchDetails(): Promise<void> {
    hideError(editModalError);

    const opponent   = editOpponent.value.trim();
    const teamScore  = parseInt(editTeamScore.value, 10);
    const oppScore   = parseInt(editOpponentScore.value, 10);
    const mapId      = parseInt(editMapInput.value, 10);
    const duration   = parseInt(editDuration.value, 10);
    const playedAt   = editPlayedAt.value;
    const gameModeId = parseInt(editModeInput.value, 10);

    if (!opponent || isNaN(teamScore) || isNaN(oppScore) || !mapId || !duration || !playedAt || !gameModeId) {
        showError(editModalError, 'Please fill in all fields.');
        return;
    }

    const stats = collectEditStats().map(s => ({
        player_id:            s.playerId,
        kills_number:         s.killsNumber,
        deaths_number:        s.deathsNumber,
        assists_number:       s.assistsNumber,
        flash_assists_number: s.flashAssistsNumber,
        total_damage:         s.totalDamage,
        hs_percent:           s.hsPercent,
        rkast_number:         s.rkastNumber,
    }));

    const payload = {
        opponent_name:  opponent,
        team_score:     teamScore,
        opponent_score: oppScore,
        map_id:         mapId,
        game_mode_id:   gameModeId,
        duration,
        played_at:      playedAt.replace('T', ' '),
        stats,
    };

    if (btnUpdate) btnUpdate.disabled = true;

    try {
        const res = await apiFetch<GameMatch>(`/matches/${matchId}`, {
            method: 'PUT',
            body:   JSON.stringify(payload),
        });

        if (!res.success) {
            showError(editModalError, res.errorMessage ?? 'Failed to update match.');
            return;
        }

        closeEditModal();
        await fetchMatch();
    } catch {
        showError(editModalError, 'Server connection error.');
    } finally {
        if (btnUpdate) btnUpdate.disabled = false;
    }
}

/**
 * API - delete match
 */
async function deleteMatch(): Promise<void> {
    if (btnDeleteConfirm) btnDeleteConfirm.disabled = true;

    try {
        const res = await apiFetch(`/matches/${matchId}`, { method: 'DELETE' });

        if (!res.success) {
            closeDeleteModal();
            showError(errorEl, res.errorMessage ?? 'Failed to delete match.');
            return;
        }

        window.location.href = '/dashboard/matches';
    } catch {
        closeDeleteModal();
        showError(errorEl, 'Server connection error.');
    } finally {
        if (btnDeleteConfirm) btnDeleteConfirm.disabled = false;
    }
}

/**
 * Render
 */
function renderHeader(m: GameMatch): void {
    // Result badge
    metaResultBadge.className = `badge badge--${m.result.toLowerCase()}`;
    metaResultBadge.textContent = m.result;

    // Date (Month DD, YYYY HH:mm)
    metaPlayedAt.textContent = formatDate(m.playedAt);

    // Game mode badge
    metaGameMode.textContent = m.gameModeIdent;

    // Score headline: "13 – 9"
    metaScore.textContent = `${m.teamScore}–${m.opponentScore}`;

    metaTitle.textContent = `${m.teamName} vs ${m.opponentName}`;

    // Map badge — keep the icon already in HTML, append text node
    metaMap.innerHTML = `<i class="fa-solid fa-map"></i> ${escapeHtml(m.mapIdent)}`;

    // Duration badge
    metaDuration.innerHTML = `<i class="fa-regular fa-clock"></i> ${m.duration} min`;
}

function renderStats(stats: PlayerStats[], m: GameMatch): void {
    if (stats.length === 0) {
        statsTbody.innerHTML = '<tr><td class="empty-state" colspan="7">No player stats recorded.</td></tr>';
        return;
    }

    // ADR = total_damage / total_rounds; total_rounds = team_score + opponent_score
    const totalRounds = m.teamScore + m.opponentScore || 1;

    statsTbody.innerHTML = stats.map(s => {
        const adr         = (s.totalDamage / totalRounds).toFixed(1);
        const kastPercent = ((s.rkastNumber / totalRounds) * 100).toFixed(1);

        let pmBadge: string;
        if (s.plusMinus > 0) {
            pmBadge = `<span class="stat-badge stat-badge--positive">+${s.plusMinus}</span>`;
        } else if (s.plusMinus < 0) {
            pmBadge = `<span class="stat-badge stat-badge--negative">${s.plusMinus}</span>`;
        } else {
            pmBadge = `<span class="stat-badge stat-badge--neutral">0</span>`;
        }

        return `
            <tr>
                <td>${escapeHtml(s.playerNickname)}</td>
                <td>${s.killsNumber} / ${s.deathsNumber} / ${s.assistsNumber}</td>
                <td>${pmBadge}</td>
                <td class="stat--adr">${adr}</td>
                <td>${kastPercent}%</td>
                <td>${s.hsPercent.toFixed(1)}%</td>
                <td>${s.flashAssistsNumber}</td>
            </tr>
        `;
    }).join('');
}

function renderEditStatsRows(stats: PlayerStats[], target: HTMLElement): void {
    target.innerHTML = stats.map(s => `
        <tr data-player-id="${s.playerId}">
            <td>${escapeHtml(s.playerNickname)}</td>
            <td><input type="number" name="kills_number"         min="0" value="${s.killsNumber}"></td>
            <td><input type="number" name="deaths_number"        min="0" value="${s.deathsNumber}"></td>
            <td><input type="number" name="assists_number"       min="0" value="${s.assistsNumber}"></td>
            <td><input type="number" name="flash_assists_number" min="0" value="${s.flashAssistsNumber}"></td>
            <td><input type="number" name="total_damage"         min="0" value="${s.totalDamage}"></td>
            <td><input type="number" name="hs_percent"           min="0" max="100" value="${s.hsPercent}"></td>
            <td><input type="number" name="rkast_number"         min="0" value="${s.rkastNumber}"></td>
        </tr>
    `).join('');
}

/**
 * Modal - edit match (ADMIN, COACH)
 */
async function openEditModal(): Promise<void> {
    if (!currentMatch || !modalEdit) return;
    hideError(editModalError);

    // Fetch maps + modes if not yet cached
    if (mapsCache.length === 0) {
        const [mRes, gRes] = await Promise.all([
            apiFetch<DictEntry[]>('/game-maps'),
            apiFetch<DictEntry[]>('/game-modes'),
        ]);
        mapsCache = mRes.data ?? [];
        modesCache = gRes.data ?? [];
    }

    // Pre-fill text / number inputs
    editOpponent.value      = currentMatch.opponentName;
    editTeamScore.value     = String(currentMatch.teamScore);
    editOpponentScore.value = String(currentMatch.opponentScore);
    editDuration.value      = String(currentMatch.duration);
    editPlayedAt.value      = currentMatch.playedAt.replace(' ', 'T').slice(0, 16);

    // Populate custom-selects with current values pre-selected
    populateCustomSelect(editMapDropdown, editMapTrigger, editMapInput, mapsCache,  currentMatch.mapId);
    populateCustomSelect(editModeDropdown, editModeTrigger, editModeInput, modesCache, currentMatch.gameModeId);

    // Re-init only the edit modal so new dropdown buttons get listeners
    initCustomSelects(modalEdit);

    // Pre-fill stats rows
    renderEditStatsRows(currentStats, editStatsTbody);

    modalEdit.showModal();
    modalEdit.hidden = false;
}

function closeEditModal(): void {
    modalEdit?.close();
    hideError(editModalError);
}

function closeDeleteModal(): void {
    modalConfirm?.close();
}

function collectEditStats(): BasicPlayerStats[] {
    return currentStats.map(s => {
        const row = editStatsTbody.querySelector<HTMLTableRowElement>(`tr[data-player-id="${s.playerId}"]`)!;
        const get = (name: string): number =>
            parseFloat(row.querySelector<HTMLInputElement>(`[name="${name}"]`)?.value ?? '0') || 0;

        return {
            playerId: s.playerId,
            killsNumber: get('kills_number'),
            deathsNumber: get('deaths_number'),
            assistsNumber: get('assists_number'),
            flashAssistsNumber: get('flash_assists_number'),
            totalDamage: get('total_damage'),
            hsPercent: get('hs_percent'),
            rkastNumber: get('rkast_number'),
        };
    });
}

/**
 * Event listeners
 */
if (canWrite) {
    btnDelete?.addEventListener('click', () => modalConfirm?.showModal());
    btnDeleteCancel?.addEventListener('click', closeDeleteModal);
    btnDeleteConfirm?.addEventListener('click', await deleteMatch);

    btnEdit?.addEventListener('click', openEditModal);
    btnEditClose?.addEventListener('click', closeEditModal);
    btnEditCancel?.addEventListener('click', closeEditModal);
    btnUpdate?.addEventListener('click', await updateMatchDetails);
}

/**
 * UI helpers
 */

/**
 * Injects option buttons into the dropdown div and sets the trigger label + hidden input value.
 */
function populateCustomSelect(
    dropdown: HTMLElement,
    trigger: HTMLButtonElement,
    hiddenInput: HTMLInputElement,
    entries: DictEntry[],
    selectedId: number
): void {
    dropdown.innerHTML = entries.map(e =>
        `<button type="button" class="custom-select__option" data-value="${e.id}">${escapeHtml(e.ident)}</button>`
    ).join('');

    const selected = entries.find(e => e.id === selectedId);
    if (selected && trigger.firstChild) {
        trigger.firstChild.textContent = escapeHtml(selected.ident);
        hiddenInput.value = String(selected.id);
    }
}

function hideError(el: HTMLElement): void {
    el.textContent = '';
    el.hidden = true;
    el.classList.remove("state-error--hidden");
}

function showError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden = false;
    el.classList.add("state-error--hidden");
}

/**
 * Init
 */
await fetchMatch();