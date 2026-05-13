-- =============================================================================
-- 010_views.sql
-- Dashboard views for Clutch Manager
--
-- Views:
--   v_player_dashboard  — per-player stats for a single player (filter by user id)
--   v_coach_dashboard   — per-player stats for all players in a team (filter by team id)
--   v_admin_dashboard   — aggregate stats per team + recent audit log entries
-- =============================================================================


-- =============================================================================
-- v_player_dashboard
--
-- One row per player. Filter by user_id in PHP:
--   SELECT * FROM v_player_dashboard WHERE player_id = :id
--
-- Columns:
--   player_id              — user id (for WHERE clause in PHP)
--   nickname               — player nickname
--   team_role              — role in team (ENTRY, AWP, ...)
--   player_total_matches   — number of matches the player participated in
--   player_kd              — overall K/D ratio (sum kills / sum deaths)
--   player_kast            — overall KAST% (sum rkast_number / sum total_rounds * 100)
--   player_win_rate        — % of participated matches that were wins
--   avg_kills              — average kills per match
--   avg_deaths             — average deaths per match
--   avg_assists            — average assists per match
--   avg_flash_assists      — average flash assists per match
--   avg_total_damage       — average total damage per match
--   avg_hs_percent         — average headshot % per match
-- =============================================================================

CREATE
OR REPLACE VIEW v_player_dashboard AS
SELECT u.id                                 AS player_id,
       u.nickname,
       tr.ident                             AS team_role,
       COUNT(pms.match_id)                  AS player_total_matches,

       -- K/D: delegated to function (reused by coach view as well)
       fn_player_kd(u.id)                   AS player_kd,

       -- KAST%: sum of rkast rounds / sum of total rounds per match
       ROUND(
               CAST(SUM(pms.rkast_number) AS NUMERIC)
                   / NULLIF(SUM(gm.team_score + gm.opponent_score), 0)
                   * 100,
               1
       )                                    AS player_kast,

       -- Win rate: delegated to function
       fn_player_win_rate(u.id)             AS player_win_rate,

       ROUND(AVG(pms.kills_number))         AS avg_kills,
       ROUND(AVG(pms.deaths_number))        AS avg_deaths,
       ROUND(AVG(pms.assists_number))       AS avg_assists,
       ROUND(AVG(pms.flash_assists_number)) AS avg_flash_assists,
       ROUND(AVG(pms.total_damage))         AS avg_total_damage,
       ROUND(AVG(pms.hs_percent))           AS avg_hs_percent

FROM users u
-- join through match stats — player only sees matches they actually played
         JOIN player_match_stats pms ON pms.player_id = u.id
         JOIN game_matches gm ON gm.id = pms.match_id
         JOIN system_roles sr ON sr.id = u.system_role_id
         LEFT JOIN team_roles tr ON tr.id = u.team_role_id
WHERE sr.ident = 'PLAYER'
  AND u.deleted_at IS NULL
  AND gm.deleted_at IS NULL
GROUP BY u.id,
         u.nickname,
         tr.ident;


-- =============================================================================
-- v_coach_dashboard
--
-- One row per player in a team. Filter by team_id in PHP:
--   SELECT * FROM v_coach_dashboard WHERE team_id = :id
--
-- Extends v_player_dashboard with team metadata so the coach sees
-- all players in their team in a single query.
--
-- Additional columns vs v_player_dashboard:
--   team_id    — for WHERE clause in PHP
--   team_name  — full team name
--   team_tag   — short team tag
-- =============================================================================

CREATE
OR REPLACE VIEW v_coach_dashboard AS
SELECT t.id                                 AS team_id,
       t.name                               AS team_name,
       t.tag                                AS team_tag,

       u.id                                 AS player_id,
       u.nickname,
       tr.ident                             AS team_role,
       COUNT(pms.match_id)                  AS player_total_matches,

       fn_player_kd(u.id)                   AS player_kd,

       ROUND(
               CAST(SUM(pms.rkast_number) AS NUMERIC)
                   / NULLIF(SUM(gm.team_score + gm.opponent_score), 0)
                   * 100,
               1
       )                                    AS player_kast,

       fn_player_win_rate(u.id)             AS player_win_rate,

       ROUND(AVG(pms.kills_number))         AS avg_kills,
       ROUND(AVG(pms.deaths_number))        AS avg_deaths,
       ROUND(AVG(pms.assists_number))       AS avg_assists,
       ROUND(AVG(pms.flash_assists_number)) AS avg_flash_assists,
       ROUND(AVG(pms.total_damage))         AS avg_total_damage,
       ROUND(AVG(pms.hs_percent))           AS avg_hs_percent

