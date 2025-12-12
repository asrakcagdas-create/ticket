-- Migration 01: Add unique index on tickets.ticket_no
-- This prevents duplicate ticket numbers and enables optimistic retry logic
-- 
-- NOTE: If your ticket_no column is VARCHAR(255) or larger, you may need to adjust
-- the index prefix length depending on your MySQL version and charset settings.
-- For utf8mb4, the max key length is typically 767 bytes (or 3072 for INNODB with
-- innodb_large_prefix). Adjust the index length if needed:
--   For VARCHAR(255): UNIQUE KEY `idx_ticket_no` (`ticket_no`(191))
--   For VARCHAR(100): UNIQUE KEY `idx_ticket_no` (`ticket_no`)
--
-- To apply this migration:
--   mysql -u username -p database_name < migration-01-add-unique-ticket-no.sql

ALTER TABLE tickets 
  ADD UNIQUE KEY `idx_ticket_no` (`ticket_no`);
