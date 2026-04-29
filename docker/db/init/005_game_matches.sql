-- =============================================================================
-- 005_game_matches.sql — Tabela meczy rozegranych w grze + dane przykładowe
-- =============================================================================
CREATE TABLE game_matches
(
    id             BIGSERIAL PRIMARY KEY,
    opponent_name  VARCHAR(255) NOT NULL,
    team_score     SMALLINT     NOT NULL CHECK ( team_score >= 0 ),
    opponent_score SMALLINT     NOT NULL CHECK ( opponent_score >= 0 ),
    duration       SMALLINT     NOT NULL CHECK ( duration > 0 ),
    team_id        BIGINT       NOT NULL REFERENCES teams (id),
    map_id         BIGINT       NOT NULL REFERENCES game_maps (id),
    game_mode_id   BIGINT       NOT NULL REFERENCES game_modes (id),
    played_at      TIMESTAMP    NOT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMP    NOT NULL DEFAULT NOW(),
    deleted_at     TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_game_matches_team_id ON game_matches(team_id);
CREATE INDEX IF NOT EXISTS idx_game_matches_map_id ON game_matches(map_id);
CREATE INDEX IF NOT EXISTS idx_game_matches_played_at ON game_matches(played_at DESC);

-- =============================================================================
-- Game matches seed
-- =============================================================================
INSERT INTO game_matches (opponent_name, team_score, opponent_score, duration, team_id, map_id, game_mode_id, played_at)
VALUES ('Faze Clan',
        13,
        10,
        40,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'MIRAGE'),
        (SELECT id FROM game_modes WHERE ident = 'COMPETITIVE'),
        '2026-04-24 20:50'),
       ('NaVi',
        7,
        13,
        34,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'NUKE'),
        (SELECT id FROM game_modes WHERE ident = 'COMPETITIVE'),
        '2026-04-25 19:07'),
       ('NaVi',
        16,
        14,
        55,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'NUKE'),
        (SELECT id FROM game_modes WHERE ident = 'COMPETITIVE'),
        '2026-04-27 21:37'),
       ('Vitality',
        6,
        13,
        25,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'DUST2'),
        (SELECT id FROM game_modes WHERE ident = 'COMPETITIVE'),
        '2026-04-26 18:43'),
       ('B8',
        13,
        5,
        28,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'INFERNO'),
        (SELECT id FROM game_modes WHERE ident = 'COMPETITIVE'),
        '2026-04-26 20:36');
;