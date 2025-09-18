-- Envisage Marketplace Database Schema
-- Created: September 12, 2025
-- Description: Complete database structure for marketplace functionality

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS envisage_db;
USE envisage_db;

-- Set charset and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Users table (if not existing)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    role ENUM('user','seller','admin') DEFAULT 'user',
    phone VARCHAR(20),
    avatar VARCHAR(255),
    bio TEXT,
    location VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table for marketplace listings
CREATE TABLE IF NOT EXISTS marketplace_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    parent_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES marketplace_categories(id) ON DELETE SET NULL,
    INDEX idx_parent_id (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marketplace listings table
CREATE TABLE IF NOT EXISTS marketplace_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'ZMW',
    condition_type ENUM('new','used_like_new','used_good','used_fair','used_poor') DEFAULT 'used_good',
    location VARCHAR(255),
    negotiable BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    favorites INT DEFAULT 0,
    status ENUM('draft','pending','active','sold','rejected','expired') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    expires_at TIMESTAMP NULL,
    featured_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES marketplace_categories(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_price (price),
    INDEX idx_created_at (created_at),
    INDEX idx_featured (featured, featured_until),
    INDEX idx_location (location),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marketplace listing images
CREATE TABLE IF NOT EXISTS marketplace_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    alt_text VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE,
    
    INDEX idx_listing_id (listing_id),
    INDEX idx_primary (listing_id, is_primary),
    INDEX idx_sort_order (listing_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marketplace orders
CREATE TABLE IF NOT EXISTS marketplace_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    listing_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'ZMW',
    status ENUM('pending_payment','paid','shipped','delivered','completed','cancelled','refunded') DEFAULT 'pending_payment',
    
    -- Buyer information
    buyer_name VARCHAR(150),
    buyer_email VARCHAR(255),
    buyer_phone VARCHAR(20),
    
    -- Shipping information
    shipping_address TEXT,
    shipping_method VARCHAR(100),
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    tracking_number VARCHAR(100),
    
    -- Important dates
    payment_due_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    
    -- Additional fields
    notes TEXT,
    cancellation_reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES users(id),
    
    INDEX idx_order_number (order_number),
    INDEX idx_listing_id (listing_id),
    INDEX idx_buyer_id (buyer_id),
    INDEX idx_seller_id (seller_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marketplace payments / Escrow records
CREATE TABLE IF NOT EXISTS marketplace_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- 'mobile_money', 'bank_transfer', 'cash', 'paypal'
    gateway VARCHAR(50), -- 'mtn_momo', 'airtel_money', 'zamtel_kwacha', 'paypal'
    gateway_ref VARCHAR(255),
    transaction_id VARCHAR(255),
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'ZMW',
    status ENUM('initiated','pending','success','failed','refunded','cancelled') DEFAULT 'initiated',
    
    -- Gateway specific fields
    gateway_response TEXT,
    gateway_fee DECIMAL(10,2) DEFAULT 0.00,
    
    -- Escrow information
    escrow_released BOOLEAN DEFAULT FALSE,
    escrow_released_at TIMESTAMP NULL,
    
    -- Refund information
    refund_amount DECIMAL(12,2) DEFAULT 0.00,
    refund_reason TEXT,
    refunded_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id) ON DELETE CASCADE,
    
    INDEX idx_order_id (order_id),
    INDEX idx_gateway_ref (gateway_ref),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User favorites/wishlist
CREATE TABLE IF NOT EXISTS marketplace_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_favorite (user_id, listing_id),
    INDEX idx_user_id (user_id),
    INDEX idx_listing_id (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marketplace reviews and ratings
CREATE TABLE IF NOT EXISTS marketplace_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    reviewer_id INT NOT NULL, -- buyer or seller
    reviewee_id INT NOT NULL, -- seller or buyer
    listing_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT TRUE,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (reviewee_id) REFERENCES users(id),
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id),
    
    UNIQUE KEY unique_review (order_id, reviewer_id),
    INDEX idx_reviewee_id (reviewee_id),
    INDEX idx_listing_id (listing_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages between buyers and sellers
CREATE TABLE IF NOT EXISTS marketplace_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    order_id INT NULL, -- If related to a specific order
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id) ON DELETE SET NULL,
    
    INDEX idx_listing_id (listing_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_order_id (order_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT IGNORE INTO marketplace_categories (name, slug, description, icon, sort_order) VALUES
('Electronics', 'electronics', 'Phones, computers, gadgets and electronic devices', 'fas fa-laptop', 1),
('Vehicles', 'vehicles', 'Cars, motorcycles, bicycles and automotive', 'fas fa-car', 2),
('Fashion', 'fashion', 'Clothing, shoes, accessories and jewelry', 'fas fa-tshirt', 3),
('Home & Garden', 'home-garden', 'Furniture, appliances and home improvement', 'fas fa-home', 4),
('Sports & Recreation', 'sports', 'Sports equipment, outdoor gear and fitness', 'fas fa-football-ball', 5),
('Books & Media', 'books-media', 'Books, movies, music and games', 'fas fa-book', 6),
('Health & Beauty', 'health-beauty', 'Cosmetics, healthcare and personal care', 'fas fa-heart', 7),
('Business & Industrial', 'business', 'Office equipment, tools and supplies', 'fas fa-briefcase', 8),
('Services', 'services', 'Professional services and skilled trades', 'fas fa-wrench', 9),
('Other', 'other', 'Miscellaneous items and collectibles', 'fas fa-box', 10);

-- Create trigger to automatically generate order numbers
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS generate_order_number 
BEFORE INSERT ON marketplace_orders
FOR EACH ROW
BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(LAST_INSERT_ID(), 6, '0'));
    END IF;
END$$
DELIMITER ;

-- Create trigger to automatically generate listing slugs
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS generate_listing_slug 
BEFORE INSERT ON marketplace_listings
FOR EACH ROW
BEGIN
    DECLARE slug_base VARCHAR(255);
    DECLARE slug_final VARCHAR(255);
    DECLARE slug_counter INT DEFAULT 0;
    DECLARE slug_exists INT DEFAULT 1;
    
    -- Generate base slug from title
    SET slug_base = LOWER(TRIM(NEW.title));
    SET slug_base = REPLACE(slug_base, ' ', '-');
    SET slug_base = REGEXP_REPLACE(slug_base, '[^a-zA-Z0-9\-]', '');
    SET slug_base = REGEXP_REPLACE(slug_base, '\-+', '-');
    SET slug_base = TRIM(BOTH '-' FROM slug_base);
    
    -- Ensure slug is unique
    SET slug_final = slug_base;
    
    WHILE slug_exists > 0 DO
        SELECT COUNT(*) INTO slug_exists 
        FROM marketplace_listings 
        WHERE slug = slug_final AND id != COALESCE(NEW.id, 0);
        
        IF slug_exists > 0 THEN
            SET slug_counter = slug_counter + 1;
            SET slug_final = CONCAT(slug_base, '-', slug_counter);
        END IF;
    END WHILE;
    
    SET NEW.slug = slug_final;
END$$
DELIMITER ;

-- Create trigger to update listing favorites count
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_favorites_count_insert
AFTER INSERT ON marketplace_favorites
FOR EACH ROW
BEGIN
    UPDATE marketplace_listings 
    SET favorites = (
        SELECT COUNT(*) 
        FROM marketplace_favorites 
        WHERE listing_id = NEW.listing_id
    )
    WHERE id = NEW.listing_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_favorites_count_delete
AFTER DELETE ON marketplace_favorites
FOR EACH ROW
BEGIN
    UPDATE marketplace_listings 
    SET favorites = (
        SELECT COUNT(*) 
        FROM marketplace_favorites 
        WHERE listing_id = OLD.listing_id
    )
    WHERE id = OLD.listing_id;
END$$
DELIMITER ;

-- Insert a default admin user (password: admin123 - change this!)
INSERT IGNORE INTO users (name, email, password_hash, role, is_verified) VALUES
('Admin User', 'admin@envisage.co.zm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_listings_search ON marketplace_listings(status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_orders_user_status ON marketplace_orders(buyer_id, status);
CREATE INDEX IF NOT EXISTS idx_payments_status_date ON marketplace_payments(status, created_at DESC);

COMMIT;

-- Display success message
SELECT 'Marketplace database schema created successfully!' as status,
       NOW() as created_at;
