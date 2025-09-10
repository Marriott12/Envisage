<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current user data
$sessionUser = getCurrentUser();
if (!$sessionUser) {
    setFlashMessage('danger', 'Unable to load user session!');
    redirect('login.php');
}

// Fetch complete user data from database
$currentUser = $db->fetch("SELECT * FROM admin_users WHERE id = ?", [$sessionUser['id']]);
if (!$currentUser) {
    setFlashMessage('danger', 'Unable to load user profile!');
    redirect('dashboard.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);
                
                // Validate inputs
                if (empty($username) || empty($email)) {
                    setFlashMessage('danger', 'Username and email are required!');
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    setFlashMessage('danger', 'Please enter a valid email address!');
                    break;
                }
                
                // Check if username/email already exists for another user
                $existingUser = $db->fetch(
                    "SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?", 
                    [$username, $email, $currentUser['id']]
                );
                
                if ($existingUser) {
                    setFlashMessage('danger', 'Username or email already exists!');
                    break;
                }
                
                // Update profile
                $result = $db->execute(
                    "UPDATE admin_users SET username = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?",
                    [$username, $email, $role, $currentUser['id']]
                );
                
                if ($result) {
                    // Update session data
                    $_SESSION['admin_user']['username'] = $username;
                    $_SESSION['admin_user']['email'] = $email;
                    $_SESSION['admin_user']['role'] = $role;
                    
                    setFlashMessage('success', 'Profile updated successfully!');
                    redirect('profile.php');
                } else {
                    setFlashMessage('danger', 'Failed to update profile!');
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate inputs
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    setFlashMessage('danger', 'All password fields are required!');
                    break;
                }
                
                if ($new_password !== $confirm_password) {
                    setFlashMessage('danger', 'New passwords do not match!');
                    break;
                }
                
                if (strlen($new_password) < 6) {
                    setFlashMessage('danger', 'New password must be at least 6 characters long!');
                    break;
                }
                
                // Verify current password
                if (!password_verify($current_password, $currentUser['password'])) {
                    setFlashMessage('danger', 'Current password is incorrect!');
                    break;
                }
                
                // Update password
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $result = $db->execute(
                    "UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?",
                    [$hashedPassword, $currentUser['id']]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Password changed successfully!');
                    redirect('profile.php');
                } else {
                    setFlashMessage('danger', 'Failed to change password!');
                }
                break;
                
            case 'update_avatar':
                // Handle avatar upload if implemented
                if (!empty($_FILES['avatar']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['avatar'], '../assets/images/avatars/', ['jpg', 'jpeg', 'png']);
                    
                    if ($uploadResult['success']) {
                        $avatarPath = 'assets/images/avatars/' . $uploadResult['filename'];
                        
                        // Delete old avatar if exists
                        if (!empty($currentUser['avatar']) && file_exists('../' . $currentUser['avatar'])) {
                            unlink('../' . $currentUser['avatar']);
                        }
                        
                        // Update avatar in database (requires avatar column in admin_users table)
                        $result = $db->execute(
                            "UPDATE admin_users SET avatar = ?, updated_at = NOW() WHERE id = ?",
                            [$avatarPath, $currentUser['id']]
                        );
                        
                        if ($result) {
                            $_SESSION['admin_user']['avatar'] = $avatarPath;
                            setFlashMessage('success', 'Avatar updated successfully!');
                        } else {
                            setFlashMessage('danger', 'Failed to update avatar!');
                        }
                    } else {
                        setFlashMessage('danger', 'Avatar upload failed: ' . $uploadResult['message']);
                    }
                }
                redirect('profile.php');
                break;
        }
    }
}

// Refresh current user data (in case it was updated during form processing)
$currentUser = $db->fetch("SELECT * FROM admin_users WHERE id = ?", [$sessionUser['id']]);

