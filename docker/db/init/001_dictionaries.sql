-- =============================================================================
-- 001_dictionaries.sql
-- Tabele słownikowe — brak zależności do innych tabel
-- =============================================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE system_roles (
                              id          BIGSERIAL    PRIMARY KEY,
                              ident       VARCHAR(255) NOT NULL UNIQUE,
                              description TEXT,
                              created_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW(),
                              updated_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);

CREATE TABLE team_roles (
                            id          BIGSERIAL    PRIMARY KEY,
                            ident       VARCHAR(255) NOT NULL UNIQUE,
                            description TEXT,
                            created_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW(),
                            updated_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);

CREATE TABLE game_modes (
                            id          BIGSERIAL    PRIMARY KEY,
                            ident       VARCHAR(255) NOT NULL UNIQUE,
                            description TEXT,
                            created_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW(),
                            updated_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);

CREATE TABLE game_maps (
                      id           BIGSERIAL    PRIMARY KEY,
                      ident        VARCHAR(255) NOT NULL UNIQUE,
                      is_active    BOOLEAN      NOT NULL DEFAULT TRUE,
                      created_at   TIMESTAMPTZ    NOT NULL DEFAULT NOW(),
                      updated_at   TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);

CREATE TABLE strategy_types (
                                id          BIGSERIAL    PRIMARY KEY,
                                ident       VARCHAR(255) NOT NULL UNIQUE,
                                description TEXT,
                                created_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW(),
                                updated_at  TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);