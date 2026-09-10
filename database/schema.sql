-- =====================================================================
-- ZeeBroast - Complete Database Schema
-- Crispy Broast Chicken - Online Ordering Website
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `zeebroast` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `zeebroast`;

-- ---------------------------------------------------------------------
-- Site Settings (Admin configurable — logo, colors, contact, etc.)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Admin Users
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
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

-- ---------------------------------------------------------------------
-- Customers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `allow_referral_points` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Admin-controlled: eligible to earn/redeem loyalty points',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Customer Addresses
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
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
  KEY `fk_address_user` (`user_id`),
  CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Categories (Burgers, Broast, Fries, Wings, Drinks ...)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(60) NULL COMMENT 'emoji or icon class used as visual placeholder',
  `image` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Products (Menu Items)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) NULL,
  `image` VARCHAR(255) NULL,
  `icon` VARCHAR(10) NULL COMMENT 'emoji placeholder shown when no image uploaded',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `stock_status` ENUM('in_stock','out_of_stock') NOT NULL DEFAULT 'in_stock',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_slug` (`slug`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Deals / Combos
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `deals`;
CREATE TABLE `deals` (
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

-- Items included inside a deal (for display e.g. "2 Broast + 1 Drink")
DROP TABLE IF EXISTS `deal_items`;
CREATE TABLE `deal_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `deal_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `item_label` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_dealitem_deal` (`deal_id`),
  KEY `fk_dealitem_product` (`product_id`),
  CONSTRAINT `fk_dealitem_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dealitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Coupons
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
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

-- ---------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
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
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Order Items
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
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
  KEY `fk_orderitem_deal` (`deal_id`),
  CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orderitem_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Loyalty / Referral Points Ledger (transaction log; balance = SUM(points))
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `loyalty_points_transactions`;
CREATE TABLE `loyalty_points_transactions` (
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
  KEY `fk_loyalty_tx_order` (`order_id`),
  CONSTRAINT `fk_loyalty_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loyalty_tx_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Contact Messages
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
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

-- ---------------------------------------------------------------------
-- Newsletter / Loyalty subscribers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `subscribers`;
CREATE TABLE `subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscriber_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Default admin login: admin@zeebroast.com / Admin@123
INSERT INTO `admin_users` (`full_name`, `email`, `password`, `role`) VALUES
('Site Administrator', 'admin@zeebroast.com', '$2y$10$vb1UAaldxNMMyh8zxV33qeNjoLzg6D3RpcivYYnrQcW/MPmlCs6xK', 'super_admin');
-- NOTE: hash above corresponds to password: Admin@123 (bcrypt). Change after first login.

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'ZeeBroast'),
('site_tagline', 'Crispy Broast Chicken'),
('logo_image', ''),
('logo_text', 'ZEEBROAST'),
('primary_color', '#E31C25'),
('dark_bg', '#0D0D0D'),
('phone', '+92 300 1234567'),
('email', 'info@zeebroast.com'),
('address', 'Lahore, Pakistan'),
('facebook_url', 'https://facebook.com/zeebroast'),
('instagram_url', 'https://instagram.com/zeebroast'),
('twitter_url', 'https://twitter.com/zeebroast'),
('delivery_fee', '100'),
('free_delivery_threshold', '2000'),
('min_order_amount', '300'),
('estimated_delivery_minutes', '30'),
('halal_badge', '1'),
('currency_symbol', 'Rs.'),
('loyalty_points_enabled', '0'),
('loyalty_spend_amount_per_point', '100'),
('loyalty_point_value', '1'),
('loyalty_earn_on_delivery_fee', '0'),
('loyalty_earn_after_discount', '1'),
('loyalty_award_status', 'delivered'),
('loyalty_max_redeem_percent', '100'),
('hero_title_line1', 'CRISPY.'),
('hero_title_line2', 'JUICY.'),
('hero_title_line3', 'IRRESISTIBLE.'),
('hero_subtitle', 'Experience the best broast chicken in town.'),
('order_id_prefix', 'ZB');

INSERT INTO `categories` (`name`, `slug`, `icon`, `sort_order`) VALUES
('Burgers', 'burgers', '🍔', 1),
('Broast', 'broast', '🍗', 2),
('Fries', 'fries', '🍟', 3),
('Wings', 'wings', '🍗', 4),
('Drinks', 'drinks', '🥤', 5);

INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `image`, `icon`, `is_featured`, `is_popular`, `sort_order`) VALUES
(1, 'Zinger Burger', 'zinger-burger', 'Crispy fried chicken fillet, fresh lettuce, mayo in a soft bun.', 450.00, NULL, '🍔', 1, 1, 1),
(1, 'Cheese Burger', 'cheese-burger', 'Juicy chicken patty topped with melted cheese and special sauce.', 500.00, NULL, '🍔', 0, 1, 2),
(2, 'Broast Leg', 'broast-leg', 'Crispy golden fried chicken leg piece, marinated in secret spices.', 650.00, NULL, '🍗', 1, 1, 1),
(2, 'Broast Chest', 'broast-chest', 'Crispy golden fried chicken chest piece, juicy and flavorful.', 700.00, NULL, '🍗', 0, 0, 2),
(3, 'Loaded Fries', 'loaded-fries', 'Crispy fries loaded with cheese sauce, jalapenos and toppings.', 350.00, NULL, '🍟', 1, 1, 1),
(3, 'Peri Peri Fries', 'peri-peri-fries', 'Crispy fries tossed in spicy peri peri seasoning.', 380.00, NULL, '🍟', 0, 0, 2),
(4, 'Chicken Wings (6pcs)', 'chicken-wings-6pcs', 'Six pieces of crispy fried chicken wings.', 450.00, NULL, '🍗', 1, 0, 1),
(4, 'Hot & Spicy Wings', 'hot-spicy-wings', 'Crispy wings tossed in hot and spicy sauce.', 500.00, NULL, '🍗', 0, 1, 2),
(5, 'Coca-Cola', 'coca-cola', 'Chilled 500ml Coca-Cola.', 120.00, NULL, '🥤', 0, 0, 1);

INSERT INTO `deals` (`title`, `slug`, `description`, `icon`, `original_price`, `deal_price`, `discount_percent`, `sort_order`) VALUES
('Family Deal', 'family-deal', '4 Broast Pieces + 2 Burgers + Fries + 1.5L Drink', '🍗', 2499.00, 1999.00, 20, 1),
('Broast Deal', 'broast-deal', '2 Broast Pieces + Fries + Drink', '🍗', 1599.00, 1299.00, 19, 2),
('Burger Deal', 'burger-deal', '2 Zinger Burgers + Fries + Drink', '🍔', 999.00, 899.00, 10, 3);

INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order_amount`, `status`) VALUES
('WELCOME10', 'percent', 10.00, 500.00, 'active');
