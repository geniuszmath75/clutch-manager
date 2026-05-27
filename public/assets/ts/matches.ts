import {apiFetch} from "./helpers/fetch-helpers.js";
import type {PaginationMeta} from "./helpers/fetch-helpers.js";
import {formatDate} from "./helpers/date-helpers.js";
import {escapeHtml} from "./helpers/string-helpers.js";
import { renderPagination, bindPaginationButtons } from "./helpers/pagination.js";

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

let currentPage: number            = 1;
let currentMeta: PaginationMeta | null = null;
const PAGE_SIZE = 5;
let filterDebounceTimer: ReturnType<typeof setTimeout> | null = null;
let statsPlayers: Player[] = [];

/**
 * DOM elements
 */
const tbody        = document.getElementById('matches-tbody')!;
const errorBanner  = document.getElementById('error-banner')!;
const filterOpponent = document.getElementById('filter-opponent') as HTMLInputElement;
const filterMap      = document.getElementById('filter-map')      as HTMLSelectElement;
const filterResult   = document.getElementById('filter-result')   as HTMLSelectElement;

// Add match modal
const modal      = document.getElementById('modal-add-match')  as HTMLDialogElement | null;
const btnOpen    = document.getElementById('btn-add-match')    as HTMLButtonElement  | null;
const btnClose   = document.getElementById('btn-modal-close')  as HTMLButtonElement  | null;
const btnCancel  = document.getElementById('btn-cancel')       as HTMLButtonElement  | null;
const btnSave    = document.getElementById('btn-save-match')   as HTMLButtonElement  | null;
const modalError = document.getElementById('modal-error')!;
const statsTbody = document.getElementById('stats-tbody')!;
const teamSelect = document.getElementById('match-team-id')    as HTMLSelectElement  | null;

// Modal - match details
const matchOpponent      = document.getElementById('match-opponent')       as HTMLInputElement;
const matchTeamScore     = document.getElementById('match-team-score')     as HTMLInputElement;
const matchOpponentScore = document.getElementById('match-opponent-score') as HTMLInputElement;
const matchMap           = document.getElementById('match-map')            as HTMLSelectElement;
const matchDuration      = document.getElementById('match-duration')       as HTMLInputElement;
const matchPlayedAt      = document.getElementById('match-played-at')      as HTMLInputElement;
const matchGameMode      = document.getElementById('match-game-mode')      as HTMLSelectElement;

/**
 * API - matches list
 */
async function fetchMatches(page: number): Promise<void> {
    currentPage = page;

    const params = new URLSearchParams({
        page: String(page),
        pageSize: String(PAGE_SIZE),
    });

    if (filterMap.value && filterMap.value !== 'ALL' ) params.set('map_ident', filterMap.value);
    if (filterResult.value && filterResult.value !== 'ALL') params.set('result', filterResult.value);


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
        renderPagination(res.meta ?? null, "matches");
    } catch {
        showError(errorBanner, "Server connection error");
    }

}

/**
 * API - team players list
 */
async function fetchTeamPlayers(teamId: number): Promise<void> {
    statsTbody.innerHTML = '<tr><td class="empty-state" colspan="8"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>';

    try {
        const res = await apiFetch<Player[]>(`/players?team_id=${teamId}&is_active=true`);

        if (!res.success) {
            statsTbody.innerHTML = '<tr><td class="empty-state" colspan="8">Failed to load players.</td></tr>';
            return;
        }

        statsPlayers = res.data ?? [];

        if (statsPlayers.length === 0) {
            statsTbody.innerHTML = '<tr><td class="empty-state" colspan="8">No active players found.</td></tr>';
            return;
        }

        renderStatsRows(statsPlayers, statsTbody);
    } catch {
        statsTbody.innerHTML = '<tr><td class="empty-state" colspan="8">Server connection error.</td></tr>';
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
        console.log('Opponent: ', opponent);
        console.log('Team Score: ', teamScore);
        console.log('Opponent Score: ', oppScore);
        console.log('Map ID: ', mapId);
        console.log('Duration: ', duration);
        console.log('Played At: ', playedAt);
        console.log('Game Mode ID: ', gameModeId);
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

    if (btnSave) btnSave.disabled = true;

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
        await fetchMatches(currentPage);
    } catch {
        showError(modalError, 'Network error. Please try again.');
    } finally {
        if (btnSave) btnSave.disabled = false;
    }
}

/**
 * Render
 */

function resultBadge(result: 'WIN' | 'LOSS' | 'DRAW'): string {
    return `<span class="result-badge result-badge--${result.toLowerCase()}">${result}</span>`;
}

function iconBtn(classes: string, href: string | null, id: number | null, iconClass: string): string {
    if (href) {
        return `<a href="${escapeHtml(href)}" class="btn-icon ${classes}">
                    <i class="${iconClass}" data-id="${id ?? ''}"></i>
                </a>`;
    }
    return `<button class="btn-icon ${classes}" data-id="${id}">
                <i class="${iconClass}" data-id="${id}"></i>
            </button>`;
}

