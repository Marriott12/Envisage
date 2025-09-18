-- Admin functionality database schema updates
-- Run these SQL commands to add admin support to your marketplace

-- Update marketplace_listings table to add admin approval fields
ALTER TABLE marketplace_listings 
ADD COLUMN approved_by INT NULL,
ADD COLUMN approved_at TIMESTAMP NULL,
ADD COLUMN rejected_by INT NULL,
ADD COLUMN rejected_at TIMESTAMP NULL,
ADD COLUMN rejection_reason TEXT NULL,
ADD COLUMN admin_notes TEXT NULL,
ADD INDEX idx_status (status),
ADD INDEX idx_approved_by (approved_by),
ADD INDEX idx_rejected_by (rejected_by),
ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update marketplace_orders table to add admin fields
ALTER TABLE marketplace_orders 
ADD COLUMN admin_notes TEXT NULL,
ADD INDEX idx_payment_status (payment_status);

-- Create admin activity log table
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order status history table for tracking status changes
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (order_id) REFERENCES marketplace_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create notifications table for user notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Update users table to add admin fields if not exists
ALTER TABLE users 
ADD COLUMN is_super_admin BOOLEAN DEFAULT FALSE,
ADD COLUMN permissions JSON NULL,
ADD COLUMN last_login_at TIMESTAMP NULL,
ADD INDEX idx_role (role),
ADD INDEX idx_is_super_admin (is_super_admin);

-- Insert sample admin permissions data (optional)
-- UPDATE users SET permissions = '["manage_listings", "manage_orders", "manage_users", "view_analytics"]' 
-- WHERE role = 'admin';

-- Create sample admin user (change password before use in production)
-- INSERT INTO users (name, email, phone, password, role, is_super_admin, created_at) 
-- VALUES ('Admin User', 'admin@envisage.co.zm', '+260970000000', '$2y$10$example_password_hash', 'admin', TRUE, NOW())
-- ON DUPLICATE KEY UPDATE role = 'admin', is_super_admin = TRUE;

-- Add some useful indexes for performance
ALTER TABLE marketplace_listings 
ADD INDEX idx_category (category),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_user_status (user_id, status);

ALTER TABLE marketplace_orders 
ADD INDEX idx_listing_status (listing_id, status),
ADD INDEX idx_buyer_status (buyer_id, status),
ADD INDEX idx_created_at (created_at);

-- Sample data for testing (optional - remove in production)
-- Add some sample listing statuses for testing
-- UPDATE marketplace_listings SET status = 'pending' WHERE id = 1;
-- UPDATE marketplace_listings SET status = 'approved' WHERE id = 2;
-- UPDATE marketplace_listings SET status = 'rejected' WHERE id = 3;
