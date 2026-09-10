-- =====================================================================
-- ZeeBroast - Safe/Idempotent Schema Update
-- Brings an EXISTING database up to date with the loyalty/referral points
-- feature without touching tables/data that already exist.
--
-- Safe to run repeatedly and against:
--   - a fresh database (creates everything from scratch), or
--   - an existing database that already has some/most tables and columns
--     (only the missing pieces are added; existing data is never touched).
--
-- Requirements: MariaDB 10.0.2+ or MySQL 8.0.29+ (needed for the
-- "ADD COLUMN IF NOT EXISTS" / "CREATE TABLE IF NOT EXISTS" syntax used below).
-- Your dump reports MariaDB 10.4.32, so this is compatible.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Core tables (created only if missing — existing tables/data are untouched)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin','manager','staff') NOT NULL DEFAULT 'staff',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `address` TEXT NULL,
  `allow_referral_points` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Admin-controlled: eligible to earn/redeem loyalty points',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(50) NULL,
  `house_no` VARCHAR(120) NULL,
  `street` VARCHAR(150) NULL,
  `city` VARCHAR(100) NULL,
  `instructions` VARCHAR(255) NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_address_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(60) NULL,
  `image` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) NULL,
  `image` VARCHAR(255) NULL,
  `icon` VARCHAR(10) NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `stock_status` ENUM('in_stock','out_of_stock') NOT NULL DEFAULT 'in_stock',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_slug` (`slug`),
  KEY `fk_product_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `deals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `icon` VARCHAR(10) NULL,
  `original_price` DECIMAL(10,2) NOT NULL,
  `deal_price` DECIMAL(10,2) NOT NULL,
  `discount_percent` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deal_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `deal_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `deal_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `item_label` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_dealitem_deal` (`deal_id`),
  KEY `fk_dealitem_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `discount_type` ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `is_referral` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Referral codes: unlimited use, never discount, trigger loyalty points',
  `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `expires_at` DATE NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(20) NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `guest_name` VARCHAR(150) NULL,
  `guest_phone` VARCHAR(30) NULL,
  `guest_email` VARCHAR(150) NULL,
  `order_type` ENUM('delivery','takeaway') NOT NULL DEFAULT 'delivery',
  `house_no` VARCHAR(120) NULL,
  `street` VARCHAR(150) NULL,
  `city` VARCHAR(100) NULL,
  `delivery_instructions` VARCHAR(255) NULL,
  `delivery_time_option` ENUM('asap','scheduled') NOT NULL DEFAULT 'asap',
  `scheduled_time` DATETIME NULL,
  `payment_method` ENUM('cod','jazzcash','easypaisa','card') NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `coupon_code` VARCHAR(50) NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `status` ENUM('pending','confirmed','preparing','on_the_way','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `estimated_minutes` INT NOT NULL DEFAULT 30,
  `loyalty_points_earned` INT UNSIGNED NOT NULL DEFAULT 0,
  `loyalty_points_awarded` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Guard: prevents awarding the same order twice',
  `loyalty_points_reversed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Guard: prevents reversing the same order twice',
  `loyalty_points_redeemed` INT UNSIGNED NOT NULL DEFAULT 0,
  `loyalty_discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `loyalty_referral_triggered` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Set when a referral code was applied, used to gate point earning for this order',
  `loyalty_referral_owner_id` INT UNSIGNED NULL COMMENT 'Customer who owns the referral code used on this order and who receives the earned points',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_code` (`order_code`),
  KEY `fk_order_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `deal_id` INT UNSIGNED NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `line_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_orderitem_order` (`order_id`),
  KEY `fk_orderitem_product` (`product_id`),
  KEY `fk_orderitem_deal` (`deal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `subject` VARCHAR(200) NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriber_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_coupon_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `status` ENUM('unused','used','expired') NOT NULL DEFAULT 'unused',
  `expires_at` DATE NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_coupon_code` (`code`),
  KEY `fk_user_coupon_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `loyalty_points_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NULL,
  `transaction_type` ENUM('EARN','REDEEM','ADJUSTMENT','REFUND','EXPIRATION') NOT NULL,
  `points` INT NOT NULL COMMENT 'Signed: positive = credit, negative = debit',
  `monetary_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `description` VARCHAR(255) NULL,
  `created_by_admin_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_loyalty_tx_user` (`user_id`),
  KEY `fk_loyalty_tx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Patch existing tables that pre-date the loyalty/referral feature
-- (no-op if the column is already there — safe to re-run)
-- ---------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `allow_referral_points` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `is_referral` TINYINT(1) NOT NULL DEFAULT 0 AFTER `discount_value`;

ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `loyalty_points_earned` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `loyalty_points_awarded` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `loyalty_points_reversed` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `loyalty_points_redeemed` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `loyalty_discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `loyalty_referral_triggered` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `loyalty_referral_owner_id` INT UNSIGNED NULL;

ALTER TABLE `loyalty_points_transactions`
  ADD COLUMN IF NOT EXISTS `created_by_admin_id` INT UNSIGNED NULL AFTER `description`;

-- ---------------------------------------------------------------------
-- Foreign keys — only added when creating a brand-new table above; MariaDB/MySQL
-- have no reliable "ADD CONSTRAINT IF NOT EXISTS", so pre-existing tables (as in
-- your dump) are left as-is rather than risk a failed/duplicate constraint.
-- Skip this block entirely if any of these tables already have their FKs.
-- ---------------------------------------------------------------------

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_address_user'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `addresses` ADD CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_product_category'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `products` ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_dealitem_deal'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `deal_items` ADD CONSTRAINT `fk_dealitem_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fk_dealitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_order_user'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `orders` ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_orderitem_order'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `order_items` ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL, ADD CONSTRAINT `fk_orderitem_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_user_coupon_user'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE `user_coupon_codes` ADD CONSTRAINT `fk_user_coupon_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- loyalty_points_transactions: FK intentionally NOT auto-added here. Some environments created
-- this table with BIGINT id columns (incompatible with users/orders' INT UNSIGNED ids), which
-- makes ADD CONSTRAINT fail with errno 150. Referential integrity for this table is enforced at
-- the application layer (includes/loyalty.php) instead. If your table already uses matching
-- INT UNSIGNED columns, you can add the FKs yourself:
--   ALTER TABLE `loyalty_points_transactions`
--     ADD CONSTRAINT `fk_loyalty_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
--     ADD CONSTRAINT `fk_loyalty_tx_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- Default loyalty/referral settings (only inserted if the key is missing)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'ZeeBroast'),
('site_tagline', 'Crispy Broast Chicken'),
('currency_symbol', 'Rs.'),
('delivery_fee', '100'),
('free_delivery_threshold', '2000'),
('min_order_amount', '300'),
('estimated_delivery_minutes', '30'),
('order_id_prefix', 'ZB'),
('loyalty_points_enabled', '0'),
('loyalty_spend_amount_per_point', '100'),
('loyalty_point_value', '1'),
('loyalty_earn_on_delivery_fee', '0'),
('loyalty_earn_after_discount', '1'),
('loyalty_award_status', 'delivered'),
('loyalty_max_redeem_percent', '100');
