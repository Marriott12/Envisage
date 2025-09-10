<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Insert into database
        $result = $db->execute(
            "INSERT INTO contact_submissions (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)",
            [$name, $email, $phone, $subject, $message]
        );
        
        if ($result) {
            $success = true;
            
            // Send email notification to admin
            $emailSubject = "New Contact Form Submission: " . $subject;
            $emailMessage = "
                <h3>New Contact Form Submission</h3>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
                <hr>
                <p><small>Submitted on " . date('Y-m-d H:i:s') . "</small></p>
            ";
            
            sendEmail(ADMIN_EMAIL, $emailSubject, $emailMessage, $email);
        } else {
            $error = 'Failed to submit your message. Please try again.';
        }
    }
}

// If it's an AJAX request, return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Thank you for your message! We will get back to you soon.']);
    } else {
        echo json_encode(['success' => false, 'message' => $error]);
    }
    exit;
}

// For regular form submission, redirect back to contact page
if ($success) {
    setFlashMessage('success', 'Thank you for your message! We will get back to you soon.');
    redirect('contact.php');
} elseif ($error) {
    setFlashMessage('danger', $error);
    redirect('contact.php');
}
?>
