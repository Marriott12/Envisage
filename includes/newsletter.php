<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Check if email already exists
    $existing = $db->fetch("SELECT id FROM newsletter_subscribers WHERE email = ?", [$email]);
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Email already subscribed']);
        exit;
    }
    
    // Add to newsletter subscribers table (create if needed)
    try {
        $db->execute("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )");
        
        $result = $db->execute("INSERT INTO newsletter_subscribers (email) VALUES (?)", [$email]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Successfully subscribed to newsletter']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to subscribe']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
