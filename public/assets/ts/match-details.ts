export {};

/**
 * Types
 */
interface PaginationMeta {
    total: number,
    page: number,
    pageSize: number;
    totalPages: number
}

interface ApiResponse<T> {
    success: boolean;
    statusCode?: number;
    errorMessage?: string;
    data?: T;
    meta?: PaginationMeta;
}

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
const body = document.body;
const matchId = body.dataset['matchId'] ? parseInt(body.dataset['matchId'] ?? '0', 10) : null;
const systemRole = body.dataset['systemRole'] ?? '';
const canWrite = systemRole === 'COACH' || systemRole === 'ADMIN';

let currentMatch: GameMatch | null = null;
let currentStats: PlayerStats[] = [];
let mapsCache: DictEntry[] = [];
let modesCache: DictEntry[] = [];

/**
 * DOM elements
 */
const loadingEl = document.getElementById('details-loading')!;
const errorEl = document.getElementById('details-error')!;
const headerEl = document.getElementById('match-header')!;
const statsSection = document.getElementById('stats-section')!;
const actionsEl = document.getElementById('match-actions');

const metaResultBadge = document.getElementById('meta-result-badge')!;
const metaPlayedAt = document.getElementById('meta-played-at')!;
const metaGameMode = document.getElementById('meta-game-mode')!;
const metaScore = document.getElementById('meta-score')!;
const metaMap = document.getElementById('meta-map')!;
const metaDuration = document.getElementById('meta-duration')!;
const statsTbody = document.getElementById('stats-tbody')!;

// Delete match modal
const btnDelete = document.getElementById('btn-delete-match') as HTMLButtonElement;
const modalConfirm = document.getElementById('modal-confirm-delete') as HTMLDialogElement;
const btnDeleteCancel = document.getElementById('btn-delete-cancel') as HTMLButtonElement;
const btnDeleteConfirm = document.getElementById('btn-delete-confirm') as HTMLButtonElement;

// Edit match modal
const btnEdit = document.getElementById('btn-edit-match')!;
const modalEdit = document.getElementById('modal-edit-match') as HTMLDialogElement;
const btnEditClose = document.getElementById('btn-edit-modal-close')!;
const btnEditCancel = document.getElementById('btn-edit-cancel')!;
const btnUpdate = document.getElementById('btn-update-match') as HTMLButtonElement;
const editModalError = document.getElementById('edit-modal-error')!;
const editStatsTbody = document.getElementById('edit-stats-tbody')!;

// Modal - edit match details
const editOpponent = document.getElementById('edit-opponent') as HTMLInputElement;
const editTeamScore = document.getElementById('edit-team-score') as HTMLInputElement;
const editOpponentScore = document.getElementById('edit-opponent-score') as HTMLInputElement;
const editDuration = document.getElementById('edit-duration') as HTMLInputElement;
const editPlayedAt = document.getElementById('edit-played-at') as HTMLInputElement;
const editMapSelection = document.getElementById('edit-map') as HTMLSelectElement;
const editGameModeSelection = document.getElementById('edit-game-mode') as HTMLSelectElement;

/**
 * Fetch helpers
 */
async function apiFetch<T>(url: string, options?: RequestInit): Promise<ApiResponse<T>> {
    const res = await fetch(url, {
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json'},
        ...options,
    });
    return res.json() as Promise<ApiResponse<T>>;
}

/**
 * API - match details with players' stats
 */
async function fetchMatch(): Promise<void> {
    loadingEl.hidden = false;
    headerEl.hidden = true;
    statsSection.hidden = true;
    if (actionsEl) actionsEl.hidden = true;

    const res = await apiFetch<{ match: GameMatch; stats: PlayerStats[] }>(`/matches/${matchId}`);

    loadingEl.hidden = true;

    if (!res.success) {
        showError(errorEl, res.errorMessage ?? 'Failed to fetch match.');
        return;
    }

    const payload = res.data as { match: GameMatch; stats: PlayerStats[] };
    currentMatch = payload.match;
    currentStats = payload.stats;

    renderHeader(currentMatch);
    renderStats(currentStats, currentMatch);

    headerEl.hidden = false;
    statsSection.hidden = false;
    if (actionsEl) actionsEl.hidden = false;
}

