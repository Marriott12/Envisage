<?php
// Quotation Management System Setup
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Create quotations table
    $db->execute("
        CREATE TABLE IF NOT EXISTS quotations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_number VARCHAR(20) UNIQUE NOT NULL,
            client_name VARCHAR(100) NOT NULL,
            client_email VARCHAR(100) NOT NULL,
            client_phone VARCHAR(20),
            client_company VARCHAR(100),
            project_title VARCHAR(200) NOT NULL,
            project_description TEXT NOT NULL,
            services_required TEXT,
            budget_range VARCHAR(50),
            timeline VARCHAR(100),
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('pending', 'in_review', 'quoted', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
            subtotal DECIMAL(10,2) DEFAULT 0.00,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            total_amount DECIMAL(10,2) DEFAULT 0.00,
            currency VARCHAR(10) DEFAULT 'USD',
            validity_days INT DEFAULT 30,
            terms_conditions TEXT,
            admin_notes TEXT,
            quoted_by VARCHAR(100),
            quoted_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Create quotation items table
    $db->execute("
        CREATE TABLE IF NOT EXISTS quotation_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quotation_id INT NOT NULL,
            item_description TEXT NOT NULL,
            quantity INT DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
        )
    ");

    // Create quote requests table (for initial client requests)
    $db->execute("
        CREATE TABLE IF NOT EXISTS quote_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            company VARCHAR(100),
            project_title VARCHAR(200) NOT NULL,
            project_description TEXT NOT NULL,
            services TEXT,
            budget_range VARCHAR(50),
            timeline VARCHAR(100),
            attachments TEXT,
            status ENUM('new', 'reviewed', 'quoted', 'converted') DEFAULT 'new',
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    echo "Quotation management system setup completed successfully!";

} catch (Exception $e) {
    echo "Error setting up quotation system: " . $e->getMessage();
}
?>
