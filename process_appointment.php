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
    $service_type = sanitizeInput($_POST['service_type']);
    $preferred_date = sanitizeInput($_POST['preferred_date']);
    $preferred_time = sanitizeInput($_POST['preferred_time']);
    $alternative_date = sanitizeInput($_POST['alternative_date'] ?? '');
    $alternative_time = sanitizeInput($_POST['alternative_time'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($service_type) || empty($preferred_date) || empty($preferred_time)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strtotime($preferred_date) <= time()) {
        $error = 'Please select a future date for your appointment.';
    } else {
        // Check if the selected date is not a weekend
        $dayOfWeek = date('w', strtotime($preferred_date));
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $error = 'Appointments are not available on weekends. Please select a weekday.';
        } else {
            // Insert into database
            $result = $db->execute(
                "INSERT INTO appointments (name, email, phone, service_type, preferred_date, preferred_time, alternative_date, alternative_time, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $email, $phone, $service_type, $preferred_date, $preferred_time, $alternative_date ?: null, $alternative_time ?: null, $message]
            );
            
            if ($result) {
                $success = true;
                
                // Send email notification to admin
                $admin_email = "info@envisagezm.com";
                $subject = "New Appointment Booking - $service_type";
                $email_body = "
                New appointment booking received:
                
                Name: $name
                Email: $email
                Phone: $phone
                Service: $service_type
                Preferred Date: $preferred_date
                Preferred Time: $preferred_time
                Alternative Date: $alternative_date
                Alternative Time: $alternative_time
                Message: $message
                
                Please confirm this appointment as soon as possible.
                ";
                
                $headers = "From: $email\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($admin_email, $subject, $email_body, $headers);
                
                // Send confirmation email to client
                $client_subject = "Appointment Booking Confirmation - Envisage Technology";
                $client_body = "
                Dear $name,
                
                Thank you for booking an appointment with Envisage Technology Zambia.
                
                Your appointment details:
                Service: $service_type
                Preferred Date: $preferred_date
                Preferred Time: $preferred_time
                
                We will contact you within 24 hours to confirm your appointment. If you have any urgent questions, please call us at +260 974 297 313.
                
                Best regards,
                Envisage Technology Zambia Team
                info@envisagezm.com
                +260 974 297 313 / +260 978 425 886
                ";
                
                $client_headers = "From: info@envisagezm.com\r\n";
                $client_headers .= "Reply-To: info@envisagezm.com\r\n";
                $client_headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($email, $client_subject, $client_body, $client_headers);
                
                setFlashMessage('success', 'Your appointment has been booked successfully! We will contact you within 24 hours to confirm.');
            } else {
                $error = 'Sorry, there was an error booking your appointment. Please try again.';
            }
        }
    }
} else {
    $error = 'Invalid request method.';
}

if ($success) {
    header('Location: appointment.php');
} else {
    setFlashMessage('error', $error);
    header('Location: appointment.php');
}
exit;
?>
