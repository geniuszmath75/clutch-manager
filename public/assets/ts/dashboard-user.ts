import { apiFetch }                      from './helpers/fetch-helpers.js';
import { formatDate }                    from './helpers/date-helpers.js';
import { escapeHtml }                    from './helpers/string-helpers.js';

export {}

/**
 * Types
 */
interface PlayerStat {
    playerId:          number;
    nickname:           string;
    teamRole:          string;
    playerTotalMatches: number;
    playerKd:          number;
    playerKast:        number;
    playerWinRate:    number;
    avgKills:          number;
    avgDeaths:         number;
    avgAssists:        number;
    avgFlashAssists:  number;
    avgTotalDamage:   number;
    avgHsPercent:     number;
}

interface DashboardPayload {
    role:  string;
    stats: PlayerStat | PlayerStat[];  // single object for PLAYER, array for COACH
}

interface RecentMatch {
    id:             number;
    playedAt:      string;
    mapIdent:      string;
    teamScore:     number;
    opponentScore: number;
    // PLAYER-only (may be absent for COACH request)
    killsNumber?:  number;
    deathsNumber?: number;
}

// Thresholds for stat card color classes (poor / medium / good).
// Fill in the numeric boundaries to match your design intent.
const STAT_THRESHOLDS: Record<string, [number, number]> = {
    // [poor_max, medium_max]  — above medium_max -> 'good'
    player_kd:          [0.94,  1.14 ],
    player_kast:        [45,   74  ],
    player_win_rate:    [40,   55  ],
    avg_kills:          [10,   16  ],
    avg_deaths:         [14,   18  ],   // for deaths: lower is better — logic inverted below
    avg_assists:        [2,    5   ],
    avg_flash_assists:  [0,    1   ],
    avg_total_damage:   [700,  1200],
    avg_hs_percent:     [30,   50  ],
};

// Stats where a LOWER value is better (used to invert the color logic).
const LOWER_IS_BETTER = new Set<string>(['avg_deaths']);

// Human-readable labels for each stat key.
const STAT_LABELS: Record<string, string> = {
    player_kd:          'K/D ratio',
    player_kast:        'KAST %',
    player_win_rate:    'Win rate %',
    avg_kills:          'Avg kills',
    avg_deaths:         'Avg deaths',
    avg_assists:        'Avg assists',
    avg_flash_assists:  'Avg flash assists',
    avg_total_damage:   'Avg damage',
    avg_hs_percent:     'Avg HS %',
};

/**
 * State
 */
const body       = document.body;
const systemRole = body.dataset['systemRole'] ?? '';
const isCoach    = systemRole === 'COACH';

// All players' stats loaded once — tabs switch between them client-side.
let allStats: PlayerStat[] = [];

/**
 * DOM elements
 */
const playerTabsEl       = document.getElementById('player-tabs')!;
const statsGridEl        = document.getElementById('stats-grid')!;
const statsEmptyEl       = document.getElementById('stats-empty')!             as HTMLElement;
const recentTbodyEl      = document.getElementById('recent-matches-tbody')!;
const recentEmptyEl      = document.getElementById('recent-matches-empty')!    as HTMLElement;
const recentErrorEl      = document.getElementById('recent-matches-error')!    as HTMLElement;

/**
 * API - fetch dashboard stats
 */
async function fetchDashboardStats(): Promise<void> {
    const res = await apiFetch<DashboardPayload>('/dashboard/stats');

    if (!res.success || !res.data) {
        showError(statsEmptyEl, res.errorMessage ?? 'Failed to load statistics.');
        return;
    }

    const payload = res.data;

    if (payload.role === 'COACH') {
        allStats = Array.isArray(payload.stats) ? payload.stats : [];
    } else {
        // PLAYER — single stat object
        allStats = payload.stats && !Array.isArray(payload.stats) ? [payload.stats] : [];
    }

    if (allStats.length === 0) {
        statsEmptyEl.hidden = false;
        return;
    }

    renderTabs(allStats);
    renderStatCards(allStats[0]!);
}

/**
 * API - fetch recent matches
 */
