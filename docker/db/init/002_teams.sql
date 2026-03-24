-- =============================================================================
-- 002_teams.sql — Tabela drużyn
-- =============================================================================

CREATE TABLE teams (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL UNIQUE,
    tag        VARCHAR(10)  NOT NULL UNIQUE,
    created_at TIMESTAMP  NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP  NOT NULL DEFAULT NOW()
);

-- -----------------------------------------------------------------------------
-- Dane przykładowe
-- -----------------------------------------------------------------------------

INSERT INTO teams (name, tag) VALUES
    ('Clutch Esports', 'CTCH'),
    ('Rival Squad',    'RVLS');