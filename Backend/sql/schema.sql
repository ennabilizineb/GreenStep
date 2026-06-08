-- GreenStep authoritative schema — aligned to the finalised ER Diagram (PR1 Word file).
-- OWNERSHIP: Database & Security Lead (Rawan MohamedSalih Magzob — A24CS4066).
-- Table order respects FK dependency chains (parents before children).
-- Run this file first, then sql/seed.sql.

CREATE DATABASE IF NOT EXISTS greenstep CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenstep;

-- ── 1. roles ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    role_id INT          AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(30)  NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ── 2. users ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    user_id       INT          AUTO_INCREMENT PRIMARY KEY,
    role_id       INT          NOT NULL,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(225) NOT NULL,
    joined_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- ── 3. badges ─────────────────────────────────────────────────────────────────
-- criteria_json defines unlock conditions evaluated server-side on every dashboard call.
-- Supported types: "total_logs", "streak_days", "category_logs"
CREATE TABLE IF NOT EXISTS badges (
    badge_id      INT          AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    criteria_json JSON         NOT NULL,
    image_url     VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ── 4. user_badges ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_badges (
    badge_id   INT      NOT NULL,
    user_id    INT      NOT NULL,
    awarded_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (badge_id, user_id),
    CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(badge_id)  ON DELETE CASCADE,
    CONSTRAINT fk_ub_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 5. categories ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    category_id INT          AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(60)  NOT NULL UNIQUE,
    description VARCHAR(160) NULL
) ENGINE=InnoDB;

-- ── 6. activity_types ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_types (
    activity_type_id INT           AUTO_INCREMENT PRIMARY KEY,
    category_id      INT           NOT NULL,
    name             VARCHAR(120)  NOT NULL,
    unit             VARCHAR(30)   NOT NULL,
    kg_co2_per_unit  DECIMAL(10,4) NOT NULL,
    CONSTRAINT fk_at_category FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

-- ── 7. activity_logs ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
    activity_log_id  INT           AUTO_INCREMENT PRIMARY KEY,
    user_id          INT           NOT NULL,
    activity_type_id INT           NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    logged_on        DATETIME      NOT NULL,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id)          REFERENCES users(user_id)                      ON DELETE CASCADE,
    CONSTRAINT fk_log_type FOREIGN KEY (activity_type_id) REFERENCES activity_types(activity_type_id)
) ENGINE=InnoDB;

-- ── 8. tips ───────────────────────────────────────────────────────────────────
-- added_by is nullable so system-seeded tips (no associated admin user) are valid.
CREATE TABLE IF NOT EXISTS tips (
    tip_id      INT          AUTO_INCREMENT PRIMARY KEY,
    category_id INT          NULL,
    added_by    INT          NULL,
    title       VARCHAR(160) NOT NULL,
    body        TEXT         NOT NULL,
    source_url  VARCHAR(225) NULL,
    CONSTRAINT fk_tip_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    CONSTRAINT fk_tip_added_by FOREIGN KEY (added_by)    REFERENCES users(user_id)          ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 9. challenges ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS challenges (
    challenge_id         INT           AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(160)  NOT NULL,
    description          TEXT          NULL,
    start_date           DATE          NOT NULL,
    end_date             DATE          NOT NULL,
    target_co2_reduction DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ── 10. challenge_members ─────────────────────────────────────────────────────
-- Composite primary key prevents duplicate memberships without a separate UNIQUE constraint.
CREATE TABLE IF NOT EXISTS challenge_members (
    challenge_id INT NOT NULL,
    user_id      INT NOT NULL,
    PRIMARY KEY (challenge_id, user_id),
    CONSTRAINT fk_cm_challenge FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user      FOREIGN KEY (user_id)      REFERENCES users(user_id)           ON DELETE CASCADE
) ENGINE=InnoDB;