async function fetchRecentMatches(): Promise<void> {
    // Reuse the existing /matches endpoint — filter by pageSize=3, page=1.
    const res = await apiFetch<RecentMatch[]>('/matches?page=1&pageSize=3');

    if (!res.success || !res.data) {
        showError(recentErrorEl, res.errorMessage ?? 'Failed to load recent matches.');
        return;
    }

    const matches = res.data;

    if (matches.length === 0) {
        recentEmptyEl.hidden = false;
        return;
    }

    renderRecentMatches(matches);
}

/**
 * Render
 */
function renderTabs(stats: PlayerStat[]): void {
    // Only render tabs for COACH (multiple players); PLAYER has exactly 1 row.
    if (!isCoach || stats.length <= 1) {
        playerTabsEl.hidden = true;
        return;
    }

    playerTabsEl.hidden   = false;
    playerTabsEl.innerHTML = stats.map((s, i) => `
        <button
            class="dashboard-tabs__tab${i === 0 ? ' is-active' : ''}"
            role="tab"
            data-index="${i}"
            aria-selected="${i === 0 ? 'true' : 'false'}"
        >
            ${escapeHtml(s.nickname)}
            <span class="dashboard-tabs__role">${escapeHtml(s.teamRole ?? '')}</span>
        </button>
    `).join('');

    playerTabsEl.querySelectorAll<HTMLButtonElement>('.dashboard-tabs__tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = parseInt(btn.dataset['index'] ?? '0', 10);
            activateTab(idx);
        });
    });
}

function activateTab(idx: number): void {
    playerTabsEl.querySelectorAll<HTMLButtonElement>('.dashboard-tabs__tab').forEach((btn, i) => {
        const active = i === idx;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-selected', String(active));
    });
    renderStatCards(allStats[idx]!);
}

function renderStatCards(stat: PlayerStat): void {
    const keys: (keyof typeof STAT_LABELS)[] = [
        'player_kd', 'player_kast', 'player_win_rate',
        'avg_kills', 'avg_deaths', 'avg_assists',
        'avg_flash_assists', 'avg_total_damage', 'avg_hs_percent',
    ];

    statsGridEl.innerHTML = keys.map(key => {
        const value     = (stat as unknown as Record<string, number>)[key] ?? 0;
        const label     = STAT_LABELS[key] ?? key;
        const colorClass = resolveColorClass(key, value);

        return `
            <div class="stat-card ${colorClass}">
                <span class="stat-card__label">${escapeHtml(label)}</span>
                <strong class="stat-card__value">${value}</strong>
            </div>
        `;
    }).join('');

    statsEmptyEl.hidden = true;
}

function resolveColorClass(key: string, value: number): string {
    const thresholds = STAT_THRESHOLDS[key];
    if (!thresholds) return '';

    const [poorMax, mediumMax] = thresholds;
    const invert = LOWER_IS_BETTER.has(key);

    let level: 'poor' | 'medium' | 'good';

    if (!invert) {
        level = value <= poorMax ? 'poor' : value <= mediumMax ? 'medium' : 'good';
    } else {
        // Lower value -> better color
        level = value >= poorMax ? 'poor' : value >= mediumMax ? 'medium' : 'good';
    }

    return `stat-card--${level}`;
}

function renderRecentMatches(matches: RecentMatch[]): void {
    recentTbodyEl.innerHTML = matches.map(m => {
        const result     = m.teamScore > m.opponentScore ? 'WIN'
            : m.teamScore < m.opponentScore ? 'LOSS'
                : 'DRAW';
        const resultClass = result.toLowerCase();

        return `
            <tr>
                <td>${escapeHtml(formatDate(m.playedAt))}</td>
                <td>${escapeHtml(m.mapIdent)}</td>
                <td><span class="badge badge--${resultClass}">${result}</span></td>
                <td>${m.teamScore} : ${m.opponentScore}</td>
            </tr>
        `;
    }).join('');
}

/**
 * UI helpers
 */
function showError(el: HTMLElement, msg: string): void {
    el.textContent = msg;
    el.hidden      = false;
}

/**
 * Init
 */
await fetchDashboardStats();
await fetchRecentMatches();