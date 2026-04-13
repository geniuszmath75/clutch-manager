-- =============================================================================
-- 004_players_seed.sql
-- Dane testowe graczy — rozszerzenie istniejących użytkowników o dodatkowych
-- graczy.
-- =============================================================================

-- Dodatkowi gracze testowi (uzupełniają 3 devowych z 003_users.sql)
INSERT INTO users (nickname, email, password, system_role_id, team_role_id, team_id)
VALUES ('PRa',
        'pra@clutch.gg',
        '$2y$12$4xt6PUyyNVPfOqTfoivHy.DKVh4gZagDXDL2ZTk1x8IYSzhaJDt2y',
        (SELECT id FROM system_roles WHERE ident = 'PLAYER'),
        (SELECT id FROM team_roles WHERE ident = 'ENTRY'),
        (SELECT id FROM teams WHERE tag = 'RVLS')),
       ('Tauson',
        'tauson@clutch.gg',
        '$2y$12$4xt6PUyyNVPfOqTfoivHy.DKVh4gZagDXDL2ZTk1x8IYSzhaJDt2y',
        (SELECT id FROM system_roles WHERE ident = 'PLAYER'),
        (SELECT id FROM team_roles WHERE ident = 'LURKER'),
        (SELECT id FROM teams WHERE tag = 'RVLS')),
       ('Rez',
        'rez@clutch.gg',
        '$2y$12$4xt6PUyyNVPfOqTfoivHy.DKVh4gZagDXDL2ZTk1x8IYSzhaJDt2y',
        (SELECT id FROM system_roles WHERE ident = 'PLAYER'),
        (SELECT id FROM team_roles WHERE ident = 'SUPPORT'),
        (SELECT id FROM teams WHERE tag = 'RVLS')),
       ('hypex',
        'hypex@clutch.gg',
        '$2y$12$4xt6PUyyNVPfOqTfoivHy.DKVh4gZagDXDL2ZTk1x8IYSzhaJDt2y',
        (SELECT id FROM system_roles WHERE ident = 'PLAYER'),
        (SELECT id FROM team_roles WHERE ident = 'AWP'),
        (SELECT id FROM teams WHERE tag = 'RVLS')),
       ('Snax',
        'snax@clutch.gg',
        '$2y$12$4xt6PUyyNVPfOqTfoivHy.DKVh4gZagDXDL2ZTk1x8IYSzhaJDt2y',
        (SELECT id FROM system_roles WHERE ident = 'PLAYER'),
        (SELECT id FROM team_roles WHERE ident = 'IGL'),
        (SELECT id FROM teams WHERE tag = 'RVLS'));
