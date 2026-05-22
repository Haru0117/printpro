-- Add rejection_reason column to orders table
ALTER TABLE orders ADD COLUMN rejection_reason TEXT NULL AFTER notes;
