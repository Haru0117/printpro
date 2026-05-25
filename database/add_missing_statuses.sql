-- Add missing statuses to orders table
-- Convert ENUM to VARCHAR to avoid future issues with new statuses

ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Proof Pending';
