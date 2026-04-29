-- =============================================================================
-- 006_player_match_stats.sql — Tabela statystyk graczy z meczu + dane przykładowe
-- =============================================================================
CREATE TABLE player_match_stats
(
    id                   BIGSERIAL PRIMARY KEY,
    kills_number         SMALLINT      NOT NULL DEFAULT 0 CHECK ( kills_number >= 0 ),
    deaths_number        SMALLINT      NOT NULL DEFAULT 0 CHECK ( deaths_number >= 0 ),
    assists_number       SMALLINT      NOT NULL DEFAULT 0 CHECK ( assists_number >= 0 ),
    flash_assists_number SMALLINT      NOT NULL DEFAULT 0 CHECK ( flash_assists_number >= 0 ),
    total_damage         INT           NOT NULL DEFAULT 0 CHECK ( total_damage >= 0 ),
    hs_percent           NUMERIC(5, 2) NOT NULL DEFAULT 0 CHECK ( hs_percent BETWEEN 0 AND 100),
    rkast_number         INT           NOT NULL DEFAULT 0 CHECK ( rkast_number >= 0 ),
    player_id            BIGINT        NOT NULL REFERENCES users (id),
    match_id             BIGINT        NOT NULL REFERENCES game_matches (id) ON DELETE CASCADE,
    created_at           TIMESTAMP     NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMP     NOT NULL DEFAULT NOW(),
    deleted_at           TIMESTAMP,

    UNIQUE (match_id, player_id)
);

CREATE INDEX IF NOT EXISTS idx_pms_match_id ON player_match_stats(match_id);
CREATE INDEX IF NOT EXISTS idx_pms_player_id ON player_match_stats(player_id);

-- =============================================================================
-- Player-match stats seed
-- =============================================================================
INSERT INTO player_match_stats (kills_number, deaths_number, assists_number, flash_assists_number,
                                total_damage, hs_percent, rkast_number, player_id, match_id)
VALUES (14,
        15,
        0,
        0,
        1318,
        71,
        14,
        (SELECT id FROM users WHERE nickname = 'PRa'),
        (SELECT id FROM game_matches WHERE id = 1)),
       (14,
        14,
        7,
        1,
        1918,
        36,
        15,
        (SELECT id FROM users WHERE nickname = 'Snax'),
        (SELECT id FROM game_matches WHERE id = 1)),
       (18,
        14,
        3,
        0,
        1856,
        61,
        15,
        (SELECT id FROM users WHERE nickname = 'Rez'),
        (SELECT id FROM game_matches WHERE id = 1)),
       (16,
        21,
        5,
        0,
        1812,
        31,
        17,
        (SELECT id FROM users WHERE nickname = 'Tauson'),
        (SELECT id FROM game_matches WHERE id = 1)),
       (11,
        16,
        2,
        1,
        938,
        27,
        16,
        (SELECT id FROM users WHERE nickname = 'hypex'),
        (SELECT id FROM game_matches WHERE id = 1));