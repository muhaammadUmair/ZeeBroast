-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2026 at 06:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `zeebroast`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `house_no` varchar(120) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `instructions` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `label`, `house_no`, `street`, `city`, `instructions`, `is_default`, `created_at`) VALUES
(1, 1, 'Home', 'House#551, Block H3', 'Johar town', 'Lahore', '', 0, '2026-08-27 21:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','manager','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `full_name`, `email`, `password`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'Site Administrator', 'admin@zeebroast.com', '$2y$10$vb1UAaldxNMMyh8zxV33qeNjoLzg6D3RpcivYYnrQcW/MPmlCs6xK', 'super_admin', 'active', '2026-09-03 17:17:07', '2026-08-25 21:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `icon` varchar(60) DEFAULT NULL COMMENT 'emoji or icon class used as visual placeholder',
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `image`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Burgers', 'burgers', '🍔', 'categories/2db6377f03e895fe21a5840e.png', 1, 'active', '2026-08-25 21:29:13'),
(2, 'Broast', 'broast', '🍗', 'categories/269fbc8a7c3a2f1e16535e99.png', 2, 'active', '2026-08-25 21:29:13'),
(3, 'Fries', 'fries', '🍟', 'categories/3310e034f6c47711f71ae02b.png', 3, 'active', '2026-08-25 21:29:13'),
(4, 'Wings', 'wings', '🍗', 'categories/f5bf95c997666bba782caf37.png', 4, 'active', '2026-08-25 21:29:13'),
(5, 'Drinks', 'drinks', '🥤', NULL, 5, 'active', '2026-08-25 21:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','flat') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expires_at` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `expires_at`, `status`, `created_at`) VALUES
(1, 'WELCOME10', 'percent', 10.00, 500.00, NULL, 'active', '2026-08-25 21:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `deals`
--

CREATE TABLE `deals` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `deal_price` decimal(10,2) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deals`
--

INSERT INTO `deals` (`id`, `title`, `slug`, `description`, `image`, `icon`, `original_price`, `deal_price`, `discount_percent`, `status`, `sort_order`, `created_at`) VALUES
(1, 'Family Deal', 'family-deal', '4 Broast Pieces + 2 Burgers + Fries + 1.5L Drink', 'deals/34fb8a1a40f44ffd98084ec7.png', '🍗', 2499.00, 1999.00, 20, 'active', 1, '2026-08-25 21:29:13'),
(2, 'Broast Deal', 'broast-deal', '2 Broast Pieces + Fries + Drink', 'deals/9f11d4b10c34fd0485bb0717.png', '🍗', 1599.00, 1299.00, 19, 'active', 2, '2026-08-25 21:29:13'),
(3, 'Burger Deal', 'burger-deal', '2 Zinger Burgers + Fries + Drink', 'deals/7a7c8bc77861cc39ce3e2536.png', '🍔', 999.00, 899.00, 10, 'active', 3, '2026-08-25 21:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `deal_items`
--

CREATE TABLE `deal_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `deal_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `item_label` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `guest_name` varchar(150) DEFAULT NULL,
  `guest_phone` varchar(30) DEFAULT NULL,
  `guest_email` varchar(150) DEFAULT NULL,
  `order_type` enum('delivery','takeaway') NOT NULL DEFAULT 'delivery',
  `house_no` varchar(120) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `delivery_instructions` varchar(255) DEFAULT NULL,
  `delivery_time_option` enum('asap','scheduled') NOT NULL DEFAULT 'asap',
  `scheduled_time` datetime DEFAULT NULL,
  `payment_method` enum('cod','jazzcash','easypaisa','card') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','preparing','on_the_way','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `estimated_minutes` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `guest_name`, `guest_phone`, `guest_email`, `order_type`, `house_no`, `street`, `city`, `delivery_instructions`, `delivery_time_option`, `scheduled_time`, `payment_method`, `payment_status`, `subtotal`, `delivery_fee`, `discount`, `coupon_code`, `total`, `status`, `estimated_minutes`, `created_at`, `updated_at`) VALUES
(1, 'ZBDDB43D', NULL, 'dsad', 'dsad', NULL, 'delivery', 'sadd', 'sda', 'Lahore', 'dsdsa', 'asap', NULL, 'cod', 'pending', 1450.00, 100.00, 0.00, NULL, 1550.00, 'pending', 30, '2026-08-26 21:05:49', '2026-08-26 21:05:49'),
(2, 'ZB1617D0', 1, 'Muhammad Umair', '03334418803', NULL, 'delivery', 'House#551, Block H3, Johar town', 'Johar Town', 'Lahore', NULL, 'asap', NULL, 'cod', 'pending', 1100.00, 100.00, 0.00, NULL, 1200.00, 'preparing', 30, '2026-08-27 21:08:17', '2026-08-27 21:10:08'),
(3, 'ZB9229C2', 1, 'Muhammad Umair', '03334418803', NULL, 'delivery', 'House#551, Block H3', 'Johar town', 'Lahore', NULL, 'asap', NULL, 'cod', 'pending', 650.00, 100.00, 0.00, NULL, 750.00, 'pending', 30, '2026-08-27 21:46:01', '2026-08-27 21:46:01'),
(4, 'ZB83AD43', 1, 'Muhammad Umair', '03334418803', NULL, 'delivery', 'House#551, Block H3', 'Johar town', 'Lahore', NULL, 'asap', NULL, 'cod', 'pending', 950.00, 100.00, 0.00, NULL, 1050.00, 'pending', 30, '2026-08-27 21:48:40', '2026-08-27 21:48:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `deal_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `deal_id`, `item_name`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 1, 2, NULL, 'Cheese Burger', 500.00, 2, 1000.00),
(2, 1, 1, NULL, 'Zinger Burger', 450.00, 1, 450.00),
(3, 2, 1, NULL, 'Zinger Burger', 450.00, 1, 450.00),
(4, 2, 3, NULL, 'Broast Leg', 650.00, 1, 650.00),
(5, 3, 3, NULL, 'Broast Leg', 650.00, 1, 650.00),
(6, 4, 7, NULL, 'Chicken Wings (6pcs)', 450.00, 1, 450.00),
(7, 4, 8, NULL, 'Hot & Spicy Wings', 500.00, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL COMMENT 'emoji placeholder shown when no image uploaded',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_popular` tinyint(1) NOT NULL DEFAULT 0,
  `stock_status` enum('in_stock','out_of_stock') NOT NULL DEFAULT 'in_stock',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_available` double NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `sale_price`, `image`, `icon`, `is_featured`, `is_popular`, `stock_status`, `status`, `sort_order`, `created_at`, `updated_at`, `is_available`) VALUES
(1, 1, 'Zinger Burger', 'zinger-burger', 'Crispy fried chicken fillet, fresh lettuce, mayo in a soft bun.', 450.00, NULL, 'products/b6163b552ecc9642847097d1.png', '🍔', 1, 1, 'in_stock', 'active', 1, '2026-08-25 21:29:13', '2026-08-26 19:32:52', 1),
(2, 1, 'Cheese Burger', 'cheese-burger', 'Juicy chicken patty topped with melted cheese and special sauce.', 500.00, NULL, 'products/edc72645ce6b0eff71bf42d8.jpg', '🍔', 0, 1, 'in_stock', 'active', 2, '2026-08-25 21:29:13', '2026-08-26 10:04:24', 1),
(3, 2, 'Broast Leg', 'broast-leg', 'Crispy golden fried chicken leg piece, marinated in secret spices.', 650.00, NULL, 'products/4471d460e65a25aea5dd841a.jpg', '🍗', 1, 1, 'in_stock', 'active', 1, '2026-08-25 21:29:13', '2026-08-26 10:05:58', 1),
(4, 2, 'Broast Chest', 'broast-chest', 'Crispy golden fried chicken chest piece, juicy and flavorful.', 700.00, NULL, 'products/fbb35d8e1bb244511a2db762.png', '🍗', 0, 0, 'in_stock', 'active', 2, '2026-08-25 21:29:13', '2026-08-26 19:33:24', 1),
(5, 3, 'Loaded Fries', 'loaded-fries', 'Crispy fries loaded with cheese sauce, jalapenos and toppings.', 350.00, NULL, 'products/7ca88e2ee419987769a51d13.png', '🍟', 1, 1, 'in_stock', 'active', 1, '2026-08-25 21:29:13', '2026-08-26 19:33:50', 1),
(6, 3, 'Peri Peri Fries', 'peri-peri-fries', 'Crispy fries tossed in spicy peri peri seasoning.', 380.00, NULL, 'products/0879abdc47fb0e3fbb911320.png', '🍟', 0, 0, 'in_stock', 'active', 2, '2026-08-25 21:29:13', '2026-08-26 19:34:46', 1),
(7, 4, 'Chicken Wings (6pcs)', 'chicken-wings-6pcs', 'Six pieces of crispy fried chicken wings.', 450.00, NULL, 'products/4aeb93e67404a90b85831cda.png', '🍗', 1, 0, 'in_stock', 'active', 1, '2026-08-25 21:29:13', '2026-08-26 19:35:11', 1),
(8, 4, 'Hot & Spicy Wings', 'hot-spicy-wings', 'Crispy wings tossed in hot and spicy sauce.', 500.00, NULL, 'products/455d832740f795f017dba58a.png', '🍗', 0, 1, 'in_stock', 'active', 2, '2026-08-25 21:29:13', '2026-08-26 19:35:34', 1),
(9, 5, 'Coca-Cola', 'coca-cola', 'Chilled 500ml Coca-Cola.', 120.00, NULL, 'products/7c7ddf1fc0e26a9757c9c8f1.png', '🥤', 0, 0, 'in_stock', 'active', 1, '2026-08-25 21:29:13', '2026-09-03 12:17:37', 1);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'site_name', 'ZeeBroast'),
(2, 'site_tagline', 'Crispy Broast Chicken'),
(3, 'logo_text', 'ZEEBROAST'),
(4, 'primary_color', '#E31C25'),
(5, 'dark_bg', '#0D0D0D'),
(6, 'phone', '+92 303 5636080'),
(7, 'email', 'info@zeebroast.com'),
(8, 'address', 'Lahore, Pakistan'),
(9, 'facebook_url', 'https://facebook.com/zeebroast'),
(10, 'instagram_url', 'https://instagram.com/zeebroast'),
(11, 'twitter_url', 'https://twitter.com/zeebroast'),
(12, 'delivery_fee', '100'),
(13, 'free_delivery_threshold', '2000'),
(14, 'min_order_amount', '300'),
(15, 'estimated_delivery_minutes', '30'),
(16, 'halal_badge', '1'),
(17, 'currency_symbol', 'Rs.'),
(18, 'hero_title_line1', 'CRISPY.'),
(19, 'hero_title_line2', 'JUICY.'),
(20, 'hero_title_line3', 'IRRESISTIBLE.'),
(21, 'hero_subtitle', 'Experience the best broast chicken in town.'),
(22, 'order_id_prefix', 'ZB'),
(23, 'bucket_image', 'assets/img/Starting_banner.png'),
(24, 'rider_image', 'assets/img/Rider.png'),
(25, 'drinks_sides_image', 'assets/img/Drinks_and_sides.png'),
(26, 'fcon_image', 'assets/img/favicon.png'),
(27, 'coke_image', 'assets/img/Coke.png'),
(28, 'logo_image', 'assets/img/logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `status`, `created_at`) VALUES
(1, 'Muhammad Umair', 'muhaammad.umair@gmail.com', '03334418803', '$2y$10$rjnR5yhcwUcfYeINz8ZsmuYtEm2iIz6c0sWkfwwgShU9faAEknFjO', 'active', '2026-08-27 21:06:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_address_user` (`user_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_slug` (`slug`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coupon_code` (`code`);

--
-- Indexes for table `deals`
--
ALTER TABLE `deals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_deal_slug` (`slug`);

--
-- Indexes for table `deal_items`
--
ALTER TABLE `deal_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dealitem_deal` (`deal_id`),
  ADD KEY `fk_dealitem_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_code` (`order_code`),
  ADD KEY `fk_order_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`),
  ADD KEY `fk_orderitem_deal` (`deal_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_slug` (`slug`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_setting_key` (`setting_key`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_subscriber_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deal_items`
--
ALTER TABLE `deal_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deal_items`
--
ALTER TABLE `deal_items`
  ADD CONSTRAINT `fk_dealitem_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dealitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
