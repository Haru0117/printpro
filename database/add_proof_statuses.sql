-- Add Proof Pending and Proof Pending Review to status ENUM
ALTER TABLE orders MODIFY COLUMN status ENUM('Proof Pending','Proof Pending Review','Prepress','Printing','Finishing','Shipping','Delivered','Reprint') NOT NULL DEFAULT 'Proof Pending';
