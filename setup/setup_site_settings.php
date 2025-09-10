<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Create site_settings table
    $db->execute("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type ENUM('text', 'textarea', 'number', 'boolean', 'email', 'url') DEFAULT 'text',
            setting_group VARCHAR(50) DEFAULT 'general',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ Site settings table created successfully\n";
    
    // Insert default settings
    $default_settings = [
        ['site_name', 'Envisage Technology Zambia', 'text', 'general', 'Website name'],
        ['site_description', 'Envisage Technology Zambia is a Zambian software engineering company providing the best quality services for Web Designing, Development & Digital Marketing.', 'textarea', 'general', 'Website description'],
        ['site_keywords', 'Envisage, Technology, Zambia, websites, website, development, application', 'text', 'general', 'SEO keywords'],
        ['contact_email', 'info@envisagezm.com', 'email', 'contact', 'Primary contact email'],
        ['contact_phone', '+260 974 297 313', 'text', 'contact', 'Primary phone number'],
        ['contact_phone2', '+260 978 425 886', 'text', 'contact', 'Secondary phone number'],
        ['contact_address', 'Lusaka, Zambia', 'text', 'contact', 'Business address'],
        ['facebook_url', 'https://web.facebook.com/envisagezm', 'url', 'social', 'Facebook page URL'],
        ['linkedin_url', '', 'url', 'social', 'LinkedIn profile URL'],
        ['twitter_url', '', 'url', 'social', 'Twitter profile URL'],
        ['instagram_url', '', 'url', 'social', 'Instagram profile URL'],
        ['business_hours', 'Monday - Friday: 8:00 AM - 5:00 PM', 'text', 'general', 'Business operating hours'],
        ['maintenance_mode', '0', 'boolean', 'general', 'Enable maintenance mode'],
        ['google_analytics', '', 'textarea', 'analytics', 'Google Analytics tracking code'],
        ['footer_text', '© 2025 Envisage Technology Zambia. All rights reserved.', 'text', 'general', 'Footer copyright text']
    ];
    
    foreach ($default_settings as $setting) {
        $db->execute(
            "INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, setting_group, description) VALUES (?, ?, ?, ?, ?)",
            $setting
        );
    }
    
    echo "✓ Default settings inserted successfully\n";
    echo "Site settings setup complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
