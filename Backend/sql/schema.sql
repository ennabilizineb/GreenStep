-- GreenStep starter schema (MINIMAL, for backend scaffold smoke-testing only).
-- OWNERSHIP: the authoritative schema + ER diagram belong to Member 3 (Database & Security Lead).
-- This file exists only so the API can boot and the auth flow can be demonstrated.
-- Replace/extend with the finalised schema before PR2.

CREATE DATABASE IF NOT EXISTS greenstep CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenstep;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120)  NOT NULL,
    email         VARCHAR(190)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('user','leader','admin') NOT NULL DEFAULT 'user',
    joined_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_types (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    category        VARCHAR(60)  NOT NULL,
    name            VARCHAR(120) NOT NULL,
    unit            VARCHAR(30)  NOT NULL,
    kg_co2_per_unit DECIMAL(10,4) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    activity_type_id INT NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    logged_on        DATE NOT NULL,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_type FOREIGN KEY (activity_type_id) REFERENCES activity_types(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tips (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(160) NOT NULL,
    body       TEXT NOT NULL,
    category   VARCHAR(60) NOT NULL DEFAULT 'general',
    source_url VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS challenges (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(160) NOT NULL,
    description          TEXT NULL,
    start_date           DATE NOT NULL,
    end_date             DATE NOT NULL,
    target_co2_reduction DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS challenge_members (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    user_id      INT NOT NULL,
    UNIQUE KEY uq_member (challenge_id, user_id),
    CONSTRAINT fk_cm_challenge FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
