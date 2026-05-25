-- =============================================
-- RUN THIS if you already ran archive_migration.sql
-- and are getting the FK constraint error on delete.
-- This removes the bad foreign key from both archive tables.
-- =============================================

USE barangay_system;

-- Fix deleted_users_archive
ALTER TABLE deleted_users_archive
    DROP FOREIGN KEY deleted_users_archive_ibfk_1;

ALTER TABLE deleted_users_archive
    MODIFY COLUMN deleted_by_role VARCHAR(20) NOT NULL;

-- Fix deleted_documents_archive
ALTER TABLE deleted_documents_archive
    DROP FOREIGN KEY deleted_documents_archive_ibfk_1;

ALTER TABLE deleted_documents_archive
    MODIFY COLUMN deleted_by_role VARCHAR(20) NOT NULL;
