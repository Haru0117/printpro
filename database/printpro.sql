-- ============================================================
--  PrintPro — Complete Database Schema
--  Engine : MySQL 8.0+
--  Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS printpro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE printpro;

-- ============================================================
-- 1. USERS
--    Central auth table for all roles.
-- ============================================================
CREATE TABLE users (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  name             VARCHAR(120)     NOT NULL,
  email            VARCHAR(180)     NOT NULL,
  password_hash    VARCHAR(255)     NOT NULL,
  role             ENUM('admin','manager','operator','client') NOT NULL DEFAULT 'client',
  subscription_plan ENUM('free','pro','premium','premium+')     DEFAULT 'free',
  status           ENUM('active','suspended')                  NOT NULL DEFAULT 'active',
  two_factor_enabled TINYINT(1)     NOT NULL DEFAULT 0,
  last_login_at    TIMESTAMP        NULL,
  created_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role   (role),
  KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CLIENTS
--    Extended profile for users with role = 'client'.
-- ============================================================
CREATE TABLE clients (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  business_name  VARCHAR(160) NOT NULL,
  industry       VARCHAR(100)     NULL,
  phone          VARCHAR(30)      NULL,
  address        TEXT             NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_clients_user (user_id),
  CONSTRAINT fk_clients_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. SUBSCRIPTIONS
--    One active subscription per client.
-- ============================================================
CREATE TABLE subscriptions (
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
--    Admin-managed material catalogue used in order pricing.
-- ============================================================
CREATE TABLE print_specs (
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

-- Seed data matching the admin dashboard defaults
INSERT INTO print_specs (material_name, base_price, unit) VALUES
  ('A4 Glossy 200gsm',  12.00, 'Sheet'),
  ('100lb Gloss Cover',  9.50, 'Sheet'),
  ('80lb Matte Text',    7.00, 'Sheet'),
  ('14pt Silk Laminate', 15.00,'Sheet'),
  ('100lb Uncoated',     6.50, 'Sheet');

-- ============================================================
-- 5. ORDERS
--    Core order / print-job record.
-- ============================================================
CREATE TABLE orders (
  id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_number         VARCHAR(20)   NOT NULL,           -- e.g. PPR-048
  client_id            INT UNSIGNED  NOT NULL,
  assigned_operator_id INT UNSIGNED      NULL,
  print_spec_id        INT UNSIGNED      NULL,           -- FK → print_specs

  -- Product details
  product_type         VARCHAR(80)   NOT NULL,           -- Brochure, Flyer, etc.
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
  tax_rate             DECIMAL(5,2)  NOT NULL DEFAULT 12.00,  -- percent
  total_amount         DECIMAL(12,2) NOT NULL DEFAULT 0.00,

  -- Workflow
  status               ENUM('Prepress','Printing','Finishing','Shipping','Delivered','Reprint')
                        NOT NULL DEFAULT 'Prepress',
  progress_pct         TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 0-100
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
--    Audit trail — every status change on an order.
-- ============================================================
CREATE TABLE order_status_history (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  changed_by  INT UNSIGNED     NULL,                -- users.id
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
--    Client asset storage (linked optionally to an order).
-- ============================================================
CREATE TABLE files (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  client_id       INT UNSIGNED  NOT NULL,
  order_id        INT UNSIGNED      NULL,
  filename        VARCHAR(255)  NOT NULL,
  original_name   VARCHAR(255)  NOT NULL,
  file_path       VARCHAR(500)  NOT NULL,
  file_type       VARCHAR(20)   NOT NULL,           -- pdf, ai, psd, png, jpg, zip
  file_size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  folder          VARCHAR(120)      NULL,           -- e.g. Summer 2024, Approved Art
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
--    One invoice per order, billing the client.
-- ============================================================
CREATE TABLE invoices (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  invoice_number  VARCHAR(30)   NOT NULL,           -- e.g. INV-2024-10
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
--    Print machines registered in the system.
-- ============================================================
CREATE TABLE devices (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name              VARCHAR(120)  NOT NULL,          -- Heidelberg Speedmaster
  model             VARCHAR(120)  NOT NULL,          -- XL 106
  unit_number       VARCHAR(10)   NOT NULL,          -- #01
  status            ENUM('online','idle','offline') NOT NULL DEFAULT 'offline',
  ink_level_pct     TINYINT UNSIGNED NOT NULL DEFAULT 100,   -- 0-100
  sheets_per_hour   INT UNSIGNED      NULL,
  job_queue_count   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_maintenance  DATE              NULL,
  est_back_online   DATE              NULL,          -- used when offline
  created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_devices_unit (unit_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. DEVICE_JOBS
--     Junction: which device ran which order.
-- ============================================================
CREATE TABLE device_jobs (
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
--     Design templates available in the client portal.
-- ============================================================
CREATE TABLE templates (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  category      VARCHAR(80)  NOT NULL,   -- Flyer, Brochure, Card, Poster, Mailer, Booklet
  size          VARCHAR(40)      NULL,   -- e.g. 4"×6"
  plan_required ENUM('free','pro') NOT NULL DEFAULT 'free',
  is_popular    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed templates matching the client dashboard
INSERT INTO templates (name, category, size, plan_required, is_popular) VALUES
  ('Bold Summer Flyer',       'Flyer',    '4"×6"',     'free', 0),
  ('Tri-Fold Corporate',      'Brochure', '8.5"×11"',  'free', 1),
  ('Minimalist Business Card','Card',     '3.5"×2"',   'free', 0),
  ('Event Poster — Vivid',    'Poster',   '11"×17"',   'pro',  0),
  ('Direct Mail EDDM',        'Mailer',   '6.25"×9"',  'pro',  0),
  ('Annual Report Booklet',   'Booklet',  '8.5"×11"',  'free', 1),
  ('Real Estate Flyer',       'Flyer',    '5.5"×8.5"', 'free', 0),
  ('Luxury Foil Card',        'Card',     '3.5"×2"',   'pro',  0),
  ('Sale Promo Poster',       'Poster',   '18"×24"',   'free', 1);

-- ============================================================
-- 12. NOTIFICATIONS
--     Per-user notification feed.
-- ============================================================
CREATE TABLE notifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  type        VARCHAR(60)  NOT NULL,   -- order_update, device_alert, billing, etc.
  message     TEXT         NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_notif_user    (user_id),
  KEY idx_notif_is_read (is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin user (password = admin123, bcrypt placeholder)
INSERT INTO users (name, email, password_hash, role) VALUES
  ('Alexander Wright', 'admin@printpro.com',  'admin123',   'admin'),
  ('James Holloway',   'james@printpro.com',  'manager123', 'manager'),
  ('Marcus Reid',      'marcus@printpro.com', 'operator123','operator'),
  ('Natasha Brown',    'natasha@printpro.com','manager123', 'manager'),
  ('Frank Castillo',   'frank@printpro.com',  'operator123',   'operator'),
  -- Client users
  ('Ocean Santana',    'ocean@example.com',   'client123',   'client'),
  ('TechStart Inc',    'billing@techstart.com','client123',   'client');

-- Client profiles
INSERT INTO clients (user_id, business_name, industry) VALUES
  (6, 'Ocean Santana Design', 'Creative Agency'),
  (7, 'TechStart Inc',        'Technology');

-- Subscriptions
INSERT INTO subscriptions (client_id, plan, status, amount, applied_on, renews_on) VALUES
  (1, 'Premium', 'Active',   1899.00, '2023-11-12', '2024-11-12'),
  (2, 'Basic',   'Active',    899.00, '2024-01-15', '2024-02-15');

-- Sample orders
INSERT INTO orders (order_number, client_id, assigned_operator_id, product_type, quantity,
                    paper_weight, finish, print_sides, turnaround, shipping_method,
                    unit_price, total_amount, status, progress_pct, due_date)
VALUES
  ('PPR-048', 1, 3, 'Brochure', 1000, '100lb Gloss Cover', 'Matte UV', 'Double Sided', 'Standard', 'Ground',     4.25,  4250.00, 'Prepress',  25, '2024-10-24'),
  ('PPR-045', 2, 3, 'Mailer',   2500, '80lb Matte Text',   'None',     'Single Sided', 'Rush',     'Express',    5.12, 12800.00, 'Reprint',   30, NULL),
  ('PPR-043', 1, 5, 'Poster',    500, '14pt Silk Laminate','Gloss UV', 'Double Sided', 'Standard', 'Ground',    31.00, 15500.00, 'Printing',  80, '2024-10-21'),
  ('PPR-041', 1, 3, 'Flyer',    5000, '100lb Gloss Cover', 'None',     'Single Sided', 'Standard', 'Express',    1.75,  8750.00, 'Shipping',  90, '2024-11-02'),
  ('PPR-038', 2, 5, 'Banner',    200, '100lb Uncoated',    'None',     'Single Sided', 'Standard', 'Ground',    12.25,  2450.00, 'Delivered',100, '2024-10-18');

-- Invoices for completed orders
INSERT INTO invoices (invoice_number, order_id, client_id, amount, status, issued_date, due_date, paid_at)
VALUES
  ('INV-2024-10', 1, 1,  4250.00, 'Paid', '2024-10-18', '2024-10-25', '2024-10-19'),
  ('INV-2024-09', 2, 2, 12800.00, 'Due',  '2024-10-10', '2024-10-31', NULL),
  ('INV-2024-08', 3, 1, 15500.00, 'Paid', '2024-10-01', '2024-10-15', '2024-10-02'),
  ('INV-2024-07', 4, 1,  8750.00, 'Paid', '2024-09-22', '2024-10-06', '2024-09-23'),
  ('INV-2024-06', 5, 2,  2450.00, 'Paid', '2024-09-10', '2024-09-24', '2024-09-11');

-- Devices
INSERT INTO devices (name, model, unit_number, status, ink_level_pct, sheets_per_hour, job_queue_count) VALUES
  ('Heidelberg Speedmaster', 'XL 106',          '#01', 'online',  78, 16000, 3),
  ('Konica Minolta',         'AccurioPress C14000','#02','online', 92, 14000, 1),
  ('Roland VersaUV',         'LEF2-300',        '#03', 'idle',    55, NULL,  0),
  ('Duplo System 5000',      'Booklet Maker',   '#04', 'online',  61, NULL,  2),
  ('Mimaki CJV300',          'Wide Format',     '#05', 'online',  84, NULL,  1),
  ('Heidelberg GTO 52',      'Offset Press',    '#06', 'offline',  0, NULL,  0);

-- ============================================================
-- END OF SCHEMA
-- ============================================================
