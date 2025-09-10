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
                $client_name = sanitizeInput($_POST['client_name']);
                $client_position = sanitizeInput($_POST['client_position']);
                $client_company = sanitizeInput($_POST['client_company']);
                $testimonial = sanitizeInput($_POST['testimonial']);
                $rating = (int)$_POST['rating'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $client_image = '';
                if (!empty($_FILES['client_image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['client_image'], '../assets/images/testimonials/');
                    if ($uploadResult['success']) {
                        $client_image = 'assets/images/testimonials/' . $uploadResult['filename'];
                    } else {
                        setFlashMessage('danger', 'Image upload failed: ' . $uploadResult['message']);
                        break;
                    }
                }
                
                $result = $db->execute(
                    "INSERT INTO testimonials (client_name, client_position, client_company, testimonial, client_image, rating, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$client_name, $client_position, $client_company, $testimonial, $client_image, $rating, $is_active]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Testimonial added successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to add testimonial!');
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $client_name = sanitizeInput($_POST['client_name']);
                $client_position = sanitizeInput($_POST['client_position']);
                $client_company = sanitizeInput($_POST['client_company']);
                $testimonial = sanitizeInput($_POST['testimonial']);
                $rating = (int)$_POST['rating'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $currentTestimonial = $db->fetch("SELECT * FROM testimonials WHERE id = ?", [$id]);
                $client_image = $currentTestimonial['client_image'];
                
                if (!empty($_FILES['client_image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['client_image'], '../assets/images/testimonials/');
                    if ($uploadResult['success']) {
                        $client_image = 'assets/images/testimonials/' . $uploadResult['filename'];
                        if ($currentTestimonial['client_image'] && file_exists('../' . $currentTestimonial['client_image'])) {
                            unlink('../' . $currentTestimonial['client_image']);
                        }
                    }
                }
                
                $result = $db->execute(
                    "UPDATE testimonials SET client_name = ?, client_position = ?, client_company = ?, testimonial = ?, client_image = ?, rating = ?, is_active = ? WHERE id = ?",
                    [$client_name, $client_position, $client_company, $testimonial, $client_image, $rating, $is_active, $id]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Testimonial updated successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to update testimonial!');
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $testimonial = $db->fetch("SELECT client_image FROM testimonials WHERE id = ?", [$id]);
                
                $result = $db->execute("DELETE FROM testimonials WHERE id = ?", [$id]);
                
                if ($result) {
                    if ($testimonial['client_image'] && file_exists('../' . $testimonial['client_image'])) {
                        unlink('../' . $testimonial['client_image']);
                    }
                    setFlashMessage('success', 'Testimonial deleted successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to delete testimonial!');
                }
                break;
        }
        
        redirect('testimonials.php');
    }
}

// Get all testimonials
$testimonials = $db->fetchAll("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC");

// Get testimonial for editing
$editTestimonial = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editTestimonial = $db->fetch("SELECT * FROM testimonials WHERE id = ?", [$editId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Manage Testimonials</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testimonialModal">
                        <i class="fas fa-plus"></i> Add Testimonial
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
                    <?php if (empty($testimonials)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-quote-left fa-3x text-muted mb-3"></i>
                                    <h5>No testimonials found</h5>
                                    <p class="text-muted">Add your first client testimonial to get started.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <?php if ($testimonial['client_image']): ?>
                                                <img src="<?php echo '../' . $testimonial['client_image']; ?>" alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($testimonial['client_name']); ?></h6>
                                                <?php if ($testimonial['client_position']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($testimonial['client_position']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($testimonial['client_company']): ?>
                                                    <br><small class="text-primary"><?php echo htmlspecialchars($testimonial['client_company']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="text-end">
                                                <?php if ($testimonial['is_active']): ?>
                                                    <span class="badge bg-success mb-2">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary mb-2">Inactive</span>
                                                <?php endif; ?>
                                                <br>
                                                <div class="text-warning">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?php echo $i <= $testimonial['rating'] ? '' : '-o'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <blockquote class="blockquote mb-4">
                                            <p class="mb-0"><i class="fas fa-quote-left text-muted me-2"></i><?php echo nl2br(htmlspecialchars($testimonial['testimonial'])); ?></p>
                                        </blockquote>
                                        
                                        <div class="d-flex justify-content-end">
                                            <div class="btn-group" role="group">
                                                <a href="?edit=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#testimonialModal">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete()">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
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
    
    <!-- Testimonial Modal -->
    <div class="modal fade" id="testimonialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editTestimonial ? 'Edit' : 'Add'; ?> Testimonial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editTestimonial ? 'edit' : 'add'; ?>">
                        <?php if ($editTestimonial): ?>
                            <input type="hidden" name="id" value="<?php echo $editTestimonial['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client_name" class="form-label">Client Name</label>
                                    <input type="text" class="form-control" id="client_name" name="client_name" required 
                                           value="<?php echo $editTestimonial ? htmlspecialchars($editTestimonial['client_name']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client_position" class="form-label">Position/Title</label>
                                    <input type="text" class="form-control" id="client_position" name="client_position" 
                                           value="<?php echo $editTestimonial ? htmlspecialchars($editTestimonial['client_position']) : ''; ?>"
                                           placeholder="e.g., CEO, Manager">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client_company" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="client_company" name="client_company" 
                                           value="<?php echo $editTestimonial ? htmlspecialchars($editTestimonial['client_company']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <select class="form-control" id="rating" name="rating" required>
                                        <option value="">Select Rating</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($editTestimonial && $editTestimonial['rating'] == $i) ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="testimonial" class="form-label">Testimonial</label>
                            <textarea class="form-control" id="testimonial" name="testimonial" rows="4" required><?php echo $editTestimonial ? htmlspecialchars($editTestimonial['testimonial']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_image" class="form-label">Client Photo</label>
                            <input type="file" class="form-control" id="client_image" name="client_image" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                            <?php if ($editTestimonial && $editTestimonial['client_image']): ?>
                                <img id="imagePreview" src="<?php echo '../' . $editTestimonial['client_image']; ?>" class="image-preview mt-2" alt="Current image" style="max-width: 100px;">
                            <?php else: ?>
                                <img id="imagePreview" src="#" class="image-preview mt-2" style="display: none; max-width: 100px;" alt="Preview">
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo (!$editTestimonial || $editTestimonial['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editTestimonial ? 'Update' : 'Add'; ?> Testimonial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
    
    <?php if ($editTestimonial): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('testimonialModal')).show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
