-- =============================================================================
-- 004_players_seed.sql
-- Dane testowe graczy — rozszerzenie istniejących użytkowników o dodatkowych
-- graczy.
-- =============================================================================

-- Dodatkowi gracze testowi (uzupełniają 3 devowych z 003_users.sql)
INSERT INTO users (nickname, email, password, system_role_id, team_role_id)
SELECT u.nickname,
       u.email,
       '$2y$12$4xt6PUyyNVPfOqTfoivHy.DKVh4gZagDXDL2ZTk1x8IYSzhaJDt2y',
       sr.id,
       tr.id
FROM (VALUES ('PRa', 'pra@clutch.gg', 'PLAYER', 'ENTRY'),
             ('Tauson', 'tauson@clutch.gg', 'PLAYER', 'LURKER'),
             ('Rez', 'rez@clutch.gg', 'PLAYER', 'SUPPORT'),
             ('hypex', 'hypex@clutch.gg', 'PLAYER', 'AWP'),
             ('Snax', 'snax@clutch.gg', 'PLAYER', 'IGL')) AS u(nickname, email, system_role_ident, team_role_ident)
         JOIN system_roles sr ON sr.ident = u.system_role_ident
         JOIN team_roles tr ON tr.ident = u.team_role_ident;