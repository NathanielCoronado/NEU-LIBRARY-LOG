-- =====================================================================
--  NEU LIBRARY LOG — DATABASE SCHEMA
--  HOW TO IMPORT
--    1. Create/select your database first (on InfinityFree: Control Panel >
--       MySQL Databases, then open it in phpMyAdmin).
--    2. phpMyAdmin > Import > choose this file > Go.
--  For a local XAMPP setup you can create one first with:
--       CREATE DATABASE neu_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--
--  NOTE: Rebuilt from the queries the PHP files use. If you still have the
--  original database, export it from phpMyAdmin instead — that is always
--  the most accurate copy.
-- =====================================================================


-- ---------------------------------------------------------------------
-- Registered accounts (email/password or Google)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    first_name        VARCHAR(100) NOT NULL,
    last_name         VARCHAR(100) NOT NULL DEFAULT '',
    email             VARCHAR(191) NOT NULL UNIQUE,
    password          VARCHAR(255) NULL,              -- NULL for Google-only accounts
    profile_pic       TEXT NULL,
    status            VARCHAR(30)  NULL,
    role              VARCHAR(10)  NOT NULL DEFAULT 'USER',   -- USER | ADMIN
    is_verified       TINYINT(1)   NOT NULL DEFAULT 0,
    is_blocked        TINYINT(1)   NOT NULL DEFAULT 0,
    verification_code VARCHAR(100) NULL,
    reset_token       VARCHAR(64)  NULL,
    token_expiry      DATETIME     NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- People currently INSIDE the library (moved to library_logs on time-out)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS active_sessions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_type     VARCHAR(50)  NULL,
    id_number     VARCHAR(50)  NULL,
    contact       VARCHAR(30)  NULL,
    first_name    VARCHAR(100) NULL,
    middle_name   VARCHAR(100) NULL,
    last_name     VARCHAR(100) NULL,
    suffix        VARCHAR(20)  NULL,
    course        VARCHAR(150) NULL,
    reason        VARCHAR(100) NULL,
    others_detail TEXT         NULL,
    time_in       VARCHAR(30)  NULL,   -- stored as text, e.g. "09:15:02 AM"
    date_visited  DATE         NULL,
    email         VARCHAR(191) NULL,
    INDEX idx_as_email (email),
    INDEX idx_as_idnum (id_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Completed visits (history)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS library_logs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_type     VARCHAR(50)  NULL,
    id_number     VARCHAR(50)  NULL,
    contact       VARCHAR(30)  NULL,
    first_name    VARCHAR(100) NULL,
    middle_name   VARCHAR(100) NULL,
    last_name     VARCHAR(100) NULL,
    suffix        VARCHAR(20)  NULL,
    course        VARCHAR(150) NULL,
    reason        VARCHAR(100) NULL,
    others_detail TEXT         NULL,
    time_in       VARCHAR(30)  NULL,
    time_out      VARCHAR(30)  NULL,
    date_visited  DATE         NULL,
    email         VARCHAR(191) NULL,
    status        VARCHAR(30)  NULL,   -- Completed | Blocked | NULL (admin time-out)
    INDEX idx_ll_email (email),
    INDEX idx_ll_idnum (id_number),
    INDEX idx_ll_date (date_visited)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- One profile per ID number (used by the admin "Add Visitor" modal)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_masterlist (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_number   VARCHAR(50)  NOT NULL UNIQUE,
    first_name  VARCHAR(100) NULL,
    middle_name VARCHAR(100) NULL,
    last_name   VARCHAR(100) NULL,
    suffix      VARCHAR(20)  NULL,
    contact     VARCHAR(30)  NULL,
    user_type   VARCHAR(50)  NULL,
    course      VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Policy-violation flags
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitor_flags (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   VARCHAR(50)  NULL,
    student_name VARCHAR(255) NULL,
    reason       TEXT         NULL,
    flagged_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status       VARCHAR(20)  NOT NULL DEFAULT 'FLAG',
    INDEX idx_vf_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