async function updateMatchDetails(): Promise<void> {
    hideError(editModalError);

    const opponent = editOpponent.value.trim();
    const teamScore = parseInt(editTeamScore.value, 10);
    const oppScore = parseInt(editOpponentScore.value, 10);
    const mapId = parseInt(editMapSelection.value, 10);
    const duration = parseInt(editDuration.value, 10);
    const playedAt = editPlayedAt.value;
    const gameModeId = parseInt(editGameModeSelection.value, 10);

    if (!opponent || isNaN(teamScore) || isNaN(oppScore) || !mapId || !duration || !playedAt || !gameModeId) {
        showError(editModalError, 'Please fill in all fields.');
        return;
    }

    const stats = collectEditStats().map(s => ({
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
        opponent_name: opponent,
        team_score: teamScore,
        opponent_score: oppScore,
        map_id: mapId,
        game_mode_id: gameModeId,
        duration,
        played_at: playedAt.replace('T', ' '),
        stats: stats,
    };

    btnUpdate.disabled = true;

    try {
        const res = await apiFetch<GameMatch>(`/matches/${matchId}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });

        if (!res.success) {
            showError(editModalError, res.errorMessage ?? 'Failed to update match.');
            return;
        }

        closeEditModal();
        fetchMatch();
    } catch {
        showError(editModalError, 'Server connection error.');
    } finally {
        btnUpdate.disabled = false;
    }
}

async function deleteMatch(): Promise<void> {
    btnDeleteConfirm.disabled = true;

    try {
        const res = await apiFetch(`/matches/${matchId}`, {method: 'DELETE'});

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
        btnDeleteConfirm.disabled = false;
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

    // Score headline: "TeamName  16 : 12  Opponent"
    metaScore.textContent = `${m.teamScore} : ${m.opponentScore}  ${esc(m.opponentName)}`;

    // Map badge
    metaMap.textContent = m.mapIdent;

    // Duration badge
    metaDuration.textContent = `${m.duration} min`;
}

function renderStats(stats: PlayerStats[], m: GameMatch): void {
    if (stats.length === 0) {
        statsTbody.innerHTML = '<tr><td colspan="6" class="table__empty">No player stats recorded.</td></tr>';
        return;
    }

    // ADR = total_damage / total_rounds; total_rounds = team_score + opponent_score
    const totalRounds = m.teamScore + m.opponentScore || 1;

    statsTbody.innerHTML = stats.map(s => {
        const adr = (s.totalDamage / totalRounds).toFixed(1);
        const kastPercent = ((s.rkastNumber / totalRounds) * 100).toFixed(1);
        const pmClass = s.plusMinus >= 0 ? 'stat--positive' : 'stat--negative';
        const pmPrefix = s.plusMinus >= 0 ? '+' : '';

        return `
            <tr>
                <td>${esc(s.playerNickname)}</td>
                <td>${s.killsNumber} / ${s.deathsNumber} / ${s.assistsNumber}</td>
                <td>${s.kd}</td>
                <td class="${pmClass}">${pmPrefix}${s.plusMinus}</td>
                <td>${adr}</td>
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
            <td>${esc(s.playerNickname)}</td>
            <td><input class="input input--sm" type="number" name="kills_number"         min="0" value="${s.killsNumber}"></td>
            <td><input class="input input--sm" type="number" name="deaths_number"        min="0" value="${s.deathsNumber}"></td>
            <td><input class="input input--sm" type="number" name="assists_number"       min="0" value="${s.assistsNumber}"></td>
            <td><input class="input input--sm" type="number" name="flash_assists_number" min="0" value="${s.flashAssistsNumber}"></td>
            <td><input class="input input--sm" type="number" name="total_damage"         min="0" value="${s.totalDamage}"></td>
            <td><input class="input input--sm" type="number" name="hs_percent"           min="0" max="100" value="${s.hsPercent}"></td>
            <td><input class="input input--sm" type="number" name="rkast_number"         min="0" value="${s.rkastNumber}"></td>
        </tr>
    `).join('');
}

/**
 * Modal - edit match (ADMIN, COACH)
 */
async function openEditModal(): Promise<void> {
    if (!currentMatch) return;
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

    // Pre-fill fields
    editOpponent.value = currentMatch.opponentName;
    editTeamScore.value = String(currentMatch.teamScore);
    editOpponentScore.value = String(currentMatch.opponentScore);
    editDuration.value = String(currentMatch.duration);
    editPlayedAt.value = currentMatch.playedAt.replace(' ', 'T').slice(0, 16);

    // Populate map select
    editMapSelection.innerHTML = mapsCache.map(m =>
        `<option value="${m.id}" ${m.id === currentMatch!.mapId ? 'selected' : ''}>${esc(m.ident)}</option>`
    ).join('');

    // Populate game mode select
    editGameModeSelection.innerHTML = modesCache.map(m =>
        `<option value="${m.id}" ${m.id === currentMatch!.gameModeId ? 'selected' : ''}>${esc(m.ident)}</option>`
    ).join('');

    // Pre-fill stats rows
    renderEditStatsRows(currentStats, editStatsTbody);

    modalEdit.showModal();
    modalEdit.hidden = false;
}

function closeEditModal(): void {
    modalEdit.hidden = true;
    modalEdit.close();
    hideError(editModalError);
}

function closeDeleteModal(): void {
    modalConfirm.hidden = true;
    modalConfirm.close();
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
    btnDelete.addEventListener('click', () => {
        modalConfirm.showModal();
        modalConfirm.hidden = false;
    });

    btnDeleteCancel.addEventListener('click', () => {
        modalConfirm.hidden = true;
        modalConfirm.close();
    });

    btnDeleteConfirm.addEventListener('click', async () => {
        await deleteMatch();
    });

    btnEdit.addEventListener('click', openEditModal);
    btnEditClose.addEventListener('click', closeEditModal);
    btnEditCancel.addEventListener('click', closeEditModal);

    btnUpdate.addEventListener('click', async () => {
        await updateMatchDetails();
    });
}

/**
 * UI helpers
 */
function hideError(el: HTMLElement): void {
    el.textContent = '';
    el.hidden = true;
}

function showError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden = false;
}

function esc(str: string): string {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatDate(iso: string): string {
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleString('en-US', {
        month: 'long', day: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: false,
    });
}

/**
 * Init
 */
await fetchMatch();