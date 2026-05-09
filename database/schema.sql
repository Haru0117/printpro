CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `role` enum('client','admin') NOT NULL DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subscription_plan` enum('free','pro','premium','premium+') DEFAULT 'free',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `print_specs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spec_type` enum('paper','finish','size') NOT NULL,
  `name` varchar(255) NOT NULL,
  `price_modifier` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','processing','printed','shipped','cancelled') NOT NULL DEFAULT 'pending',
  `qty` int(11) NOT NULL,
  `paper_id` int(11) NOT NULL,
  `finish_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `artwork_path` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `discount_applied` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_order` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('System Admin', 'admin@printpro.com', '$2y$10$E9h/q.v6jW9yF3F.p.oI.OqLq7E/.T6YdK9e.h/eD1F5oP7g9aB/O', 'admin');

-- Insert some default specs
INSERT INTO `print_specs` (`spec_type`, `name`, `price_modifier`) VALUES
('paper', '130 GSM Standard', 0.05),
('paper', '170 GSM Premium', 0.08),
('paper', '250 GSM Cardstock', 0.15),
('finish', 'Matte', 0.02),
('finish', 'Glossy', 0.03),
('finish', 'Soft Touch', 0.06),
('size', 'A4 (210 x 297 mm)', 0.10),
('size', 'A5 (148 x 210 mm)', 0.06),
('size', 'US Letter', 0.12);
