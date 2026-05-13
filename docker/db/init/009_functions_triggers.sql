-- =============================================================================
-- 009_functions_triggers.sql
-- =============================================================================


-- =============================================================================
-- FUNCTIONS
-- =============================================================================

-- -----------------------------------------------------------------------------
-- fn_player_kd(p_player_id BIGINT)
-- Returns overall K/D ratio for a given player across all their matches.
-- Used by: v_player_dashboard, v_coach_dashboard
-- -----------------------------------------------------------------------------
CREATE
OR REPLACE FUNCTION fn_player_kd(p_player_id BIGINT)
RETURNS NUMERIC AS $$
DECLARE
v_kills  BIGINT;
    v_deaths
BIGINT;
BEGIN
SELECT SUM(kills_number),
       SUM(deaths_number)
INTO v_kills, v_deaths
FROM player_match_stats
WHERE player_id = p_player_id;

IF
v_deaths IS NULL OR v_deaths = 0 THEN
        RETURN v_kills;
END IF;

RETURN ROUND(CAST(v_kills AS NUMERIC) / v_deaths, 2);
END;
$$
LANGUAGE plpgsql STABLE;


-- -----------------------------------------------------------------------------
-- fn_player_win_rate(p_player_id BIGINT)
-- Returns win rate (%) for a given player based on matches they participated in.
-- A win is when team_score > opponent_score for the match.
-- Used by: v_player_dashboard, v_coach_dashboard
-- -----------------------------------------------------------------------------
CREATE
OR REPLACE FUNCTION fn_player_win_rate(p_player_id BIGINT)
RETURNS NUMERIC AS $$
DECLARE
v_total_matches BIGINT;
    v_won_matches
BIGINT;
BEGIN
SELECT COUNT(DISTINCT gm.id)
INTO v_total_matches
FROM player_match_stats pms
         JOIN game_matches gm ON gm.id = pms.match_id
WHERE pms.player_id = p_player_id
  AND gm.deleted_at IS NULL;

IF
v_total_matches = 0 THEN
        RETURN 0;
END IF;

SELECT COUNT(DISTINCT gm.id)
INTO v_won_matches
FROM player_match_stats pms
         JOIN game_matches gm ON gm.id = pms.match_id
WHERE pms.player_id = p_player_id
  AND gm.deleted_at IS NULL
  AND gm.team_score > gm.opponent_score;

RETURN ROUND(CAST(v_won_matches AS NUMERIC) / v_total_matches * 100, 1);
END;
$$
LANGUAGE plpgsql STABLE;


-- =============================================================================
-- AUDIT LOG — tables setup
-- =============================================================================

CREATE TABLE IF NOT EXISTS entity_types
(
    id
    BIGSERIAL
    PRIMARY
    KEY,
    ident
    VARCHAR
(
    255
) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW
(
),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW
(
)
    );

CREATE TABLE IF NOT EXISTS user_actions
(
    id
    BIGSERIAL
    PRIMARY
    KEY,
    ident
    VARCHAR
(
    255
) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW
(
),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW
(
)
    );

CREATE TABLE IF NOT EXISTS audit_log
(
    id
    BIGSERIAL
    PRIMARY
    KEY,
    user_id
    BIGINT
    NOT
    NULL
    REFERENCES
    users
(
    id
),
    user_action_id BIGINT NOT NULL REFERENCES user_actions
(
    id
),
    entity_type_id BIGINT NULL REFERENCES entity_types
(
    id
),
    entity_id BIGINT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW
(
)
    );

