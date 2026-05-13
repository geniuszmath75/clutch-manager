-- =============================================================================
-- 008_additional_player_stats_seed.sql
-- Player-match stats for matches 2–5 (RVLS team)
--
-- Match totals:
--   Match 2  vs NaVi   NUKE  LOSS  7:13  total_rounds = 20
--   Match 3  vs NaVi   NUKE  WIN  16:14  total_rounds = 30  (OT)
--   Match 4  vs Vit.   DUST2 LOSS  6:13  total_rounds = 19
--   Match 5  vs B8     INF   WIN  13:5   total_rounds = 18
--
-- Role notes:
--   PRa    – Entry  → high kills, high deaths, high hs%
--   Snax   – IGL    → balanced, low hs%, decent assists
--   Rez    – Lurker → low deaths, moderate kills, low hs%
--   Tauson – Support→ high flash assists & assists, average kills
--   hypex  – AWP    → high hs%, few assists, low deaths
-- =============================================================================

INSERT INTO player_match_stats (kills_number, deaths_number, assists_number, flash_assists_number,
                                total_damage, hs_percent, rkast_number, player_id, match_id)
VALUES

-- -------------------------------------------------------------------------
-- Match 2 — vs NaVi, NUKE, LOSS 7:13, total_rounds = 20
-- -------------------------------------------------------------------------

-- PRa (Entry) — aggressive, high deaths in losing match
(10, 17, 2, 0, 930, 58, 10,
 (SELECT id FROM users WHERE nickname = 'PRa'),
 (SELECT id FROM game_matches WHERE id = 2)),

-- Snax (IGL) — solid read of game but limited impact
(8, 14, 5, 0, 820, 27, 12,
 (SELECT id FROM users WHERE nickname = 'Snax'),
 (SELECT id FROM game_matches WHERE id = 2)),

-- Rez (Lurker) — stayed alive, low impact in loss
(6, 12, 1, 0, 510, 31, 10,
 (SELECT id FROM users WHERE nickname = 'Rez'),
 (SELECT id FROM game_matches WHERE id = 2)),

-- Tauson (Support) — decent assists, limited flash opportunities
(7, 15, 6, 2, 670, 29, 11,
 (SELECT id FROM users WHERE nickname = 'Tauson'),
 (SELECT id FROM game_matches WHERE id = 2)),

-- hypex (AWP) — struggled on NUKE angles, NaVi read him
(9, 13, 1, 0, 860, 24, 10,
 (SELECT id FROM users WHERE nickname = 'hypex'),
 (SELECT id FROM game_matches WHERE id = 2)),

-- -------------------------------------------------------------------------
-- Match 3 — vs NaVi, NUKE, WIN 16:14, total_rounds = 30  (OT)
-- -------------------------------------------------------------------------

-- PRa (Entry) — top fragger in series decider, many trades
(22, 20, 3, 0, 2090, 61, 22,
 (SELECT id FROM users WHERE nickname = 'PRa'),
 (SELECT id FROM game_matches WHERE id = 3)),

-- Snax (IGL) — consistent across long match, read NaVi well
(16, 16, 7, 0, 1640, 32, 24,
 (SELECT id FROM users WHERE nickname = 'Snax'),
 (SELECT id FROM game_matches WHERE id = 3)),

-- Rez (Lurker) — efficient, avoided unnecessary duels in OT
(13, 13, 2, 0, 1200, 31, 20,
 (SELECT id FROM users WHERE nickname = 'Rez'),
 (SELECT id FROM game_matches WHERE id = 3)),

-- Tauson (Support) — high assist count across 30 rounds
(12, 17, 10, 4, 1140, 30, 21,
 (SELECT id FROM users WHERE nickname = 'Tauson'),
 (SELECT id FROM game_matches WHERE id = 3)),

-- hypex (AWP) — clutch AWP rounds in OT, low deaths
(18, 14, 2, 0, 1780, 21, 22,
 (SELECT id FROM users WHERE nickname = 'hypex'),
 (SELECT id FROM game_matches WHERE id = 3)),

-- -------------------------------------------------------------------------
-- Match 4 — vs Vitality, DUST2, LOSS 6:13, total_rounds = 19
-- -------------------------------------------------------------------------

-- PRa (Entry) — entry deaths stacking up, Vitality CT dominance
(7, 15, 1, 0, 650, 55, 8,
 (SELECT id FROM users WHERE nickname = 'PRa'),
 (SELECT id FROM game_matches WHERE id = 4)),

-- Snax (IGL) — couldn't find answers mid-map
(6, 13, 3, 0, 580, 22, 8,
 (SELECT id FROM users WHERE nickname = 'Snax'),
 (SELECT id FROM game_matches WHERE id = 4)),

-- Rez (Lurker) — best K/D on team despite loss
(5, 11, 1, 0, 430, 27, 8,
 (SELECT id FROM users WHERE nickname = 'Rez'),
 (SELECT id FROM game_matches WHERE id = 4)),

-- Tauson (Support) — limited flash value on open DUST2
(4, 13, 4, 1, 380, 23, 7,
 (SELECT id FROM users WHERE nickname = 'Tauson'),
 (SELECT id FROM game_matches WHERE id = 4)),

-- hypex (AWP) — solid with AWP but not enough
(8, 12, 0, 0, 760, 12, 9,
 (SELECT id FROM users WHERE nickname = 'hypex'),
 (SELECT id FROM game_matches WHERE id = 4)),

-- -------------------------------------------------------------------------
-- Match 5 — vs B8, INFERNO, WIN 13:5, total_rounds = 18
-- -------------------------------------------------------------------------

-- PRa (Entry) — dominant entry performance against weaker opponent
(17, 9, 2, 0, 1590, 65, 15,
 (SELECT id FROM users WHERE nickname = 'PRa'),
 (SELECT id FROM game_matches WHERE id = 5)),

-- Snax (IGL) — clean calls, solid support kills
(13, 8, 5, 0, 1200, 34, 14,
 (SELECT id FROM users WHERE nickname = 'Snax'),
 (SELECT id FROM game_matches WHERE id = 5)),

-- Rez (Lurker) — very efficient in easy win, minimal deaths
(11, 6, 2, 0, 950, 30, 13,
 (SELECT id FROM users WHERE nickname = 'Rez'),
 (SELECT id FROM game_matches WHERE id = 5)),

-- Tauson (Support) — multiple flash assists opening sites
(10, 8, 7, 3, 890, 28, 13,
 (SELECT id FROM users WHERE nickname = 'Tauson'),
 (SELECT id FROM game_matches WHERE id = 5)),

-- hypex (AWP) — controlled INFERNO angles, clean AWP match
(15, 7, 1, 0, 1380, 22, 14,
 (SELECT id FROM users WHERE nickname = 'hypex'),
 (SELECT id FROM game_matches WHERE id = 5));