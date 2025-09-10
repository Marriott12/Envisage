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
    $company = sanitizeInput($_POST['company'] ?? '');
    $project_title = sanitizeInput($_POST['project_title']);
    $project_description = sanitizeInput($_POST['project_description']);
    $services = isset($_POST['services']) ? implode(', ', $_POST['services']) : '';
    $budget_range = sanitizeInput($_POST['budget_range']);
    $timeline = sanitizeInput($_POST['timeline']);
    
    if (empty($name) || empty($email) || empty($project_title) || empty($project_description) || empty($services) || empty($budget_range) || empty($timeline)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Handle file uploads
        $attachments = [];
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $upload_dir = 'uploads/quote-attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
            $max_file_size = 10 * 1024 * 1024; // 10MB

            foreach ($_FILES['attachments']['name'] as $key => $filename) {
                if (!empty($filename)) {
                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $file_size = $_FILES['attachments']['size'][$key];
                    
                    if (!in_array($file_ext, $allowed_types)) {
                        $error = "File type '{$file_ext}' is not allowed.";
                        break;
                    }
                    
                    if ($file_size > $max_file_size) {
                        $error = "File '{$filename}' is too large. Maximum size is 10MB.";
                        break;
                    }
                    
                    $new_filename = uniqid() . '_' . sanitizeInput($filename);
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $upload_path)) {
                        $attachments[] = $upload_path;
                    }
                }
            }
        }

        if (empty($error)) {
            $attachments_json = !empty($attachments) ? json_encode($attachments) : null;
            
            // Insert into database
            $result = $db->execute(
                "INSERT INTO quote_requests (name, email, phone, company, project_title, project_description, services, budget_range, timeline, attachments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $email, $phone, $company, $project_title, $project_description, $services, $budget_range, $timeline, $attachments_json]
            );
            
            if ($result) {
                $success = true;
                
                // Send email notification to admin
                $admin_email = "info@envisagezm.com";
                $subject = "New Quote Request - $project_title";
                $email_body = "
                New quote request received:
                
                Client Information:
                Name: $name
                Email: $email
                Phone: $phone
                Company: $company
                
                Project Information:
                Title: $project_title
                Description: $project_description
                Services Required: $services
                Budget Range: $budget_range
                Timeline: $timeline
                
                Attachments: " . count($attachments) . " file(s)
                
                Please review and provide a quote as soon as possible.
                
                Admin Panel: " . SITE_URL . "admin/quote-requests.php
                ";
                
                $headers = "From: $email\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($admin_email, $subject, $email_body, $headers);
                
                // Send confirmation email to client
                $client_subject = "Quote Request Received - Envisage Technology";
                $client_body = "
                Dear $name,
                
                Thank you for your quote request. We have received your project details and will review them carefully.
                
                Project Summary:
                - Title: $project_title
                - Budget Range: $budget_range
                - Timeline: $timeline
                
                Our team will analyze your requirements and provide you with a detailed quote within 24-48 hours. 
                
                If you have any urgent questions, please call us at +260 974 297 313.
                
                Best regards,
                Envisage Technology Zambia Team
                info@envisagezm.com
                +260 974 297 313 / +260 978 425 886
                ";
                
                $client_headers = "From: info@envisagezm.com\r\n";
                $client_headers .= "Reply-To: info@envisagezm.com\r\n";
                $client_headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($email, $client_subject, $client_body, $client_headers);
                
                setFlashMessage('success', 'Your quote request has been submitted successfully! We will send you a detailed quote within 24-48 hours.');
            } else {
                $error = 'Sorry, there was an error submitting your quote request. Please try again.';
            }
        }
    }
} else {
    $error = 'Invalid request method.';
}

if ($success) {
    header('Location: quote.php');
} else {
    setFlashMessage('error', $error);
    header('Location: quote.php');
}
exit;
?>
