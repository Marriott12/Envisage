<?php
// Utility functions for the website

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate a slug from text
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// Format date for display
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

// Truncate text with ellipsis
function truncateText($text, $length = 100, $ending = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - strlen($ending)) . $ending;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message system
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function displayFlashMessages() {
    $message = getFlashMessage();
    if ($message) {
        $alertType = match($message['type']) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-info'
        };
        
        echo '<div class="alert ' . $alertType . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Image upload function
function uploadImage($file, $targetDir = 'uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $targetDir = rtrim($targetDir, '/') . '/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = uniqid() . '_' . time() . '.' . $fileType;
    $targetFile = $targetDir . $newFileName;
    
    // Check if file is an actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image'];
    }
    
    // Check file size (5MB limit)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File is too large (max 5MB)'];
    }
    
    // Allow certain file formats
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only ' . implode(', ', $allowedTypes) . ' files are allowed'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $targetFile];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Generate pagination
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $pagination = '';
    
    if ($totalPages <= 1) {
        return $pagination;
    }
    
    $pagination .= '<nav aria-label="Page navigation">';
    $pagination .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
    
    return $pagination;
}

// Send email function
function sendEmail($to, $subject, $message, $from = null) {
    if (!$from) {
        $from = EMAIL;
    }
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $from" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>
