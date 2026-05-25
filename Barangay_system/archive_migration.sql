-- =============================================
-- ARCHIVE MIGRATION
-- Run this ONCE after your existing database is set up.
-- =============================================

USE barangay_system;

-- Archive table for deleted users
-- NOTE: deleted_by_id is a plain INT (no FK) so that demo/admin accounts
--       with IDs not in the users table (e.g. id=0) can still delete users.
--       Audit identity is preserved via deleted_by_name and deleted_by_role.
CREATE TABLE IF NOT EXISTS deleted_users_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Snapshot of the deleted user
    original_user_id  INT NOT NULL,
    full_name         VARCHAR(100) NOT NULL,
    username          VARCHAR(50)  NOT NULL,
    email             VARCHAR(100),
    address           TEXT,
    contact_number    VARCHAR(20),
    role              ENUM('user','staff','admin') NOT NULL,
    user_status       ENUM('active','inactive')   NOT NULL,
    user_created_at   TIMESTAMP NULL,

    -- Audit: who deleted this record (stored as text, no FK)
    deleted_by_id     INT          NOT NULL,
    deleted_by_name   VARCHAR(100) NOT NULL,
    deleted_by_role   VARCHAR(20)  NOT NULL,
    deletion_reason   TEXT,
    deleted_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address        VARCHAR(45)
);

-- Archive table for deleted document types
CREATE TABLE IF NOT EXISTS deleted_documents_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Snapshot of the deleted document
    original_document_id INT         NOT NULL,
    document_name        VARCHAR(100) NOT NULL,
    description          TEXT,
    requirements         TEXT,
    processing_days      INT,
    fee                  DECIMAL(10,2),
    document_status      ENUM('active','inactive') NOT NULL,
    document_created_at  TIMESTAMP NULL,

    -- Audit: who deleted this record (stored as text, no FK)
    deleted_by_id        INT          NOT NULL,
    deleted_by_name      VARCHAR(100) NOT NULL,
    deleted_by_role      VARCHAR(20)  NOT NULL,
    deletion_reason      TEXT,
    deleted_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address           VARCHAR(45)
);