// Get user statistics
$userStats = [
    'last_login' => isset($currentUser['last_login']) ? $currentUser['last_login'] : null,
    'account_created' => $currentUser['created_at'],
    'total_logins' => 0, // Could be tracked with a login_count field
    'account_age_days' => ceil((time() - strtotime($currentUser['created_at'])) / (60 * 60 * 24))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">
                        <i class="fas fa-user-edit"></i> Profile Settings
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <!-- Profile Overview -->
                <div class="row mb-4">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="profile-avatar mb-3">
                                    <?php if (isset($currentUser['avatar']) && $currentUser['avatar']): ?>
                                        <img src="../<?php echo htmlspecialchars($currentUser['avatar']); ?>" 
                                             alt="Profile Avatar" class="rounded-circle" width="120" height="120" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                             style="width: 120px; height: 120px;">
                                            <i class="fas fa-user fa-3x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h4><?php echo htmlspecialchars($currentUser['username']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                
                                <span class="badge bg-<?php echo $currentUser['role'] == 'admin' ? 'danger' : ($currentUser['role'] == 'manager' ? 'warning' : 'info'); ?> mb-3">
                                    <?php echo ucfirst($currentUser['role']); ?>
                                </span>
                                
                                <!-- Avatar Upload Form -->
                                <form method="POST" enctype="multipart/form-data" class="mt-3">
                                    <input type="hidden" name="action" value="update_avatar">
                                    <div class="mb-2">
                                        <input type="file" class="form-control form-control-sm" name="avatar" accept="image/*" onchange="this.form.submit()">
                                    </div>
                                    <small class="text-muted">Upload new avatar (JPG, PNG)</small>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <!-- Account Statistics -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line"></i> Account Statistics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="border-end pe-3">
                                            <h6 class="text-muted">Account Created</h6>
                                            <p class="mb-2">
                                                <i class="fas fa-calendar-alt text-primary"></i>
                                                <?php echo date('M j, Y', strtotime($userStats['account_created'])); ?>
                                            </p>
                                            
                                            <h6 class="text-muted">Account Age</h6>
                                            <p class="mb-0">
                                                <i class="fas fa-clock text-info"></i>
                                                <?php echo $userStats['account_age_days']; ?> days
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="ps-3">
                                            <h6 class="text-muted">Last Login</h6>
                                            <p class="mb-2">
                                                <i class="fas fa-sign-in-alt text-success"></i>
                                                <?php if ($userStats['last_login']): ?>
                                                    <?php echo date('M j, Y g:i A', strtotime($userStats['last_login'])); ?>
                                                <?php else: ?>
                                                    Never logged in
                                                <?php endif; ?>
                                            </p>
                                            
                                            <h6 class="text-muted">Status</h6>
                                            <p class="mb-0">
                                                <?php if ($currentUser['is_active']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times-circle"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Settings Forms -->
                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user"></i> Profile Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <select class="form-control" id="role" name="role">
                                            <option value="admin" <?php echo $currentUser['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            <option value="manager" <?php echo $currentUser['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                            <option value="editor" <?php echo $currentUser['role'] == 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        </select>
                                        <small class="text-muted">Your access level and permissions</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-lock"></i> Change Password
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="6" required>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="6" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shield-alt"></i> Security Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Account Security Tips:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Use a strong, unique password</li>
                                    <li><i class="fas fa-check text-success"></i> Keep your login credentials secure</li>
                                    <li><i class="fas fa-check text-success"></i> Log out when finished</li>
                                    <li><i class="fas fa-check text-success"></i> Monitor your account activity</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Profile Information:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>User ID:</strong></td>
                                        <td><?php echo $currentUser['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo date('M j, Y', strtotime($currentUser['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($currentUser['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Auto-submit avatar form when file is selected
        document.querySelector('input[name="avatar"]').addEventListener('change', function() {
            if (this.files[0]) {
                if (confirm('Upload this image as your new avatar?')) {
                    this.form.submit();
                } else {
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>
