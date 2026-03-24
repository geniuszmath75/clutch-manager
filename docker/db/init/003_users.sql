-- =============================================================================
-- 003_users.sql — Tabela użytkowników + dane przykładowe
-- =============================================================================

CREATE TABLE users (
                       id             BIGSERIAL       PRIMARY KEY,
                       nickname       VARCHAR(255)  NOT NULL UNIQUE,
                       email          VARCHAR(255) NOT NULL UNIQUE,
                       password       VARCHAR(255) NOT NULL,
                       system_role_id INT          NOT NULL REFERENCES system_roles(id),
                       team_role_id   INT                   REFERENCES team_roles(id),
                       team_id        INT                   REFERENCES teams(id),
                       is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
                       created_at     TIMESTAMP  NOT NULL DEFAULT NOW(),
                       updated_at     TIMESTAMP  NOT NULL DEFAULT NOW(),
                       deleted_at     TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- Seed danych słownikowych (system_roles, team_roles, game_modes, game_maps, strategy_types)
-- Tabele zostały utworzone w 001_dictionaries.sql bez danych.
-- -----------------------------------------------------------------------------

INSERT INTO system_roles (ident) VALUES
                                    ('ADMIN'),
                                    ('PLAYER'),
                                    ('COACH');

INSERT INTO team_roles (ident) VALUES
                                  ('IGL'),
                                  ('AWP'),
                                  ('ENTRY'),
                                  ('SUPPORT'),
                                  ('LURKER');

INSERT INTO game_modes (ident) VALUES
                                  ('COMPETITIVE'),
                                  ('PREMIER'),
                                  ('WINGMAN');

INSERT INTO game_maps (ident) VALUES
                                 ('MIRAGE'),
                                 ('DUST2'),
                                 ('INFERNO'),
                                 ('NUKE'),
                                 ('OVERPASS'),
                                 ('ANCIENT'),
                                 ('ANUBIS');

INSERT INTO strategy_types (ident) VALUES
                                      ('ATTACK'),
                                      ('DEFENSE'),
                                      ('ECO'),
                                      ('DEFAULT');

-- -----------------------------------------------------------------------------
-- Dane przykładowe użytkowników
-- -----------------------------------------------------------------------------

INSERT INTO users (nickname, email, password, system_role_id, team_role_id, team_id) VALUES
                                                                                              (
                                                                                                  'admin',
                                                                                                  'admin@clutch.gg',
                                                                                                  '$2y$12$40SUI8NjtQpAqc8yZvLrR.n2q4W5Qe5OjTfbtPnR./cXgX83k53GG', -- Admin1234!
                                                                                                  (SELECT id FROM system_roles WHERE ident = 'ADMIN'),
                                                                                                  NULL,
                                                                                                  NULL
                                                                                              ),
                                                                                              (
                                                                                                  'coach_dev',
                                                                                                  'coach@clutch.gg',
                                                                                                  '$2y$12$BvjdQMSy6oAfbfZQQz9bae96bDIWYAzUkxceMt0DWUZH2hUNyT1Re', -- Coach1234!
                                                                                                  (SELECT id FROM system_roles WHERE ident = 'COACH'),
                                                                                                  NULL,
                                                                                                  (SELECT id FROM teams WHERE tag = 'CTCH')
                                                                                              ),
                                                                                              (
                                                                                                  'player_dev',
                                                                                                  'player1@clutch.gg',
                                                                                                  '$2y$12$MHySo54MZhOt26yGD7Gqle/O4.IVhKB6hUcqb889ns.NjyzbEnxPi', -- Player1234!
                                                                                                  (SELECT id FROM system_roles WHERE ident = 'PLAYER'),
                                                                                                  (SELECT id FROM team_roles WHERE ident = 'ENTRY'),
                                                                                                  (SELECT id FROM teams WHERE tag = 'CTCH')
                                                                                              );