FROM teams t
         JOIN users u ON u.team_id = t.id
         JOIN player_match_stats pms ON pms.player_id = u.id
         JOIN game_matches gm ON gm.id = pms.match_id
         JOIN system_roles sr ON sr.id = u.system_role_id
         LEFT JOIN team_roles tr ON tr.id = u.team_role_id
WHERE sr.ident = 'PLAYER'
  AND u.deleted_at IS NULL
  AND gm.deleted_at IS NULL
GROUP BY t.id,
         t.name,
         t.tag,
         u.id,
         u.nickname,
         tr.ident;


-- =============================================================================
-- v_admin_dashboard
--
-- One row per team — aggregate stats across the whole team.
-- No filter needed; admin sees all teams.
--
-- Columns:
--   team_id            — team id
--   team_name          — full team name
--   team_tag           — short tag
--   total_players      — number of active, non-deleted players
--   total_matches      — number of non-deleted matches for the team
--   team_win_rate      — % of team matches that were wins
--   team_kd            — overall K/D for the whole team across all matches
--   avg_kills_per_match    — average kills scored by team per match (sum / matches)
--   avg_damage_per_match   — average total damage by team per match
-- =============================================================================

CREATE
OR REPLACE VIEW v_admin_dashboard AS
SELECT t.id                  AS team_id,
       t.name                AS team_name,
       t.tag                 AS team_tag,

       -- Active roster size
       COUNT(DISTINCT u.id)  AS total_players,

       -- Total non-deleted matches for this team
       COUNT(DISTINCT gm.id) AS total_matches,

       -- Team win rate
       ROUND(
               CAST(COUNT(DISTINCT gm.id) FILTER (WHERE gm.team_score > gm.opponent_score) AS NUMERIC)
                   / NULLIF(COUNT(DISTINCT gm.id), 0)
                   * 100,
               1
       )                     AS team_win_rate,

       -- Team-wide K/D across all players and all matches
       ROUND(
               CAST(SUM(pms.kills_number) AS NUMERIC)
                   / NULLIF(SUM(pms.deaths_number), 0),
               2
       )                     AS team_kd,

       -- Average kills per match (sum of all kills / number of distinct matches)
       ROUND(
               CAST(SUM(pms.kills_number) AS NUMERIC)
                   / NULLIF(COUNT(DISTINCT gm.id), 0)
       )                     AS avg_kills_per_match,

       -- Average damage per match
       ROUND(
               CAST(SUM(pms.total_damage) AS NUMERIC)
                   / NULLIF(COUNT(DISTINCT gm.id), 0)
       )                     AS avg_damage_per_match

FROM teams t
         JOIN users u ON u.team_id = t.id
         JOIN system_roles sr ON sr.id = u.system_role_id
         JOIN player_match_stats pms ON pms.player_id = u.id
         JOIN game_matches gm ON gm.id = pms.match_id
WHERE sr.ident = 'PLAYER'
  AND u.deleted_at IS NULL
  AND gm.deleted_at IS NULL
GROUP BY t.id,
         t.name,
         t.tag;


-- =============================================================================
-- v_admin_audit_log
--
-- Recent audit log entries enriched with actor nickname, action label,
-- entity type label and team context of the actor.
-- Used by admin dashboard — latest activity feed.
-- Filter / order in PHP: ORDER BY created_at DESC LIMIT :n
--
-- Columns:
--   log_id          — audit_log.id (for ordering)
--   actor_nickname  — nickname of user who performed the action
--   actor_role      — system role of the actor (COACH, ADMIN, …)
--   team_tag        — team tag of the actor (NULL for ADMIN)
--   action_ident    — e.g. ADD_MATCH, DELETE_STRATEGY
--   entity_type     — e.g. GAME_MATCH, STRATEGY, PLAYER (NULL if not applicable)
--   entity_id       — id of the affected record (NULL if not applicable)
--   created_at      — timestamp of the action
-- =============================================================================

CREATE
OR REPLACE VIEW v_admin_audit_log AS
SELECT al.id      AS log_id,
       u.nickname AS actor_nickname,
       sr.ident   AS actor_role,
       t.tag      AS team_tag,
       ua.ident   AS action_ident,
       et.ident   AS entity_type,
       al.entity_id,
       al.created_at
FROM audit_log al
         JOIN users u ON u.id = al.user_id
         JOIN system_roles sr ON sr.id = u.system_role_id
         JOIN user_actions ua ON ua.id = al.user_action_id
         LEFT JOIN entity_types et ON et.id = al.entity_type_id
         LEFT JOIN teams t ON t.id = u.team_id;