-- ══════════════════════════════════════════════════════════════════════════════════════
--  PrintPro — Add Job Tickets Log Table
--  Tables: job_tickets_log
-- ══════════════════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `job_tickets_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `generated_by_user_id` int NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_job_tickets_log_orders` (`order_id`),
  KEY `fk_job_tickets_log_users` (`generated_by_user_id`),
  CONSTRAINT `fk_job_tickets_log_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_tickets_log_users` FOREIGN KEY (`generated_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for quick lookups by order
CREATE INDEX idx_job_tickets_log_order_id ON `job_tickets_log`(`order_id`);
CREATE INDEX idx_job_tickets_log_generated_at ON `job_tickets_log`(`generated_at`);
