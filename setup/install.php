<?php
require_once __DIR__ . '/../config/config.php';

// Create database if it doesn't exist
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    
    // Create admin users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create pages table for dynamic content
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(100) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        meta_description TEXT,
        meta_keywords TEXT,
        content LONGTEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        icon VARCHAR(100),
        is_active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create team members table
    $pdo->exec("CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        position VARCHAR(255),
        bio TEXT,
        image VARCHAR(255),
        facebook_url VARCHAR(255),
        linkedin_url VARCHAR(255),
        twitter_url VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create portfolio/projects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        category VARCHAR(100),
        client VARCHAR(255),
        project_url VARCHAR(255),
        is_featured BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create testimonials table
    $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255) NOT NULL,
        client_position VARCHAR(255),
        client_company VARCHAR(255),
        testimonial TEXT NOT NULL,
        client_image VARCHAR(255),
        rating INT DEFAULT 5,
        is_active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create blog posts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        excerpt TEXT,
        content LONGTEXT,
        featured_image VARCHAR(255),
        meta_description TEXT,
        meta_keywords TEXT,
        author_id INT,
        is_published BOOLEAN DEFAULT FALSE,
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES admin_users(id)
    )");
    
    // Create contact form submissions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        subject VARCHAR(255),
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create site settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'text',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default admin user (password: admin123)
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO admin_users (username, email, password) VALUES 
                ('admin', '" . ADMIN_EMAIL . "', '$hashedPassword')");
    
    // Insert default site settings
    $defaultSettings = [
        ['site_name', SITE_NAME],
        ['site_description', SITE_DESCRIPTION],
        ['site_keywords', SITE_KEYWORDS],
        ['contact_email', EMAIL],
        ['contact_phone_1', PHONE_1],
        ['contact_phone_2', PHONE_2],
        ['contact_address', ADDRESS],
        ['facebook_url', FACEBOOK_URL],
        ['linkedin_url', LINKEDIN_URL],
        ['pinterest_url', PINTEREST_URL]
    ];
    
    foreach($defaultSettings as $setting) {
        $pdo->exec("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES 
                    ('{$setting[0]}', '{$setting[1]}')");
    }
    
    // Insert default services
    $defaultServices = [
        ['Web Development', 'Custom website development using modern technologies', 'assets/images/p1.jpg', 'fa-code'],
        ['Mobile App Development', 'Native and cross-platform mobile application development', 'assets/images/p2.jpg', 'fa-mobile'],
        ['Digital Marketing', 'SEO, social media marketing, and online advertising', 'assets/images/p3.jpg', 'fa-bullhorn'],
        ['Graphic Design', 'Logo design, branding, and visual identity', 'assets/images/p4.jpg', 'fa-paint-brush'],
        ['Software Maintenance', 'Ongoing support and maintenance for your applications', 'assets/images/p5.jpg', 'fa-cogs'],
        ['Consultation', 'Technology consulting and project planning', 'assets/images/p6.jpg', 'fa-lightbulb-o']
    ];
    
    foreach($defaultServices as $index => $service) {
        $pdo->exec("INSERT IGNORE INTO services (name, description, image, icon, sort_order) VALUES 
                    ('{$service[0]}', '{$service[1]}', '{$service[2]}', '{$service[3]}', $index)");
    }
    
    echo "Database and tables created successfully!\n";
    echo "Default admin user created:\n";
    echo "Username: admin\n";
    echo "Email: " . ADMIN_EMAIL . "\n";
    echo "Password: admin123\n";
    echo "\nPlease change the default password after first login!\n";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
