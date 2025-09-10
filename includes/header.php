<?php
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
}

// Get page-specific meta data
function getPageMeta($page = '') {
    global $db;
    
    // Default meta data
    $meta = [
        'title' => SITE_NAME,
        'description' => SITE_DESCRIPTION,
        'keywords' => SITE_KEYWORDS,
        'author' => SITE_AUTHOR
    ];
    
    // Try to get page-specific meta from database
    if (!empty($page) && $db) {
        try {
            $pageData = $db->fetch("SELECT title, meta_description, meta_keywords FROM pages WHERE slug = ?", [$page]);
            if ($pageData) {
                $meta['title'] = $pageData['title'] . ' | ' . SITE_NAME;
                $meta['description'] = $pageData['meta_description'] ?: $meta['description'];
                $meta['keywords'] = $pageData['meta_keywords'] ?: $meta['keywords'];
            }
        } catch (Exception $e) {
            // Use default meta if database query fails
        }
    }
    
    return $meta;
}

// Get current page for navigation
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

$currentPage = isset($page) ? $page : getCurrentPage();
$meta = getPageMeta($currentPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?php echo htmlspecialchars($meta['description']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($meta['author']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta['keywords']); ?>">
    
    <!-- Open Graph meta tags -->
    <meta property="og:url" content="<?php echo SITE_URL; ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo htmlspecialchars($meta['title']); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($meta['description']); ?>" />
    <meta property="og:image" content="<?php echo SITE_URL; ?>assets/images/bg5.jpg" />
    
    <title><?php echo htmlspecialchars($meta['title']); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>assets/images/favicon.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Template CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style-starter.css">
    
    <!-- Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    
    <?php if (isset($additional_head)): ?>
        <?php echo $additional_head; ?>
    <?php endif; ?>
</head>
<body>
