-- Migration: Add UNIQUE constraint on tickets.ticket_no
-- Purpose: Prevent duplicate ticket numbers and support optimistic retry logic
-- Date: 2025-12-12

-- Add UNIQUE constraint on ticket_no column
-- Note: Adjust column length (255) if your ticket_no field has different requirements
ALTER TABLE tickets ADD UNIQUE KEY uq_ticket_no (ticket_no(255));

-- This migration prevents race conditions when creating tickets concurrently
-- The API now implements optimistic retry logic to handle duplicate key errors gracefully
