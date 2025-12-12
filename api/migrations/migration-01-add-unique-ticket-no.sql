-- Migration: Add UNIQUE constraint on tickets.ticket_no
-- 
-- IMPORTANT: Backup your database before running this migration!
-- 
-- This migration adds a UNIQUE KEY constraint on the ticket_no column
-- to prevent duplicate ticket numbers from being created.
--
-- The optimistic retry logic in the application will handle any
-- duplicate key conflicts by retrying the INSERT operation.
--
-- If you have a very large tickets table, you may need to adjust
-- the prefix length in the index definition to optimize performance.

USE ciik8ph9c_ticket;

-- Add UNIQUE constraint on ticket_no
ALTER TABLE tickets 
ADD UNIQUE KEY uk_ticket_no (ticket_no);

-- Verify the constraint was added
SHOW INDEX FROM tickets WHERE Key_name = 'uk_ticket_no';
