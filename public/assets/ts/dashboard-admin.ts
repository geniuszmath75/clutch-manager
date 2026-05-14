import { apiFetch }                         from './helpers/fetch-helpers.js';
import type { PaginationMeta } from './helpers/fetch-helpers.js';
import { formatDate }                       from './helpers/date-helpers.js';
import { escapeHtml }                       from './helpers/string-helpers.js';

export {}

/**
 * Types
 */
interface TeamStat {
    teamId:             number;
    teamName:           string;
    teamTag:            string;
    totalPlayers:       number;
    totalMatches:       number;
    teamWinRate:       number;
    teamKd:             number;
    avgKillsPerMatch: number;
    avgDamagePerMatch: number;
}

interface LogEntry {
    logId:          number;
    actorNickname:  string;
    actorRole:      string;
    teamTag:        string | null;
    actionIdent:    string;
    entityType:     string | null;
    entityId:       number | null;
    createdAt:      string;
}

/**
 * State
 */
let teamsPage     = 1;
let teamsMeta: PaginationMeta | null = null;

let auditPage     = 1;
let auditMeta: PaginationMeta | null = null;

const TEAMS_PAGE_SIZE = 5;
const AUDIT_PAGE_SIZE = 10;

/**
 * DOM elements
 */
const teamsTbodyEl     = document.getElementById('teams-tbody')!;
const teamsEmptyEl     = document.getElementById('teams-empty')!       as HTMLElement;
const teamsErrorEl     = document.getElementById('teams-error')!       as HTMLElement;
const teamsPagEl       = document.getElementById('teams-pagination')!  as HTMLElement;
const teamsBtnPrev     = document.getElementById('teams-btn-prev')!    as HTMLButtonElement;
const teamsBtnNext     = document.getElementById('teams-btn-next')!    as HTMLButtonElement;
const teamsPagInfoEl   = document.getElementById('teams-pagination-info')!;

// Audit section
const auditTbodyEl     = document.getElementById('audit-tbody')!;
const auditEmptyEl     = document.getElementById('audit-empty')!       as HTMLElement;
const auditErrorEl     = document.getElementById('audit-error')!       as HTMLElement;
const auditPagEl       = document.getElementById('audit-pagination')!  as HTMLElement;
const auditBtnPrev     = document.getElementById('audit-btn-prev')!    as HTMLButtonElement;
const auditBtnNext     = document.getElementById('audit-btn-next')!    as HTMLButtonElement;
const auditPagInfoEl   = document.getElementById('audit-pagination-info')!;

/**
 * API - fetch team stats
 */
async function fetchTeamStats(page: number): Promise<void> {
    teamsPage = page;

    const res = await apiFetch<TeamStat[]>(
        `/dashboard/admin/teams?page=${page}&pageSize=${TEAMS_PAGE_SIZE}`
    );

    if (!res.success || !res.data) {
        showError(teamsErrorEl, res.errorMessage ?? 'Failed to load team statistics.');
        return;
    }

    teamsMeta = res.meta ?? null;
    renderTeamsTable(res.data);
    renderPagination(teamsPagEl, teamsBtnPrev, teamsBtnNext, teamsPagInfoEl, teamsMeta);
}

/**
 * API - fetch audit logs
 */
async function fetchAuditLog(page: number): Promise<void> {
    auditPage = page;

    const res = await apiFetch<LogEntry[]>(
        `/dashboard/admin/logs?page=${page}&pageSize=${AUDIT_PAGE_SIZE}`
    );

    if (!res.success || !res.data) {
        showError(auditErrorEl, res.errorMessage ?? 'Failed to load audit log.');
        return;
    }

    auditMeta = res.meta ?? null;
    renderAuditTable(res.data);
    renderPagination(auditPagEl, auditBtnPrev, auditBtnNext, auditPagInfoEl, auditMeta);
}

/**
 * Render
 */
function renderTeamsTable(teams: TeamStat[]): void {
    teamsErrorEl.hidden = true;

    if (teams.length === 0) {
        teamsEmptyEl.hidden = false;
        teamsTbodyEl.innerHTML = '';
        return;
    }

    teamsEmptyEl.hidden    = true;
    teamsTbodyEl.innerHTML = teams.map(t => `
        <tr>
            <td>${escapeHtml(t.teamName)}</td>
            <td><span class="badge">${escapeHtml(t.teamTag)}</span></td>
            <td>${t.totalMatches}</td>
            <td>${t.teamWinRate}%</td>
            <td>${t.teamKd}</td>
            <td>${t.avgKillsPerMatch}</td>
            <td>${t.avgDamagePerMatch}</td>
        </tr>
    `).join('');
}

function renderAuditTable(entries: LogEntry[]): void {
    auditErrorEl.hidden = true;

    if (entries.length === 0) {
        auditEmptyEl.hidden = false;
        auditTbodyEl.innerHTML = '';
        return;
    }

    auditEmptyEl.hidden    = true;
    auditTbodyEl.innerHTML = entries.map(e => `
        <tr>
            <td>${escapeHtml(formatDate(e.createdAt))}</td>
            <td>${escapeHtml(e.actorNickname)}</td>
            <td><span class="badge badge--role">${escapeHtml(e.actorRole)}</span></td>
            <td>${e.teamTag ? escapeHtml(e.teamTag) : '—'}</td>
            <td><span class="badge badge--action">${escapeHtml(e.actionIdent)}</span></td>
            <td>${e.entityType ? escapeHtml(e.entityType) : '—'}</td>
            <td>${e.entityId !== null ? e.entityId : '—'}</td>
        </tr>
    `).join('');
}

function renderPagination(
    container: HTMLElement,
    btnPrev: HTMLButtonElement,
    btnNext: HTMLButtonElement,
    infoEl: Element,
    meta: PaginationMeta | null,
): void {
    if (!meta) {
        container.hidden = true;
        return;
    }

    container.hidden      = false;
    infoEl.textContent    = `Showing ${meta.page} of ${meta.totalPages} (${meta.total} results)`;

    btnPrev.disabled      = meta.page <= 1;
    btnNext.disabled      = meta.page >= meta.totalPages;
}

/**
 * Event listeners
 */
teamsBtnPrev.addEventListener('click', async () => {
    if (teamsPage > 1) {
        await fetchTeamStats(teamsPage - 1);
    }
});

teamsBtnNext.addEventListener('click', async () => {
    if (teamsMeta && teamsPage < teamsMeta.totalPages) {
        await fetchTeamStats(teamsPage + 1);
    }
});

auditBtnPrev.addEventListener('click', async () => {
    if (auditPage > 1) {
        await fetchAuditLog(auditPage - 1);
    }
});

auditBtnNext.addEventListener('click', async () => {
    if (auditMeta && auditPage < auditMeta.totalPages) {
        await fetchAuditLog(auditPage + 1);
    }
});

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
await fetchTeamStats(1);
await fetchAuditLog(1);