<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';
// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitizeInput($_POST['name']);
                $position = sanitizeInput($_POST['position']);
                $bio = sanitizeInput($_POST['bio']);
                $facebook_url = sanitizeInput($_POST['facebook_url']);
                $linkedin_url = sanitizeInput($_POST['linkedin_url']);
                $twitter_url = sanitizeInput($_POST['twitter_url']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $image = '';
                if (!empty($_FILES['image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['image'], '../assets/images/team/');
                    if ($uploadResult['success']) {
                        $image = 'assets/images/team/' . $uploadResult['filename'];
                    } else {
                        setFlashMessage('danger', 'Image upload failed: ' . $uploadResult['message']);
                        break;
                    }
                }
                
                $result = $db->execute(
                    "INSERT INTO team_members (name, position, bio, image, facebook_url, linkedin_url, twitter_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$name, $position, $bio, $image, $facebook_url, $linkedin_url, $twitter_url, $is_active]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Team member added successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to add team member!');
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitizeInput($_POST['name']);
                $position = sanitizeInput($_POST['position']);
                $bio = sanitizeInput($_POST['bio']);
                $facebook_url = sanitizeInput($_POST['facebook_url']);
                $linkedin_url = sanitizeInput($_POST['linkedin_url']);
                $twitter_url = sanitizeInput($_POST['twitter_url']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $currentMember = $db->fetch("SELECT * FROM team_members WHERE id = ?", [$id]);
                $image = $currentMember['image'];
                
                if (!empty($_FILES['image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['image'], '../assets/images/team/');
                    if ($uploadResult['success']) {
                        $image = 'assets/images/team/' . $uploadResult['filename'];
                        if ($currentMember['image'] && file_exists('../' . $currentMember['image'])) {
                            unlink('../' . $currentMember['image']);
                        }
                    }
                }
                
                $result = $db->execute(
                    "UPDATE team_members SET name = ?, position = ?, bio = ?, image = ?, facebook_url = ?, linkedin_url = ?, twitter_url = ?, is_active = ? WHERE id = ?",
                    [$name, $position, $bio, $image, $facebook_url, $linkedin_url, $twitter_url, $is_active, $id]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Team member updated successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to update team member!');
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $member = $db->fetch("SELECT image FROM team_members WHERE id = ?", [$id]);
                
                $result = $db->execute("DELETE FROM team_members WHERE id = ?", [$id]);
                
                if ($result) {
                    if ($member['image'] && file_exists('../' . $member['image'])) {
                        unlink('../' . $member['image']);
                    }
                    setFlashMessage('success', 'Team member deleted successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to delete team member!');
                }
                break;
        }
        
        redirect('team.php');
    }
}

// Get all team members
$teamMembers = $db->fetchAll("SELECT * FROM team_members ORDER BY sort_order ASC, created_at DESC");

// Get team member for editing
$editMember = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editMember = $db->fetch("SELECT * FROM team_members WHERE id = ?", [$editId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Manage Team</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teamModal">
                        <i class="fas fa-plus"></i> Add Team Member
                    </button>
                </div>
                
                <?php $flashMessage = getFlashMessage(); ?>
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                        <?php echo $flashMessage['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php if (empty($teamMembers)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No team members found</h5>
                                    <p class="text-muted">Add your first team member to get started.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teamMembers as $member): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <?php if ($member['image']): ?>
                                            <img src="<?php echo '../' . $member['image']; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                                <i class="fas fa-user fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h5 class="card-title"><?php echo htmlspecialchars($member['name']); ?></h5>
                                        <p class="text-primary"><?php echo htmlspecialchars($member['position']); ?></p>
                                        
                                        <?php if ($member['bio']): ?>
                                            <p class="card-text text-muted small"><?php echo htmlspecialchars(truncateText($member['bio'], 80)); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <?php if ($member['facebook_url']): ?>
                                                <a href="<?php echo htmlspecialchars($member['facebook_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fab fa-facebook-f"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($member['linkedin_url']): ?>
                                                <a href="<?php echo htmlspecialchars($member['linkedin_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fab fa-linkedin-in"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($member['twitter_url']): ?>
                                                <a href="<?php echo htmlspecialchars($member['twitter_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fab fa-twitter"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <?php if ($member['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="btn-group" role="group">
                                            <a href="?edit=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#teamModal">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Team Modal -->
    <div class="modal fade" id="teamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editMember ? 'Edit' : 'Add'; ?> Team Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editMember ? 'edit' : 'add'; ?>">
                        <?php if ($editMember): ?>
                            <input type="hidden" name="id" value="<?php echo $editMember['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           value="<?php echo $editMember ? htmlspecialchars($editMember['name']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position/Title</label>
                                    <input type="text" class="form-control" id="position" name="position" 
                                           value="<?php echo $editMember ? htmlspecialchars($editMember['position']) : ''; ?>"
                                           placeholder="e.g., CEO, Developer, Designer">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio/Description</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $editMember ? htmlspecialchars($editMember['bio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                            <?php if ($editMember && $editMember['image']): ?>
                                <img id="imagePreview" src="<?php echo '../' . $editMember['image']; ?>" class="image-preview mt-2" alt="Current image" style="max-width: 150px;">
                            <?php else: ?>
                                <img id="imagePreview" src="#" class="image-preview mt-2" style="display: none; max-width: 150px;" alt="Preview">
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="facebook_url" class="form-label">Facebook URL</label>
                                    <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                           value="<?php echo $editMember ? htmlspecialchars($editMember['facebook_url']) : ''; ?>"
                                           placeholder="https://facebook.com/username">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                    <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                           value="<?php echo $editMember ? htmlspecialchars($editMember['linkedin_url']) : ''; ?>"
                                           placeholder="https://linkedin.com/in/username">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="twitter_url" class="form-label">Twitter URL</label>
                                    <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                                           value="<?php echo $editMember ? htmlspecialchars($editMember['twitter_url']) : ''; ?>"
                                           placeholder="https://twitter.com/username">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo (!$editMember || $editMember['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editMember ? 'Update' : 'Add'; ?> Team Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
    
    <?php if ($editMember): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('teamModal')).show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