function renderTable(matches: GameMatch[]): void {
    if (matches.length === 0) {
        tbody.innerHTML = '<tr><td class="empty-state" colspan="7">No matches found.</td></tr>';
        return;
    }

    tbody.innerHTML = matches.map(m => {
        const detailsBtn = iconBtn('btn-icon--edit', `/dashboard/matches/${m.id}`, null, 'fa-solid fa-eye');
        const actionsHtml = canWrite
            ? `${detailsBtn}`
            : detailsBtn;

        return `
            <tr>
                <td>
                    <div class="opponent-cell">
                        ${escapeHtml(m.opponentName)}
                    </div>
                </td>
                <td>${escapeHtml(m.mapIdent)}</td>
                <td class="score-cell">${m.teamScore} – ${m.opponentScore}</td>
                <td>${resultBadge(m.result)}</td>
                <td>${formatDate(m.playedAt)}</td>
                <td class="col-actions">
                    <div class="actions-group">${actionsHtml}</div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderStatsRows(players: Player[], target: HTMLElement): void {
    target.innerHTML = players.map((p, i) => `
        <tr data-player-id="${p.id}">
            <td>${escapeHtml(p.nickname)}</td>
            <td><input type="number" name="kills_number"         data-i="${i}" min="0" value="0"></td>
            <td><input type="number" name="deaths_number"        data-i="${i}" min="0" value="0"></td>
            <td><input type="number" name="assists_number"       data-i="${i}" min="0" value="0"></td>
            <td><input type="number" name="flash_assists_number" data-i="${i}" min="0" value="0"></td>
            <td><input type="number" name="total_damage"         data-i="${i}" min="0" value="0"></td>
            <td><input type="number" name="hs_percent"           data-i="${i}" min="0" max="100" value="0"></td>
            <td><input type="number" name="rkast_number"         data-i="${i}" min="0" value="0"></td>
        </tr>
    `).join('');
}

/**
 * Filters
 */
// async function onFilterChange(): Promise<void> {
//     await fetchMatches(1);
// }

function onOpponentInput(): void {
    if (filterDebounceTimer) clearTimeout(filterDebounceTimer);
    filterDebounceTimer = setTimeout(() => fetchMatches(1), 300);
}

/**
 * Modal - add match (ADMIN, COACH)
 */

async function openAddMatchModal(): Promise<void> {
    if (!modal) return;
    hideError(modalError);
    modal.showModal();

    if (systemRole === 'COACH' && sessionTeamId) {
        await fetchTeamPlayers(sessionTeamId);
    }
    // ADMIN: wait for team selection via change event
}

function closeAddMatchModal(): void {
    if (!modal) return;
    hideError(modalError);
    statsTbody.innerHTML = '<tr><td class="empty-state" colspan="8">Select a team to load players.</td></tr>';
    statsPlayers = [];
    modal.close();
}

function collectStatsRows(target: HTMLElement, players: Player[]): PlayerStats[] {
    return players.map(p => {
        const row = target.querySelector<HTMLTableRowElement>(`tr[data-player-id="${p.id}"]`)!;
        const get = (name: string): number =>
            parseFloat(row.querySelector<HTMLInputElement>(`[name="${name}"]`)?.value ?? '0') || 0;

        return {
            playerId:           p.id,
            playerNickname:     p.nickname,
            killsNumber:        get('kills_number'),
            deathsNumber:       get('deaths_number'),
            assistsNumber:      get('assists_number'),
            flashAssistsNumber: get('flash_assists_number'),
            totalDamage:        get('total_damage'),
            hsPercent:          get('hs_percent'),
            rkastNumber:        get('rkast_number'),
        };
    });
}
/**
 * Event listeners
 */

filterOpponent.addEventListener('input', onOpponentInput);
filterMap.addEventListener('change', async () => await fetchMatches(1));
filterResult.addEventListener('change', async () => await fetchMatches(1));

// ADMIN — reload players when team changes
teamSelect?.addEventListener('change', async () => {
    const tid = parseInt(teamSelect.value, 10);
    if (tid) await fetchTeamPlayers(tid);
});

if (canWrite && modal && btnOpen && btnClose && btnCancel && btnSave) {
    btnOpen.addEventListener('click',   openAddMatchModal);
    btnClose.addEventListener('click',  closeAddMatchModal);
    btnCancel.addEventListener('click', closeAddMatchModal);
    btnSave.addEventListener('click',   addMatch);
}

// Pagination buttons wired via shared helper
bindPaginationButtons(
    (page) => fetchMatches(page),
    () => currentPage,
    () => currentMeta
);

/**
 * UI helpers
 */

function showError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden = false;
    el.classList.remove("error-banner--hidden");
}

function hideError(el: HTMLElement): void {
    el.textContent = '';
    el.hidden = true;
    el.classList.add("error-banner--hidden");
}

/**
 * Init
 */
await fetchMatches(1);