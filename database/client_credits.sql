-- ============================================================
--  PrintPro — Client Credits System
--  Add this to the printpro database
-- ============================================================

USE printpro;

-- ============================================================
-- CLIENT_CREDITS
-- Table to store credit balances for clients
-- ============================================================
CREATE TABLE IF NOT EXISTS client_credits (
  id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  client_id       INT UNSIGNED     NOT NULL,
  balance         DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_client_credits_client (client_id),
  CONSTRAINT fk_client_credits_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CREDIT_TRANSACTIONS
-- Table to track all credit additions and deductions
-- ============================================================
CREATE TABLE IF NOT EXISTS credit_transactions (
  id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  client_id       INT UNSIGNED     NOT NULL,
  transaction_type ENUM('add','deduct') NOT NULL,
  amount          DECIMAL(12,2)    NOT NULL,
  description     VARCHAR(255)    DEFAULT NULL,
  order_id        INT UNSIGNED    DEFAULT NULL,  -- If deducted for an order
  created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_credit_transactions_client (client_id),
  KEY idx_credit_transactions_order (order_id),
  CONSTRAINT fk_credit_transactions_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE,
  CONSTRAINT fk_credit_transactions_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRIGGER: Update credit balance on transaction insert
-- ============================================================
DELIMITER //

CREATE TRIGGER update_credit_balance_after_insert
AFTER INSERT ON credit_transactions
FOR EACH ROW
BEGIN
  IF NEW.transaction_type = 'add' THEN
    UPDATE client_credits SET balance = balance + NEW.amount, updated_at = CURRENT_TIMESTAMP WHERE client_id = NEW.client_id;
  ELSEIF NEW.transaction_type = 'deduct' THEN
    UPDATE client_credits SET balance = balance - NEW.amount, updated_at = CURRENT_TIMESTAMP WHERE client_id = NEW.client_id;
  END IF;
END //

DELIMITER ;

-- ============================================================
-- DEFAULT CREDITS FOR EXISTING CLIENTS
-- Set a default credit amount (e.g., 10000.00) for all existing clients
-- Adjust the amount as needed
-- ============================================================
INSERT IGNORE INTO client_credits (client_id, balance)
SELECT id, 10000.00 FROM clients;

-- ============================================================
-- TRIGGER: Auto-create credit record for new clients
-- ============================================================
DELIMITER //

CREATE TRIGGER create_default_credits_after_client_insert
AFTER INSERT ON clients
FOR EACH ROW
BEGIN
  INSERT INTO client_credits (client_id, balance) VALUES (NEW.id, 10000.00);
END //

DELIMITER ;