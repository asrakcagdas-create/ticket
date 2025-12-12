-- Migration: Add UNIQUE constraint on tickets.ticket_no
-- Description: This prevents duplicate ticket numbers from being created,
--              which is critical for ticket integrity and QR code uniqueness.
-- 
-- Prerequisites:
-- 1. Ensure the tickets table exists
-- 2. Remove any existing duplicate ticket_no values before running this migration
--
-- To check for duplicates before running:
-- SELECT ticket_no, COUNT(*) as count FROM tickets GROUP BY ticket_no HAVING count > 1;
--
-- To apply this migration:
-- mysql -u [username] -p [database_name] < migrations/migration-01-add-unique-ticket-no.sql

-- Add UNIQUE constraint on ticket_no
-- Using 255 as the key length to ensure compatibility with utf8mb4
ALTER TABLE tickets ADD UNIQUE KEY uq_ticket_no (ticket_no(255));