CREATE INDEX IF NOT EXISTS idx_audit_log_user_id ON audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_action_id ON audit_log(user_action_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_entity ON audit_log(entity_type_id, entity_id);


-- =============================================================================
-- AUDIT LOG — seed data
-- =============================================================================

INSERT INTO entity_types (ident, description)
VALUES ('GAME_MATCH', 'A single game match record'),
       ('STRATEGY', 'A team strategy record'),
       ('PLAYER', 'A player (user with PLAYER role)'),
       ('TEAM', 'A team record'),
       ('USER', 'A user account record'),
       ('PLAYER_MATCH_STATS', 'Per-player statistics for a single match');

INSERT INTO user_actions (ident, description)
VALUES ('ADD_MATCH', 'Added a new match'),
       ('EDIT_MATCH', 'Edited an existing match'),
       ('DELETE_MATCH', 'Deleted a match'),
       ('ADD_STRATEGY', 'Added a new strategy'),
       ('EDIT_STRATEGY', 'Edited an existing strategy'),
       ('DELETE_STRATEGY', 'Deleted a strategy'),
       ('ADD_PLAYER', 'Added a player to a team'),
       ('DEACTIVATE_PLAYER', 'Deactivated a player'),
       ('DELETE_PLAYER', 'Removed a player from the system');

-- =============================================================================
-- Team Rival Squad - coach seed data
-- =============================================================================
INSERT INTO users (nickname, email, password, system_role_id, team_role_id, team_id)
VALUES ('Gruby',
        'gruby@clutch.gg',
        '$2y$12$BvjdQMSy6oAfbfZQQz9bae96bDIWYAzUkxceMt0DWUZH2hUNyT1Re',
        (SELECT id FROM system_roles WHERE ident = 'COACH'),
        NULL,
        (SELECT id FROM teams WHERE tag = 'RVLS'));

-- =============================================================================
-- TRIGGER — auto-insert audit log row
-- Fired AFTER INSERT / UPDATE / DELETE on watched tables.
-- The trigger function expects the calling context to set:
--   NEW.updated_by_user_id  (for INSERT / UPDATE)
--   OLD.updated_by_user_id  (for DELETE)
--
-- Because triggers cannot receive ad-hoc parameters, the approach used here is
-- a generic AFTER-statement trigger per table that maps the operation to the
-- correct user_action ident via a CASE expression.
-- The PHP service layer must supply `updated_by_user_id` in the payload so the
-- trigger can read it from the row.
--
-- NOTE: updated_by_user_id columns are added below to the relevant tables.
-- =============================================================================

-- Add updated_by_user_id to tables that need audit tracking
ALTER TABLE game_matches
    ADD COLUMN IF NOT EXISTS updated_by_user_id BIGINT NULL REFERENCES users(id);
ALTER TABLE team_strategies
    ADD COLUMN IF NOT EXISTS updated_by_user_id BIGINT NULL REFERENCES users(id);
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS updated_by_user_id BIGINT NULL REFERENCES users(id);


-- -----------------------------------------------------------------------------
-- fn_audit_log_game_matches()
-- Trigger function for game_matches table.
-- -----------------------------------------------------------------------------
CREATE
OR REPLACE FUNCTION fn_audit_log_game_matches()
RETURNS TRIGGER AS $$
DECLARE
v_action_ident VARCHAR(255);
    v_actor_id
BIGINT;
    v_entity_type
BIGINT;
BEGIN
SELECT id
INTO v_entity_type
FROM entity_types
WHERE ident = 'GAME_MATCH';

IF
TG_OP = 'INSERT' THEN
        v_action_ident := 'ADD_MATCH';
        v_actor_id
:= NEW.updated_by_user_id;
    ELSIF
TG_OP = 'UPDATE' AND NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        v_action_ident := 'DELETE_MATCH';
        v_actor_id
:= NEW.updated_by_user_id;
    ELSIF
TG_OP = 'UPDATE' THEN
        v_action_ident := 'EDIT_MATCH';
        v_actor_id
:= NEW.updated_by_user_id;
ELSE
        RETURN NEW;
END IF;

    IF
v_actor_id IS NULL THEN
        RETURN NEW;
END IF;

INSERT INTO audit_log (user_id, user_action_id, entity_type_id, entity_id)
SELECT v_actor_id, ua.id, v_entity_type, NEW.id
FROM user_actions ua
WHERE ua.ident = v_action_ident;

RETURN NEW;
END;
$$
LANGUAGE plpgsql;

CREATE
OR REPLACE TRIGGER trg_audit_game_matches
AFTER INSERT OR
UPDATE ON game_matches
    FOR EACH ROW EXECUTE FUNCTION fn_audit_log_game_matches();


-- -----------------------------------------------------------------------------
-- fn_audit_log_strategies()
-- Trigger function for team_strategies table.
-- -----------------------------------------------------------------------------
CREATE
OR REPLACE FUNCTION fn_audit_log_strategies()
RETURNS TRIGGER AS $$
DECLARE
v_action_ident VARCHAR(255);
    v_actor_id
BIGINT;
    v_entity_type
BIGINT;
BEGIN
SELECT id
INTO v_entity_type
FROM entity_types
WHERE ident = 'STRATEGY';

IF
TG_OP = 'INSERT' THEN
        v_action_ident := 'ADD_STRATEGY';
        v_actor_id
:= NEW.updated_by_user_id;
    ELSIF
TG_OP = 'UPDATE' AND NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        v_action_ident := 'DELETE_STRATEGY';
        v_actor_id
:= NEW.updated_by_user_id;
    ELSIF
TG_OP = 'UPDATE' THEN
        v_action_ident := 'EDIT_STRATEGY';
        v_actor_id
:= NEW.updated_by_user_id;
ELSE
        RETURN NEW;
END IF;

    IF
v_actor_id IS NULL THEN
        RETURN NEW;
END IF;

INSERT INTO audit_log (user_id, user_action_id, entity_type_id, entity_id)
SELECT v_actor_id, ua.id, v_entity_type, NEW.id
FROM user_actions ua
WHERE ua.ident = v_action_ident;

RETURN NEW;
END;
$$
LANGUAGE plpgsql;

CREATE
OR REPLACE TRIGGER trg_audit_strategies
AFTER INSERT OR
UPDATE ON team_strategies
    FOR EACH ROW EXECUTE FUNCTION fn_audit_log_strategies();


-- -----------------------------------------------------------------------------
-- fn_audit_log_users()
-- Trigger function for users table — tracks player add / deactivate / delete.
-- -----------------------------------------------------------------------------
CREATE
OR REPLACE FUNCTION fn_audit_log_users()
RETURNS TRIGGER AS $$
DECLARE
v_action_ident VARCHAR(255);
    v_actor_id
BIGINT;
    v_entity_type
BIGINT;
BEGIN
SELECT id
INTO v_entity_type
FROM entity_types
WHERE ident = 'PLAYER';

-- Only track rows with PLAYER system role
IF
NEW.system_role_id NOT IN (SELECT id FROM system_roles WHERE ident = 'PLAYER') THEN
        RETURN NEW;
END IF;

    IF
TG_OP = 'UPDATE' AND NEW.team_id IS NOT NULL AND OLD.team_id IS NULL THEN
        v_action_ident := 'ADD_PLAYER';
        v_actor_id
:= NEW.updated_by_user_id;
    ELSIF
TG_OP = 'UPDATE' AND NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        v_action_ident := 'DELETE_PLAYER';
        v_actor_id
:= NEW.updated_by_user_id;
    ELSIF
TG_OP = 'UPDATE' AND NEW.is_active = FALSE AND OLD.is_active = TRUE THEN
        v_action_ident := 'DEACTIVATE_PLAYER';
        v_actor_id
:= NEW.updated_by_user_id;
ELSE
        RETURN NEW;
END IF;

    IF
v_actor_id IS NULL THEN
        RETURN NEW;
END IF;

INSERT INTO audit_log (user_id, user_action_id, entity_type_id, entity_id)
SELECT v_actor_id, ua.id, v_entity_type, NEW.id
FROM user_actions ua
WHERE ua.ident = v_action_ident;

RETURN NEW;
END;
$$
LANGUAGE plpgsql;

CREATE
OR REPLACE TRIGGER trg_audit_users
AFTER INSERT OR
UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION fn_audit_log_users();