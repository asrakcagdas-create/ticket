-- Migration: Add unique constraint on tickets.ticket_no
-- Purpose: Prevent duplicate ticket numbers and ensure data integrity
-- Date: 2025-12-12
-- 
-- This migration adds a UNIQUE constraint on the ticket_no column in the tickets table
-- to prevent race conditions that could create duplicate ticket numbers.
--
-- Prerequisites:
-- - The tickets table must exist
-- - Any existing duplicate ticket_no values must be resolved before running this migration
--
-- To apply this migration:
-- mysql -u [username] -p [database_name] < migrations/migration-01-add-unique-ticket-no.sql
--
-- Note: If you encounter errors about duplicate keys, you'll need to first identify and
-- resolve any existing duplicate ticket numbers in your database.

USE ciik8ph9c_ticket;

-- Add UNIQUE constraint on ticket_no
-- Using 255 character length as ticket_no format is "7S-YYYYMMDD-XXXXXX" which is much shorter
ALTER TABLE tickets ADD UNIQUE KEY uq_ticket_no (ticket_no(255));
