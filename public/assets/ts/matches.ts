import {apiFetch} from "./helpers/fetch-helpers.js";
import type {PaginationMeta} from "./helpers/fetch-helpers.js";
import {formatDate} from "./helpers/date-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";

export {};

/**
 * Types
 */
interface GameMatch {
    id: number;
    teamId: number;
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
}

interface PlayerStats {
    playerId: number;
    playerNickname: string;
    killsNumber: number;
    deathsNumber: number;
    assistsNumber: number;
    flashAssistsNumber: number;
    totalDamage: number;
    hsPercent: number;
    rkastNumber: number;
}

interface Player {
    id: number;
    nickname: string;
    teamId: number | null;
}

/**
 * State
 */
const body = document.body;
const systemRole = body.dataset['systemRole'] ?? '';
const sessionTeamId = body.dataset['teamId'] ? parseInt(body.dataset['teamId'] ?? '0', 10) : null;
const canWrite = systemRole === 'COACH' || systemRole === 'ADMIN';

let currentPage = 1;
let currentMeta: PaginationMeta | null = null;
const PAGE_SIZE = 5;
let filterDebounceTimer: ReturnType<typeof setTimeout> | null = null;
// Stats row state: array parallel to loaded players
let statsPlayers: Player[] = [];

/**
 * DOM elements
 */
const tbody = document.getElementById('matches-tbody')!;
const paginationEl = document.getElementById('pagination')!;
const paginInfo = document.getElementById('pagination-info')!;
const btnPrev = document.getElementById('btn-prev')! as HTMLButtonElement;
const btnNext = document.getElementById('btn-next')! as HTMLButtonElement;
const errorBanner = document.getElementById('error-banner')!;
const filterOpponent = document.getElementById('filter-opponent') as HTMLInputElement;
const filterMap = document.getElementById('filter-map') as HTMLSelectElement;
const filterResult = document.getElementById('filter-result') as HTMLSelectElement;

// Add match modal
const modal = document.getElementById('modal-add-match') as HTMLDialogElement;
const btnOpen = document.getElementById('btn-add-match') as HTMLButtonElement;
const btnClose = document.getElementById('btn-modal-close') as HTMLButtonElement;
const btnCancel = document.getElementById('btn-cancel') as HTMLButtonElement;
const btnSave = document.getElementById('btn-save-match') as HTMLButtonElement;
const modalError = document.getElementById('modal-error')!;
const statsTbody = document.getElementById('stats-tbody')!;
const teamSelect = document.getElementById('match-team-id') as HTMLSelectElement | null;

// Modal - match details
const matchOpponent = document.getElementById('match-opponent') as HTMLInputElement;
const matchTeamScore = document.getElementById('match-team-score') as HTMLInputElement;
const matchOpponentScore = document.getElementById('match-opponent-score') as HTMLInputElement;
const matchMap = document.getElementById('match-map') as HTMLSelectElement;
const matchDuration = document.getElementById('match-duration') as HTMLInputElement;
const matchPlayedAt = document.getElementById('match-played-at') as HTMLInputElement;
const matchGameMode = document.getElementById('match-game-mode') as HTMLSelectElement;

/**
 * API - matches list
 */
async function fetchMatches(page: number): Promise<void> {
    currentPage = page;

    const params = new URLSearchParams({
        page: String(page),
        pageSize: String(PAGE_SIZE),
    });

    if (filterMap.value) params.set('map_ident', filterMap.value);
    if (filterResult.value) params.set('result', filterResult.value);


    try {
        const res = await apiFetch<GameMatch[]>(`/matches?${params}`);

        if (!res.success) {
            showError(errorBanner, res.errorMessage ?? 'Fetching matches error.');
            return;
        }

        hideError(errorBanner);

        const matches = res.data ?? [];
        currentMeta = res.meta ?? null;

        // Client-side opponent filter (text search — avoid extra round-trips)
        const opponentQuery = filterOpponent.value.trim().toLowerCase();
        const filtered = opponentQuery
            ? matches.filter(m => m.opponentName.toLowerCase().includes(opponentQuery))
            : matches;

        renderTable(filtered);
        renderPagination(res.meta ?? null);
    } catch {
        showError(errorBanner, "Server connection error");
    }

}

