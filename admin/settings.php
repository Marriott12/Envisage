<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $sanitizedValue = sanitizeInput($value);
            
            // Update or insert setting
            $existing = $db->fetch("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
            
            if ($existing) {
                $db->execute("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?", [$sanitizedValue, $key]);
            } else {
                $db->execute("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)", [$key, $sanitizedValue]);
            }
        }
    }
    
    setFlashMessage('success', 'Settings updated successfully!');
    redirect('settings.php');
}

// Get all settings
$settings = [];
$settingsData = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Default values if settings don't exist
$defaultSettings = [
    'site_name' => SITE_NAME,
    'site_description' => SITE_DESCRIPTION,
    'site_keywords' => SITE_KEYWORDS,
    'contact_email' => EMAIL,
    'contact_phone_1' => PHONE_1,
    'contact_phone_2' => PHONE_2,
    'contact_address' => ADDRESS,
    'facebook_url' => FACEBOOK_URL,
    'linkedin_url' => LINKEDIN_URL,
    'pinterest_url' => PINTEREST_URL,
    'admin_email' => ADMIN_EMAIL
];

// Merge with defaults
$settings = array_merge($defaultSettings, $settings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Site Settings</h1>
                </div>
                
                <?php $flashMessage = getFlashMessage(); ?>
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                        <?php echo $flashMessage['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <!-- General Settings -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-cog"></i> General Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="site_name" class="form-label">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" 
                                               value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_description" class="form-label">Site Description</label>
                                        <textarea class="form-control" id="site_description" name="site_description" rows="3" required><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_keywords" class="form-label">Site Keywords</label>
                                        <input type="text" class="form-control" id="site_keywords" name="site_keywords" 
                                               value="<?php echo htmlspecialchars($settings['site_keywords']); ?>"
                                               placeholder="keyword1, keyword2, keyword3">
                                        <small class="form-text text-muted">Separate keywords with commas</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="admin_email" class="form-label">Admin Email</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                               value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-address-book"></i> Contact Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="contact_email" class="form-label">Contact Email</label>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                               value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="contact_phone_1" class="form-label">Phone 1</label>
                                                <input type="text" class="form-control" id="contact_phone_1" name="contact_phone_1" 
                                                       value="<?php echo htmlspecialchars($settings['contact_phone_1']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="contact_phone_2" class="form-label">Phone 2</label>
                                                <input type="text" class="form-control" id="contact_phone_2" name="contact_phone_2" 
                                                       value="<?php echo htmlspecialchars($settings['contact_phone_2']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="contact_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="contact_address" name="contact_address" rows="2"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-share-alt"></i> Social Media Links</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="facebook_url" class="form-label">Facebook URL</label>
                                        <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                               value="<?php echo htmlspecialchars($settings['facebook_url']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                        <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                               value="<?php echo htmlspecialchars($settings['linkedin_url']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="pinterest_url" class="form-label">Pinterest URL</label>
                                        <input type="url" class="form-control" id="pinterest_url" name="pinterest_url" 
                                               value="<?php echo htmlspecialchars($settings['pinterest_url']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Settings -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-search"></i> SEO Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="google_analytics_id" class="form-label">Google Analytics ID</label>
                                        <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" 
                                               value="<?php echo htmlspecialchars($settings['google_analytics_id'] ?? ''); ?>"
                                               placeholder="GA-XXXXXXXXX-X">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="google_tag_manager_id" class="form-label">Google Tag Manager ID</label>
                                        <input type="text" class="form-control" id="google_tag_manager_id" name="google_tag_manager_id" 
                                               value="<?php echo htmlspecialchars($settings['google_tag_manager_id'] ?? ''); ?>"
                                               placeholder="GTM-XXXXXXX">
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1"
                                               <?php echo (!empty($settings['maintenance_mode'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">
                                            Maintenance Mode
                                        </label>
                                        <small class="form-text text-muted d-block">Enable to show maintenance page to visitors</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center">
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>
