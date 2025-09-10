<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    echo "Inserting default site settings...\n";
    
    // Insert default settings
    $default_settings = [
        'site_name' => 'Envisage Technology Zambia',
        'site_description' => 'Envisage Technology Zambia is a Zambian software engineering company providing the best quality services for Web Designing, Development & Digital Marketing.',
        'site_keywords' => 'Envisage, Technology, Zambia, websites, website, development, application',
        'contact_email' => 'info@envisagezm.com',
        'contact_phone' => '+260 974 297 313',
        'contact_phone2' => '+260 978 425 886',
        'contact_address' => 'Lusaka, Zambia',
        'facebook_url' => 'https://web.facebook.com/envisagezm',
        'linkedin_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'business_hours' => 'Monday - Friday: 8:00 AM - 5:00 PM',
        'maintenance_mode' => '0',
        'google_analytics' => '',
        'footer_text' => '© 2025 Envisage Technology Zambia. All rights reserved.'
    ];
    
    foreach ($default_settings as $key => $value) {
        // Check if setting already exists
        $exists = $db->fetch("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
        
        if (!$exists) {
            $result = $db->execute(
                "INSERT INTO site_settings (setting_key, setting_value, setting_type, setting_group) VALUES (?, ?, 'text', 'general')",
                [$key, $value]
            );
            
            if ($result) {
                echo "✓ Added setting: $key\n";
            } else {
                echo "✗ Failed to add setting: $key\n";
            }
        } else {
            echo "- Setting already exists: $key\n";
        }
    }
    
    echo "\nSite settings setup complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
