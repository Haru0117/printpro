-- ============================================================
--  PrintPro — Add Missing Tables (safe, non-destructive)
--  Run this in phpMyAdmin → SQL tab on the `printpro` database
-- ============================================================

USE printpro;

-- ============================================================
-- 3. SUBSCRIPTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS subscriptions (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id    INT UNSIGNED NOT NULL,
  plan         ENUM('free','pro','premium','premium+') NOT NULL DEFAULT 'free',
  status       ENUM('Active','Canceled','Past Due')        NOT NULL DEFAULT 'Active',
  amount       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  applied_on   DATE          NOT NULL,
  renews_on    DATE              NULL,
  canceled_at  TIMESTAMP         NULL,
  updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_subscriptions_client (client_id),
  CONSTRAINT fk_subscriptions_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. PRINT_SPECS
-- ============================================================
CREATE TABLE IF NOT EXISTS print_specs (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  material_name  VARCHAR(120)  NOT NULL,
  base_price     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unit           VARCHAR(40)   NOT NULL DEFAULT 'Sheet',
  status         ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available',
  created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_print_specs_name (material_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data (only inserts if they don't already exist)
INSERT IGNORE INTO print_specs (material_name, base_price, unit) VALUES
  ('A4 Glossy 200gsm',  12.00, 'Sheet'),
  ('100lb Gloss Cover',  9.50, 'Sheet'),
  ('80lb Matte Text',    7.00, 'Sheet'),
  ('14pt Silk Laminate', 15.00,'Sheet'),
  ('100lb Uncoated',     6.50, 'Sheet');

-- ============================================================
-- 5. ORDERS  ← THE KEY TABLE for "Create an Order"
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_number         VARCHAR(20)   NOT NULL,           -- e.g. PPR-049
  client_id            INT UNSIGNED  NOT NULL,
  assigned_operator_id INT UNSIGNED      NULL,
  print_spec_id        INT UNSIGNED      NULL,

  -- Product details (mapped from the Create Order form)
  product_type         VARCHAR(80)   NOT NULL,           -- Flyers, Brochures, etc.
  quantity             INT UNSIGNED  NOT NULL DEFAULT 1,
  size_width           DECIMAL(6,2)      NULL,           -- inches
  size_height          DECIMAL(6,2)      NULL,
  paper_weight         VARCHAR(80)       NULL,
  finish               VARCHAR(80)       NULL,
  print_sides          ENUM('Single Sided','Double Sided') NOT NULL DEFAULT 'Double Sided',
  bleed                TINYINT(1)    NOT NULL DEFAULT 1,
  turnaround           ENUM('Standard','Rush','Priority')  NOT NULL DEFAULT 'Standard',
  shipping_method      ENUM('Ground','Express','Overnight') NOT NULL DEFAULT 'Ground',

  -- Financials
  unit_price           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_rate             DECIMAL(5,2)  NOT NULL DEFAULT 12.00,
  total_amount         DECIMAL(12,2) NOT NULL DEFAULT 0.00,

  -- Workflow
  status               ENUM('Prepress','Printing','Finishing','Shipping','Delivered','Reprint')
                        NOT NULL DEFAULT 'Prepress',
  progress_pct         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  due_date             DATE              NULL,
  notes                TEXT              NULL,
  created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_client   (client_id),
  KEY idx_orders_operator (assigned_operator_id),
  KEY idx_orders_status   (status),
  KEY idx_orders_due_date (due_date),
  CONSTRAINT fk_orders_client   FOREIGN KEY (client_id)            REFERENCES clients (id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_operator FOREIGN KEY (assigned_operator_id) REFERENCES users (id)   ON DELETE SET NULL,
  CONSTRAINT fk_orders_spec     FOREIGN KEY (print_spec_id)        REFERENCES print_specs (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ORDER_STATUS_HISTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS order_status_history (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  changed_by  INT UNSIGNED     NULL,
  status      ENUM('Prepress','Printing','Finishing','Shipping','Delivered','Reprint') NOT NULL,
  note        TEXT             NULL,
  changed_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_osh_order (order_id),
  CONSTRAINT fk_osh_order      FOREIGN KEY (order_id)   REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_osh_changed_by FOREIGN KEY (changed_by) REFERENCES users (id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. FILES
-- ============================================================
CREATE TABLE IF NOT EXISTS files (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  client_id       INT UNSIGNED  NOT NULL,
  order_id        INT UNSIGNED      NULL,
  filename        VARCHAR(255)  NOT NULL,
  original_name   VARCHAR(255)  NOT NULL,
  file_path       VARCHAR(500)  NOT NULL,
  file_type       VARCHAR(20)   NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  folder          VARCHAR(120)      NULL,
  starred         TINYINT(1)    NOT NULL DEFAULT 0,
  trashed         TINYINT(1)    NOT NULL DEFAULT 0,
  uploaded_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_files_client  (client_id),
  KEY idx_files_order   (order_id),
  KEY idx_files_trashed (trashed),
  CONSTRAINT fk_files_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE,
  CONSTRAINT fk_files_order  FOREIGN KEY (order_id)  REFERENCES orders (id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. INVOICES
-- ============================================================
CREATE TABLE IF NOT EXISTS invoices (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  invoice_number  VARCHAR(30)   NOT NULL,
  order_id        INT UNSIGNED  NOT NULL,
  client_id       INT UNSIGNED  NOT NULL,
  amount          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status          ENUM('Paid','Due','Void')   NOT NULL DEFAULT 'Due',
  issued_date     DATE          NOT NULL,
  due_date        DATE              NULL,
  paid_at         TIMESTAMP         NULL,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_invoices_number (invoice_number),
  UNIQUE KEY uq_invoices_order  (order_id),
  KEY idx_invoices_client (client_id),
  KEY idx_invoices_status (status),
  CONSTRAINT fk_invoices_order  FOREIGN KEY (order_id)  REFERENCES orders (id)  ON DELETE RESTRICT,
  CONSTRAINT fk_invoices_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. DEVICES
-- ============================================================
CREATE TABLE IF NOT EXISTS devices (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name              VARCHAR(120)  NOT NULL,
  model             VARCHAR(120)  NOT NULL,
  unit_number       VARCHAR(10)   NOT NULL,
  status            ENUM('online','idle','offline') NOT NULL DEFAULT 'offline',
  ink_level_pct     TINYINT UNSIGNED NOT NULL DEFAULT 100,
  sheets_per_hour   INT UNSIGNED      NULL,
  job_queue_count   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_maintenance  DATE              NULL,
  est_back_online   DATE              NULL,
  created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_devices_unit (unit_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO devices (name, model, unit_number, status, ink_level_pct, sheets_per_hour, job_queue_count) VALUES
  ('Heidelberg Speedmaster', 'XL 106',             '#01', 'online',  78, 16000, 3),
  ('Konica Minolta',         'AccurioPress C14000', '#02', 'online',  92, 14000, 1),
  ('Roland VersaUV',         'LEF2-300',            '#03', 'idle',    55, NULL,  0),
  ('Duplo System 5000',      'Booklet Maker',       '#04', 'online',  61, NULL,  2),
  ('Mimaki CJV300',          'Wide Format',         '#05', 'online',  84, NULL,  1),
  ('Heidelberg GTO 52',      'Offset Press',        '#06', 'offline',  0, NULL,  0);

-- ============================================================
-- 10. DEVICE_JOBS
-- ============================================================
CREATE TABLE IF NOT EXISTS device_jobs (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  device_id    INT UNSIGNED NOT NULL,
  order_id     INT UNSIGNED NOT NULL,
  started_at   TIMESTAMP        NULL,
  completed_at TIMESTAMP        NULL,

  PRIMARY KEY (id),
  KEY idx_dj_device (device_id),
  KEY idx_dj_order  (order_id),
  CONSTRAINT fk_dj_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE CASCADE,
  CONSTRAINT fk_dj_order  FOREIGN KEY (order_id)  REFERENCES orders (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. TEMPLATES
-- ============================================================
CREATE TABLE IF NOT EXISTS templates (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  category      VARCHAR(80)  NOT NULL,
  size          VARCHAR(40)      NULL,
  plan_required ENUM('free','pro') NOT NULL DEFAULT 'free',
  is_popular    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO templates (name, category, size, plan_required, is_popular) VALUES
  ('Bold Summer Flyer',        'Flyer',    '4"×6"',     'free', 0),
  ('Tri-Fold Corporate',       'Brochure', '8.5"×11"',  'free', 1),
  ('Minimalist Business Card', 'Card',     '3.5"×2"',   'free', 0),
  ('Event Poster — Vivid',     'Poster',   '11"×17"',   'pro',  0),
  ('Direct Mail EDDM',         'Mailer',   '6.25"×9"',  'pro',  0),
  ('Annual Report Booklet',    'Booklet',  '8.5"×11"',  'free', 1),
  ('Real Estate Flyer',        'Flyer',    '5.5"×8.5"', 'free', 0),
  ('Luxury Foil Card',         'Card',     '3.5"×2"',   'pro',  0),
  ('Sale Promo Poster',        'Poster',   '18"×24"',   'free', 1);

-- ============================================================
-- 12. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  type        VARCHAR(60)  NOT NULL,
  message     TEXT         NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_notif_user    (user_id),
  KEY idx_notif_is_read (is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- END
-- ============================================================
