<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $question = sanitizeInput($_POST['question']);
                $answer = sanitizeInput($_POST['answer']);
                $category = sanitizeInput($_POST['category']);
                $sort_order = (int)$_POST['sort_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($question && $answer) {
                    $result = $db->execute(
                        "INSERT INTO faqs (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)",
                        [$question, $answer, $category, $sort_order, $is_active]
                    );
                    
                    if ($result) {
                        $message = "FAQ added successfully!";
                    } else {
                        $error = "Error adding FAQ.";
                    }
                }
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $question = sanitizeInput($_POST['question']);
                $answer = sanitizeInput($_POST['answer']);
                $category = sanitizeInput($_POST['category']);
                $sort_order = (int)$_POST['sort_order'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($id && $question && $answer) {
                    $result = $db->execute(
                        "UPDATE faqs SET question = ?, answer = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?",
                        [$question, $answer, $category, $sort_order, $is_active, $id]
                    );
                    
                    if ($result) {
                        $message = "FAQ updated successfully!";
                    } else {
                        $error = "Error updating FAQ.";
                    }
                }
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                if ($id) {
                    $result = $db->execute("DELETE FROM faqs WHERE id = ?", [$id]);
                    if ($result) {
                        $message = "FAQ deleted successfully!";
                    } else {
                        $error = "Error deleting FAQ.";
                    }
                }
                break;
        }
    }
}

// Get all FAQs
$faqs = $db->fetchAll("SELECT * FROM faqs ORDER BY category, sort_order ASC");

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM faqs ORDER BY category");

$pageTitle = "FAQ Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white p-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3"><i class="fas fa-question-circle me-2"></i><?php echo $pageTitle; ?></h1>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFaqModal">
                            <i class="fas fa-plus"></i> Add FAQ
                        </button>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- FAQs Table -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Category</th>
                                            <th>Question</th>
                                            <th>Sort Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($faqs as $faq): ?>
                                        <tr>
                                            <td><?php echo $faq['id']; ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($faq['category']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($faq['question'], 0, 60)) . '...'; ?></td>
                                            <td><?php echo $faq['sort_order']; ?></td>
                                            <td>
                                                <?php if ($faq['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editFaq(<?php echo htmlspecialchars(json_encode($faq)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteFaq(<?php echo $faq['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add FAQ Modal -->
    <div class="modal fade" id="addFaqModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New FAQ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" required>
                        </div>

                        <div class="mb-3">
                            <label for="question" class="form-label">Question</label>
                            <textarea class="form-control" name="question" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="answer" class="form-label">Answer</label>
                            <textarea class="form-control" name="answer" rows="5" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="sort_order" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" checked>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modal -->
    <div class="modal fade" id="editFaqModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editFaqForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit FAQ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editFaqId">
                        
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" id="editCategory" required>
                        </div>

                        <div class="mb-3">
                            <label for="editQuestion" class="form-label">Question</label>
                            <textarea class="form-control" name="question" id="editQuestion" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editAnswer" class="form-label">Answer</label>
                            <textarea class="form-control" name="answer" id="editAnswer" rows="5" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editSortOrder" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="sort_order" id="editSortOrder">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editFaq(faq) {
            document.getElementById('editFaqId').value = faq.id;
            document.getElementById('editCategory').value = faq.category;
            document.getElementById('editQuestion').value = faq.question;
            document.getElementById('editAnswer').value = faq.answer;
            document.getElementById('editSortOrder').value = faq.sort_order;
            document.getElementById('editIsActive').checked = faq.is_active == 1;
            
            new bootstrap.Modal(document.getElementById('editFaqModal')).show();
        }

        function deleteFaq(id) {
            if (confirm('Are you sure you want to delete this FAQ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
