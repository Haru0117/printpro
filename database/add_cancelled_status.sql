-- Add 'Cancelled' status to orders table ENUM
-- This allows rejected orders to be properly categorized

ALTER TABLE orders 
MODIFY COLUMN status ENUM(
    'Proof Pending',
    'Proof Pending Review', 
    'Prepress',
    'Printing',
    'Finishing',
    'Shipping',
    'Delivered',
    'Reprint',
    'Cancelled'
) NOT NULL DEFAULT 'Proof Pending';