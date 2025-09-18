-- Server-side validation and security database schema updates
-- Run these SQL commands to add validation and security support

-- Create rate limiting log table
CREATE TABLE IF NOT EXISTS rate_limit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_user (action, user_id),
    INDEX idx_action_ip (action, ip_address),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create email alerts log table
CREATE TABLE IF NOT EXISTS email_alerts_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    recipients TEXT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    data JSON,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_sent_at (sent_at)
);

-- Update marketplace_listings table for content moderation
ALTER TABLE marketplace_listings 
ADD COLUMN content_flags JSON NULL COMMENT 'Detected content flags for moderation',
ADD COLUMN validation_score INT DEFAULT 0 COMMENT 'Content validation score (0-100)',
ADD COLUMN last_reviewed_at TIMESTAMP NULL COMMENT 'Last admin review timestamp',
ADD COLUMN auto_flagged BOOLEAN DEFAULT FALSE COMMENT 'Automatically flagged by system';

-- Create content moderation log table
CREATE TABLE IF NOT EXISTS content_moderation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    flags_detected JSON,
    profanity_words JSON,
    high_risk_words JSON,
    validation_score INT DEFAULT 0,
    action_taken ENUM('approved', 'flagged', 'rejected', 'review_required') NOT NULL,
    reviewer_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_listing_id (listing_id),
    INDEX idx_action_taken (action_taken),
    INDEX idx_created_at (created_at),
    INDEX idx_validation_score (validation_score),
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create image validation log table
CREATE TABLE IF NOT EXISTS image_validation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    original_filename VARCHAR(255),
    stored_filename VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(50),
    dimensions VARCHAR(20),
    validation_status ENUM('passed', 'failed', 'suspicious') DEFAULT 'passed',
    validation_errors JSON,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_listing_id (listing_id),
    INDEX idx_validation_status (validation_status),
    INDEX idx_processed_at (processed_at),
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE CASCADE
);

-- Create security incidents log table
CREATE TABLE IF NOT EXISTS security_incidents_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type ENUM('rate_limit_abuse', 'content_violation', 'suspicious_activity', 'image_violation') NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    details JSON,
    auto_resolved BOOLEAN DEFAULT FALSE,
    admin_notified BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_incident_type (incident_type),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add user behavior tracking fields
ALTER TABLE users 
ADD COLUMN listings_created INT DEFAULT 0 COMMENT 'Total listings created by user',
ADD COLUMN listings_flagged INT DEFAULT 0 COMMENT 'Number of listings flagged for violation',
ADD COLUMN account_status ENUM('active', 'suspended', 'banned', 'under_review') DEFAULT 'active',
ADD COLUMN risk_score INT DEFAULT 0 COMMENT 'User risk score (0-100)',
ADD COLUMN last_violation_at TIMESTAMP NULL COMMENT 'Last content violation timestamp',
ADD INDEX idx_account_status (account_status),
ADD INDEX idx_risk_score (risk_score);

-- Create user violations log table
CREATE TABLE IF NOT EXISTS user_violations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    violation_type ENUM('content_policy', 'spam', 'fraud', 'harassment', 'fake_listing', 'other') NOT NULL,
    listing_id INT NULL,
    severity ENUM('minor', 'moderate', 'severe', 'critical') DEFAULT 'minor',
    description TEXT,
    action_taken ENUM('warning', 'listing_removed', 'account_suspended', 'account_banned') NULL,
    admin_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_violation_type (violation_type),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create automated actions log table
CREATE TABLE IF NOT EXISTS automated_actions_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type ENUM('auto_flag', 'auto_reject', 'rate_limit', 'image_reject', 'content_filter') NOT NULL,
    target_type ENUM('listing', 'user', 'image', 'comment') NOT NULL,
    target_id INT NOT NULL,
    trigger_reason TEXT,
    confidence_score DECIMAL(5,2) DEFAULT 0.00 COMMENT 'AI confidence score (0-100)',
    human_review_required BOOLEAN DEFAULT FALSE,
    reviewed_by INT NULL,
    review_outcome ENUM('confirmed', 'overturned', 'modified') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    INDEX idx_action_type (action_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_human_review_required (human_review_required),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add configuration table for dynamic settings
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    config_type ENUM('string', 'integer', 'float', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key),
    INDEX idx_category (category),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default configuration values
INSERT INTO system_config (config_key, config_value, config_type, description, category) VALUES
('max_images_per_listing', '10', 'integer', 'Maximum number of images allowed per listing', 'validation'),
('max_image_size_mb', '5', 'integer', 'Maximum image size in MB', 'validation'),
('max_total_upload_mb', '20', 'integer', 'Maximum total upload size in MB', 'validation'),
('rate_limit_create_listing_hourly', '5', 'integer', 'Rate limit for creating listings per hour per user', 'rate_limiting'),
('rate_limit_create_listing_daily', '20', 'integer', 'Rate limit for creating listings per day per user', 'rate_limiting'),
('profanity_filter_enabled', 'true', 'boolean', 'Enable profanity filtering', 'content_moderation'),
('auto_flag_high_risk_content', 'true', 'boolean', 'Automatically flag high-risk content for review', 'content_moderation'),
('admin_email_primary', 'admin@envisage.co.zm', 'string', 'Primary admin email for alerts', 'notifications'),
('admin_email_security', 'security@envisage.co.zm', 'string', 'Security team email for alerts', 'notifications'),
('email_notifications_enabled', 'true', 'boolean', 'Enable email notifications for admin alerts', 'notifications')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- Add indexes for performance
ALTER TABLE marketplace_listings 
ADD INDEX idx_content_flags (content_flags(100)),
ADD INDEX idx_validation_score (validation_score),
ADD INDEX idx_auto_flagged (auto_flagged);

-- Create view for flagged listings
CREATE OR REPLACE VIEW flagged_listings AS
SELECT 
    l.*,
    u.name as seller_name,
    u.email as seller_email,
    u.risk_score as seller_risk_score,
    (SELECT COUNT(*) FROM user_violations_log WHERE user_id = l.user_id) as seller_violation_count
FROM marketplace_listings l
LEFT JOIN users u ON l.user_id = u.id
WHERE l.status IN ('pending', 'flagged') 
   OR l.auto_flagged = TRUE 
   OR JSON_LENGTH(l.content_flags) > 0;

-- Create view for user risk analysis
CREATE OR REPLACE VIEW user_risk_analysis AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.risk_score,
    u.account_status,
    u.listings_created,
    u.listings_flagged,
    COALESCE((u.listings_flagged / NULLIF(u.listings_created, 0)) * 100, 0) as flag_rate_percentage,
    (SELECT COUNT(*) FROM user_violations_log WHERE user_id = u.id) as total_violations,
    (SELECT COUNT(*) FROM user_violations_log WHERE user_id = u.id AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_violations,
    u.last_violation_at,
    u.created_at as account_created
FROM users u
WHERE u.role != 'admin'
ORDER BY u.risk_score DESC, u.listings_flagged DESC;

-- Sample cleanup procedures (run periodically)
DELIMITER //

CREATE PROCEDURE CleanupOldLogs()
BEGIN
    -- Clean rate limit logs older than 7 days
    DELETE FROM rate_limit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Clean email alerts log older than 30 days
    DELETE FROM email_alerts_log WHERE sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Clean automated actions log older than 90 days (except critical ones)
    DELETE FROM automated_actions_log 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) 
    AND confidence_score < 90.00;
END //

DELIMITER ;
