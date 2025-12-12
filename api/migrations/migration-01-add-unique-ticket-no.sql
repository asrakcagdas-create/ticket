-- Migration: Add UNIQUE constraint on tickets.ticket_no
-- Date: 2025-12-12
-- Purpose: Prevent duplicate ticket numbers and enable optimistic retry logic

-- IMPORTANT: Backup your database before running this migration!
-- Command: mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

-- NOTE: If you have a large tickets table, consider adding the index in smaller chunks
-- or during off-peak hours to avoid long table locks.

-- NOTE: If your ticket_no values are longer than 191 characters, you may need to adjust
-- the prefix length: UNIQUE KEY `unique_ticket_no` (ticket_no(191))

USE ciik8ph9c_ticket;

-- Add UNIQUE constraint on ticket_no column
ALTER TABLE tickets 
  ADD UNIQUE KEY `unique_ticket_no` (ticket_no);

-- Verify the constraint was added
SHOW INDEX FROM tickets WHERE Key_name = 'unique_ticket_no';
