-- Update orders with empty status to 'Proof Pending'
UPDATE orders SET status = 'Proof Pending' WHERE status = '' OR status IS NULL;
