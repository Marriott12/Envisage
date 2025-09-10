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
                $description = sanitizeInput($_POST['description']);
                $icon = sanitizeInput($_POST['icon']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $image = '';
                if (!empty($_FILES['image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['image'], '../assets/images/');
                    if ($uploadResult['success']) {
                        $image = 'assets/images/' . $uploadResult['filename'];
                    } else {
                        setFlashMessage('danger', 'Image upload failed: ' . $uploadResult['message']);
                        break;
                    }
                }
                
                $result = $db->execute(
                    "INSERT INTO services (name, description, image, icon, is_active) VALUES (?, ?, ?, ?, ?)",
                    [$name, $description, $image, $icon, $is_active]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Service added successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to add service!');
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description']);
                $icon = sanitizeInput($_POST['icon']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Get current service data
                $currentService = $db->fetch("SELECT * FROM services WHERE id = ?", [$id]);
                $image = $currentService['image'];
                
                if (!empty($_FILES['image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['image'], '../assets/images/');
                    if ($uploadResult['success']) {
                        $image = 'assets/images/' . $uploadResult['filename'];
                        // Delete old image if it exists
                        if ($currentService['image'] && file_exists('../' . $currentService['image'])) {
                            unlink('../' . $currentService['image']);
                        }
                    }
                }
                
                $result = $db->execute(
                    "UPDATE services SET name = ?, description = ?, image = ?, icon = ?, is_active = ? WHERE id = ?",
                    [$name, $description, $image, $icon, $is_active, $id]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Service updated successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to update service!');
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $service = $db->fetch("SELECT image FROM services WHERE id = ?", [$id]);
                
                $result = $db->execute("DELETE FROM services WHERE id = ?", [$id]);
                
                if ($result) {
                    // Delete associated image
                    if ($service['image'] && file_exists('../' . $service['image'])) {
                        unlink('../' . $service['image']);
                    }
                    setFlashMessage('success', 'Service deleted successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to delete service!');
                }
                break;
        }
        
        redirect('services.php');
    }
}

// Get all services
$services = $db->fetchAll("SELECT * FROM services ORDER BY sort_order ASC, created_at DESC");

// Get service for editing if ID is provided
$editService = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editService = $db->fetch("SELECT * FROM services WHERE id = ?", [$editId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Manage Services</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
                        <i class="fas fa-plus"></i> Add New Service
                    </button>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Icon</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($services)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No services found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($service['image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-thumbnail">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center service-thumbnail">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td><?php echo htmlspecialchars(truncateText($service['description'], 50)); ?></td>
                                                <td>
                                                    <?php if ($service['icon']): ?>
                                                        <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($service['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $service['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete('Are you sure you want to delete this service? This action cannot be undone.')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Service Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editService ? 'Edit' : 'Add'; ?> Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editService ? 'edit' : 'add'; ?>">
                        <?php if ($editService): ?>
                            <input type="hidden" name="id" value="<?php echo $editService['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Service Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           value="<?php echo $editService ? htmlspecialchars($editService['name']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icon Class (FontAwesome)</label>
                                    <input type="text" class="form-control" id="icon" name="icon" 
                                           placeholder="e.g., fa-code"
                                           value="<?php echo $editService ? htmlspecialchars($editService['icon']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $editService ? htmlspecialchars($editService['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Service Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                            <?php if ($editService && $editService['image']): ?>
                                <img id="imagePreview" src="<?php echo '../' . $editService['image']; ?>" class="image-preview mt-2" alt="Current image">
                            <?php else: ?>
                                <img id="imagePreview" src="#" class="image-preview mt-2" style="display: none;" alt="Preview">
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo (!$editService || $editService['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editService ? 'Update' : 'Add'; ?> Service
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
    
    <script>
        // Show modal if editing
        <?php if ($editService): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('serviceModal')).show();
            });
        <?php endif; ?>
    </script>
</body>
</html>
