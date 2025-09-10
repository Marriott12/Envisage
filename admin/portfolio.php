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
                $title = sanitizeInput($_POST['title']);
                $description = sanitizeInput($_POST['description']);
                $category = sanitizeInput($_POST['category']);
                $client = sanitizeInput($_POST['client']);
                $project_url = sanitizeInput($_POST['project_url']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $image = '';
                if (!empty($_FILES['image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['image'], '../assets/images/portfolio/');
                    if ($uploadResult['success']) {
                        $image = 'assets/images/portfolio/' . $uploadResult['filename'];
                    } else {
                        setFlashMessage('danger', 'Image upload failed: ' . $uploadResult['message']);
                        break;
                    }
                }
                
                $result = $db->execute(
                    "INSERT INTO portfolio (title, description, image, category, client, project_url, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$title, $description, $image, $category, $client, $project_url, $is_featured, $is_active]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Portfolio item added successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to add portfolio item!');
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = sanitizeInput($_POST['title']);
                $description = sanitizeInput($_POST['description']);
                $category = sanitizeInput($_POST['category']);
                $client = sanitizeInput($_POST['client']);
                $project_url = sanitizeInput($_POST['project_url']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $currentItem = $db->fetch("SELECT * FROM portfolio WHERE id = ?", [$id]);
                $image = $currentItem['image'];
                
                if (!empty($_FILES['image']['tmp_name'])) {
                    $uploadResult = uploadImage($_FILES['image'], '../assets/images/portfolio/');
                    if ($uploadResult['success']) {
                        $image = 'assets/images/portfolio/' . $uploadResult['filename'];
                        if ($currentItem['image'] && file_exists('../' . $currentItem['image'])) {
                            unlink('../' . $currentItem['image']);
                        }
                    }
                }
                
                $result = $db->execute(
                    "UPDATE portfolio SET title = ?, description = ?, image = ?, category = ?, client = ?, project_url = ?, is_featured = ?, is_active = ? WHERE id = ?",
                    [$title, $description, $image, $category, $client, $project_url, $is_featured, $is_active, $id]
                );
                
                if ($result) {
                    setFlashMessage('success', 'Portfolio item updated successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to update portfolio item!');
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $item = $db->fetch("SELECT image FROM portfolio WHERE id = ?", [$id]);
                
                $result = $db->execute("DELETE FROM portfolio WHERE id = ?", [$id]);
                
                if ($result) {
                    if ($item['image'] && file_exists('../' . $item['image'])) {
                        unlink('../' . $item['image']);
                    }
                    setFlashMessage('success', 'Portfolio item deleted successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to delete portfolio item!');
                }
                break;
        }
        
        redirect('portfolio.php');
    }
}

// Get all portfolio items
$portfolio = $db->fetchAll("SELECT * FROM portfolio ORDER BY sort_order ASC, created_at DESC");

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM portfolio WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get portfolio item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editItem = $db->fetch("SELECT * FROM portfolio WHERE id = ?", [$editId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Portfolio - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Manage Portfolio</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#portfolioModal">
                        <i class="fas fa-plus"></i> Add New Project
                    </button>
                </div>
                
                <?php $flashMessage = getFlashMessage(); ?>
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                        <?php echo $flashMessage['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Client</th>
                                        <th>Featured</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($portfolio)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No portfolio items found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($portfolio as $item): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($item['image']): ?>
                                                        <img src="<?php echo '../' . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(truncateText($item['description'], 40)); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                <td><?php echo htmlspecialchars($item['client']); ?></td>
                                                <td>
                                                    <?php if ($item['is_featured']): ?>
                                                        <span class="badge bg-warning"><i class="fas fa-star"></i> Featured</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#portfolioModal">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete()">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
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
    
    <!-- Portfolio Modal -->
    <div class="modal fade" id="portfolioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editItem ? 'Edit' : 'Add'; ?> Portfolio Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editItem ? 'edit' : 'add'; ?>">
                        <?php if ($editItem): ?>
                            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Project Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           value="<?php echo $editItem ? htmlspecialchars($editItem['category']) : ''; ?>"
                                           placeholder="e.g., Web Design, Mobile App">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client" class="form-label">Client Name</label>
                                    <input type="text" class="form-control" id="client" name="client" 
                                           value="<?php echo $editItem ? htmlspecialchars($editItem['client']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_url" class="form-label">Project URL</label>
                                    <input type="url" class="form-control" id="project_url" name="project_url" 
                                           value="<?php echo $editItem ? htmlspecialchars($editItem['project_url']) : ''; ?>"
                                           placeholder="https://example.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Project Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                            <?php if ($editItem && $editItem['image']): ?>
                                <img id="imagePreview" src="<?php echo '../' . $editItem['image']; ?>" class="image-preview mt-2" alt="Current image">
                            <?php else: ?>
                                <img id="imagePreview" src="#" class="image-preview mt-2" style="display: none;" alt="Preview">
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                           <?php echo ($editItem && $editItem['is_featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_featured">
                                        Featured Project
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (!$editItem || $editItem['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editItem ? 'Update' : 'Add'; ?> Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
    
    <?php if ($editItem): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('portfolioModal')).show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
