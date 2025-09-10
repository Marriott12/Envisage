<?php
// Appointment Booking System Setup
require_once '../config/config.php';
require_once '../config/database.php';

try {
    // Create appointments table
    $db->execute("
        CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            service_type VARCHAR(100) NOT NULL,
            preferred_date DATE NOT NULL,
            preferred_time TIME NOT NULL,
            alternative_date DATE,
            alternative_time TIME,
            message TEXT,
            status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Create appointment time slots table
    $db->execute("
        CREATE TABLE IF NOT EXISTS appointment_slots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            time TIME NOT NULL,
            is_available BOOLEAN DEFAULT TRUE,
            max_appointments INT DEFAULT 1,
            current_appointments INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    echo "Appointment booking system setup completed successfully!";

} catch (Exception $e) {
    echo "Error setting up appointment system: " . $e->getMessage();
}
?>
