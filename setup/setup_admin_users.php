<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Create admin_users table
    $db->execute("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'manager', 'editor') DEFAULT 'admin',
            is_active TINYINT(1) DEFAULT 1,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ Admin users table created successfully\n";
    
    // Check if default admin exists
    $adminExists = $db->fetch("SELECT id FROM admin_users WHERE username = 'admin'");
    
    if (!$adminExists) {
        // Create default admin user
        $defaultPassword = 'admin123'; // Change this in production!
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        $result = $db->execute(
            "INSERT INTO admin_users (username, email, password, role) VALUES (?, ?, ?, ?)",
            ['admin', 'admin@envisagezm.com', $hashedPassword, 'admin']
        );
        
        if ($result) {
            echo "✓ Default admin user created successfully\n";
            echo "  Username: admin\n";
            echo "  Password: $defaultPassword\n";
            echo "  Email: admin@envisagezm.com\n";
            echo "\n⚠️  IMPORTANT: Change the default password after first login!\n";
        } else {
            echo "✗ Failed to create default admin user\n";
        }
    } else {
        echo "- Default admin user already exists\n";
    }
    
    echo "\nAdmin setup complete!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
