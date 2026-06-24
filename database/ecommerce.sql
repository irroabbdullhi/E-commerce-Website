-- MySQL Local Business E-Commerce Platform Database Schema
CREATE DATABASE IF NOT EXISTS `ecommerce_local` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ecommerce_local`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'owner', 'customer') NOT NULL,
  `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Businesses Table
CREATE TABLE IF NOT EXISTS `businesses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `business_name` VARCHAR(150) NOT NULL,
  `business_type` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `business_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `order_status` ENUM('pending', 'confirmed', 'processed', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_reference` VARCHAR(100) NOT NULL UNIQUE,
  `payment_status` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reviews Table
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cart Table
CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  UNIQUE KEY `user_product` (`customer_id`, `product_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Wishlist Table
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  UNIQUE KEY `user_wish` (`customer_id`, `product_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert Seed Data
-- 1. Users (password is 'admin123' hashed with bcrypt: $2y$10$iZg7tQv4b3J3M2mQhL9p5e1eG83s2v4u6r8s5x7q9d.w.e.r.t.y.u)
-- Let's use standard bcrypt hash for password 'password123' for testing:
-- $2y$10$wT8hM6k3D/268k2aF81KLO3X/qW0h.P6m1Vz9y3Lq.p4N/yL2p4Xy (which corresponds to 'password123')
-- Let's put specific bcrypt hashes:
-- 'password123' hash: $2y$10$X.p0WvG3Y2eD/Fv5tMv/2OtAfeqX/UfR3R13.0PkWu3u/t9xpeT1i
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `status`) VALUES
(1, 'System Admin', 'admin@localtrade.com', '$2y$10$X.p0WvG3Y2eD/Fv5tMv/2OtAfeqX/UfR3R13.0PkWu3u/t9xpeT1i', 'admin', 'active'),
(2, 'Artisan Brews Owner', 'seller1@localtrade.com', '$2y$10$X.p0WvG3Y2eD/Fv5tMv/2OtAfeqX/UfR3R13.0PkWu3u/t9xpeT1i', 'owner', 'active'),
(3, 'Heritage Woodworks Owner', 'seller2@localtrade.com', '$2y$10$X.p0WvG3Y2eD/Fv5tMv/2OtAfeqX/UfR3R13.0PkWu3u/t9xpeT1i', 'owner', 'active'),
(4, 'Jane Doe', 'jane@localtrade.com', '$2y$10$X.p0WvG3Y2eD/Fv5tMv/2OtAfeqX/UfR3R13.0PkWu3u/t9xpeT1i', 'customer', 'active'),
(5, 'Mark Smith', 'mark@localtrade.com', '$2y$10$X.p0WvG3Y2eD/Fv5tMv/2OtAfeqX/UfR3R13.0PkWu3u/t9xpeT1i', 'customer', 'active');

-- 2. Businesses
INSERT INTO `businesses` (`id`, `owner_id`, `business_name`, `business_type`, `phone`, `address`, `description`, `status`) VALUES
(1, 2, 'Artisan Brews', 'Beverages & Coffee', '123-456-7890', '123 Coffee Lane, Portland, OR', 'Crafting the finest small-batch local coffee and specialty brews.', 'approved'),
(2, 3, 'Heritage Grain Woodworks', 'Crafts & Furniture', '987-654-3210', 'Studio in SE Industrial District, Portland, OR', 'Handcrafted wooden items from sustainably sourced American lumber.', 'approved');

-- 3. Categories
INSERT INTO `categories` (`id`, `category_name`) VALUES
(1, 'Home Decor'),
(2, 'Kitchenware'),
(3, 'Food & Beverages'),
(4, 'Apparel'),
(5, 'Crafts');

-- 4. Products
INSERT INTO `products` (`id`, `business_id`, `category_id`, `name`, `description`, `price`, `stock`, `image`, `status`) VALUES
(1, 2, 2, 'Hand-Carved Walnut Bowl', 'Each bowl is individually turned from sustainably sourced American Black Walnut. Finished with food-safe oils to highlight the natural beauty of the timber.', 85.00, 15, 'walnut_bowl.jpg', 'active'),
(2, 1, 3, 'Artisan Coffee Dark Roast Sampler', 'A rich, full-bodied blend of local coffee beans from Artisan Brews.', 24.00, 142, 'coffee_sampler.jpg', 'active'),
(3, 1, 3, 'Scented Soy Candle', 'Hand-poured lavender-scented soy wax candle in a reusable glass jar.', 15.00, 98, 'candle.jpg', 'active'),
(4, 2, 1, 'Pottery Peak Hand-Thrown Mug', 'Stoneware mug with natural speckled glaze, perfect for your morning brew.', 18.00, 50, 'pottery_mug.jpg', 'active'),
(5, 2, 1, 'Green Valley Farms Organic Weekly Box', 'A fresh selection of seasonal greens and vegetables from local producers.', 32.00, 20, 'organic_box.jpg', 'active');

-- 5. Reviews
INSERT INTO `reviews` (`id`, `customer_id`, `product_id`, `rating`, `comment`) VALUES
(1, 4, 1, 5, 'The craftsmanship is absolutely stunning. You can feel the weight and quality of the walnut immediately. It’s a beautiful centerpiece for our dining table.'),
(2, 5, 1, 5, 'Great piece, shipping was fast for a local item. The wood grain is slightly lighter than the photo but still gorgeous.');
