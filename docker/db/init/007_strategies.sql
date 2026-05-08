-- 007_strategies.sql
-- Tabela strategii drużynowych
CREATE TABLE team_strategies
(
    id               BIGSERIAL PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    description      TEXT,
    steps_to_do      JSONB        NOT NULL DEFAULT '[]',
    team_id          BIGINT       NOT NULL REFERENCES teams (id),
    map_id           BIGINT       NOT NULL REFERENCES game_maps (id),
    strategy_type_id BIGINT       NOT NULL REFERENCES strategy_types (id),
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    deleted_at       TIMESTAMPTZ
);

-- Tabela pivotowa: gracze przypisani do strategii
CREATE TABLE IF NOT EXISTS team_strategy_player
(
    id
    BIGSERIAL
    PRIMARY
    KEY,
    team_strategy_id
    BIGINT
    NOT
    NULL
    REFERENCES
    team_strategies
(
    id
) ON DELETE CASCADE,
    player_id BIGINT NOT NULL REFERENCES users
(
    id
)
  ON DELETE CASCADE,
    UNIQUE
(
    team_strategy_id,
    player_id
)
    );

-- Seed: przykładowe strategie dla drużyny RVLS

INSERT INTO team_strategies (name, description, steps_to_do, team_id, map_id, strategy_type_id)
VALUES ('A-split Rush',
        'Szybki rush na bombsite A przez ramp i palace jednocześnie.',
        '[
          {
            "order": 1,
            "description": "Smoke CT i jungle"
          },
          {
            "order": 2,
            "description": "Flash przez ramp, wejście 3+2"
          },
          {
            "order": 3,
            "description": "Plant na default, hold stairs"
          }
        ]'::jsonb,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'MIRAGE'),
        (SELECT id FROM strategy_types WHERE ident = 'ATTACK')),
       ('B agresywny hold',
        'Agresywny hold na B - wyjście przez banana z AWP.',
        '[
          {
            "order": 1,
            "description": "AWP wyjście na banana"
          },
          {
            "order": 2,
            "description": "Nade spam na pit i car"
          },
          {
            "order": 3,
            "description": "Cofnięcie do site po zużyciu nades"
          }
        ]'::jsonb,
        (SELECT id FROM teams WHERE tag = 'RVLS'),
        (SELECT id FROM game_maps WHERE ident = 'INFERNO'),
        (SELECT id FROM strategy_types WHERE ident = 'DEFENSE'));