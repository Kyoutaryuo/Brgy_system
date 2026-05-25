-- ============================================================
-- Soft Delete Migration for barangay_system
-- Adds deleted_at (soft-delete flag) to users and documents
-- Archive rows expire / are purged after 30 days
-- ============================================================

-- 1. Add soft-delete column to `users`
ALTER TABLE `users`
  ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `created_at`;

-- 2. Add soft-delete column to `documents`
ALTER TABLE `documents`
  ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `created_at`;

-- 3. Add expires_at column to deleted_users_archive (auto-purge marker)
ALTER TABLE `deleted_users_archive`
  ADD COLUMN `expires_at` timestamp NULL DEFAULT NULL AFTER `deleted_at`;

-- 4. Add expires_at column to deleted_documents_archive (auto-purge marker)
ALTER TABLE `deleted_documents_archive`
  ADD COLUMN `expires_at` timestamp NULL DEFAULT NULL AFTER `deleted_at`;

-- 5. Add restore tracking columns to deleted_users_archive
ALTER TABLE `deleted_users_archive`
  ADD COLUMN `restored_at`      timestamp NULL DEFAULT NULL AFTER `expires_at`,
  ADD COLUMN `restored_by_id`   int(11)   NULL DEFAULT NULL AFTER `restored_at`,
  ADD COLUMN `restored_by_name` varchar(100) NULL DEFAULT NULL AFTER `restored_by_id`;

-- 6. Add restore tracking columns to deleted_documents_archive
ALTER TABLE `deleted_documents_archive`
  ADD COLUMN `restored_at`      timestamp NULL DEFAULT NULL AFTER `expires_at`,
  ADD COLUMN `restored_by_id`   int(11)   NULL DEFAULT NULL AFTER `restored_at`,
  ADD COLUMN `restored_by_name` varchar(100) NULL DEFAULT NULL AFTER `restored_by_id`;

-- 7. Backfill expires_at for existing archive rows (30 days from deletion)
UPDATE `deleted_users_archive`
  SET `expires_at` = DATE_ADD(`deleted_at`, INTERVAL 30 DAY)
  WHERE `expires_at` IS NULL;

UPDATE `deleted_documents_archive`
  SET `expires_at` = DATE_ADD(`deleted_at`, INTERVAL 30 DAY)
  WHERE `expires_at` IS NULL;

-- 8. Optional scheduled event — auto-purge expired archive rows
--    Enable the MySQL Event Scheduler first: SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS `purge_expired_archives`
--   ON SCHEDULE EVERY 1 DAY
--   DO
--   BEGIN
--     DELETE FROM deleted_users_archive     WHERE expires_at < NOW() AND restored_at IS NULL;
--     DELETE FROM deleted_documents_archive WHERE expires_at < NOW() AND restored_at IS NULL;
--   END;