/**
 * API - team players list
 */
async function fetchTeamPlayers(teamId: number): Promise<void> {
    statsTbody.innerHTML = '<tr><td colspan="8" class="table__empty">Loading...</td></tr>';

    try {
        const res = await apiFetch<Player[]>(`/players?team_id=${teamId}&is_active=true`);

        if (!res.success) {
            statsTbody.innerHTML = '<tr><td colspan="8" class="table__empty">Failed to load players</td></tr>';
            return;
        }

        statsPlayers = res.data ?? [];

        if (statsPlayers.length === 0) {
            statsTbody.innerHTML = '<tr><td colspan="8" class="table__empty">No active players found</td></tr>';
            return;
        }

        renderStatsRows(statsPlayers, statsTbody);
    } catch {
        showError(statsTbody, "Server connection error");
    }

}

/**
 * API - add new match
 */
async function addMatch(): Promise<void> {
    hideError(modalError);

    const teamId = systemRole === 'ADMIN'
        ? parseInt(teamSelect?.value ?? '0', 10)
        : sessionTeamId;

    if (!teamId) {
        showError(modalError, 'Please select a team.');
        return;
    }

    const opponent = matchOpponent.value.trim();
    const teamScore = parseInt(matchTeamScore.value, 10);
    const oppScore = parseInt(matchOpponentScore.value, 10);
    const mapId = parseInt(matchMap.value, 10);
    const duration = parseInt(matchDuration.value, 10);
    const playedAt = matchPlayedAt.value;
    const gameModeId = parseInt(matchGameMode.value, 10);

    if (!opponent || isNaN(teamScore) || isNaN(oppScore) || !mapId || !duration || !playedAt || !gameModeId) {
        showError(modalError, 'Please fill in all match info fields.');
        return;
    }

    if (statsPlayers.length === 0) {
        showError(modalError, 'No players loaded. Select a team first.');
        return;
    }

    const stats = collectStatsRows(statsTbody, statsPlayers).map(s => ({
        player_id: s.playerId,
        kills_number: s.killsNumber,
        deaths_number: s.deathsNumber,
        assists_number: s.assistsNumber,
        flash_assists_number: s.flashAssistsNumber,
        total_damage: s.totalDamage,
        hs_percent: s.hsPercent,
        rkast_number: s.rkastNumber,
    }));

    const payload = {
        team_id: teamId,
        opponent_name: opponent,
        team_score: teamScore,
        opponent_score: oppScore,
        map_id: mapId,
        game_mode_id: gameModeId,
        duration,
        played_at: playedAt.replace('T', ' '),
        stats,
    };

    btnSave.disabled = true;

    try {
        const res = await apiFetch<GameMatch>('/matches', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        if (!res.success) {
            showError(modalError, res.errorMessage ?? 'Failed to save match.');
            return;
        }

        closeAddMatchModal();
        fetchMatches(currentPage);
    } catch {
        showError(modalError, 'Network error. Please try again.');
    } finally {
        btnSave.disabled = false;
    }
}

/**
 * Render
 */
function renderTable(matches: GameMatch[]): void {
    if (matches.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="table__empty">No matches found.</td></tr>';
        return;
    }

    tbody.innerHTML = matches.map(m => {
        const resultClass = `badge badge--${m.result.toLowerCase()}`;
        const date = formatDate(m.playedAt);

        return `
            <tr>
                <td>${m.id}</td>
                <td>${escapeHtml(m.opponentName)}</td>
                <td>${escapeHtml(m.mapIdent)}</td>
                <td>${m.teamScore} : ${m.opponentScore}</td>
                <td><span class="${resultClass}">${m.result}</span></td>
                <td>${date}</td>
                <td>
                    <a href="/dashboard/matches/${m.id}" class="btn btn--sm btn--secondary">Details</a>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Render - pagination
 */
function renderPagination(meta: PaginationMeta | null): void {
    if (!meta) {
        paginationEl.hidden = true;
        return;
    }

    paginationEl.hidden = false;
    paginInfo.textContent = `Showing ${meta.page} of ${meta.totalPages} (${meta.total} matches)`;

    btnPrev.disabled = meta.page <= 1;
    btnNext.disabled = meta.page >= meta.totalPages;
}

function renderStatsRows(players: Player[], target: HTMLElement): void {
    target.innerHTML = players.map((p, i) => `
        <tr data-player-id="${p.id}">
            <td>${escapeHtml(p.nickname)}</td>
            <td><input class="input input--sm" type="number" name="kills_number"         data-i="${i}" min="0" value="0"></td>
            <td><input class="input input--sm" type="number" name="deaths_number"        data-i="${i}" min="0" value="0"></td>
            <td><input class="input input--sm" type="number" name="assists_number"       data-i="${i}" min="0" value="0"></td>
            <td><input class="input input--sm" type="number" name="flash_assists_number" data-i="${i}" min="0" value="0"></td>
            <td><input class="input input--sm" type="number" name="total_damage"         data-i="${i}" min="0" value="0"></td>
            <td><input class="input input--sm" type="number" name="hs_percent"           data-i="${i}" min="0" max="100" value="0"></td>
            <td><input class="input input--sm" type="number" name="rkast_number"         data-i="${i}" min="0" value="0"></td>
        </tr>
    `).join('');
}

/**
 * Filters
 */
async function onFilterChange(): Promise<void> {
    await fetchMatches(1);
}

function onOpponentInput(): void {
    if (filterDebounceTimer) clearTimeout(filterDebounceTimer);
    filterDebounceTimer = setTimeout(() => fetchMatches(1), 300);
}

/**
 * Modal - add match (ADMIN, COACH)
 */

async function openAddMatchModal(): Promise<void> {
    modal.showModal();
    modal.hidden = false;
    hideError(modalError);

    if (systemRole === 'COACH' && sessionTeamId) {
        await fetchTeamPlayers(sessionTeamId);
    }
    // ADMIN: wait for team selection
}

function closeAddMatchModal(): void {
    hideError(modalError);
    statsTbody.innerHTML = '<tr><td colspan="8" class="table__empty">Select a team to load players.</td></tr>';
    statsPlayers = [];
    modal.close();
}

function collectStatsRows(target: HTMLElement, players: Player[]): PlayerStats[] {
    return players.map((p) => {
        const row = target.querySelector<HTMLTableRowElement>(`tr[data-player-id="${p.id}"]`)!;

        const get = (name: string): number => parseFloat((row.querySelector<HTMLInputElement>(`[name="${name}"]`)?.value ?? '0')) || 0;

        return {
            playerId: p.id,
            playerNickname: p.nickname,
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

// Pagination
btnPrev.addEventListener('click', async () => {
    if (currentPage > 1) {
        await fetchMatches(currentPage - 1);
    }
});

btnNext.addEventListener('click', async () => {
    if (currentMeta && currentPage < currentMeta.totalPages) {
        await fetchMatches(currentPage + 1);
    }
});

filterOpponent.addEventListener('input', onOpponentInput);
filterMap.addEventListener('change', async () => {
    await onFilterChange();
});
filterResult.addEventListener('change', onFilterChange);

// ADMIN — reload players when team changes
teamSelect?.addEventListener('change', async () => {
    const tid = parseInt(teamSelect.value, 10);
    if (tid) await fetchTeamPlayers(tid);
});

if (canWrite) {
    btnOpen.addEventListener('click', openAddMatchModal);
    btnClose.addEventListener('click', closeAddMatchModal);
    btnCancel.addEventListener('click', closeAddMatchModal);
    document.getElementById('modal-overlay')?.addEventListener('click', closeAddMatchModal);

    btnSave.addEventListener('click', async () => {
        await addMatch();
    });
}

/**
 * UI helpers
 */

function showError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden = false;
}

function hideError(el: HTMLElement): void {
    el.textContent = '';
    el.hidden = true;
}

/**
 * Init
 */
await fetchMatches(1);