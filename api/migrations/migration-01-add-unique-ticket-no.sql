-- Migration: Add unique index on tickets.ticket_no
-- Purpose: Prevent duplicate ticket numbers for the 2026-01-31 concert and future events
-- Date: 2025-12-12

-- Add UNIQUE KEY constraint on ticket_no column
-- Note: Adjust the index prefix length if ticket_no column is very long (e.g., VARCHAR(255))
-- For VARCHAR(100) or less, no prefix needed. For longer columns, consider: UNIQUE KEY `uk_ticket_no` (`ticket_no`(100))

ALTER TABLE tickets 
ADD UNIQUE KEY `uk_ticket_no` (`ticket_no`);

-- This constraint will:
-- 1. Prevent duplicate ticket numbers at the database level
-- 2. Work in conjunction with optimistic retry logic in the API
-- 3. Ensure data integrity even under high concurrency
