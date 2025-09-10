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
                $email = sanitizeInput($_POST['email']);
                $name = sanitizeInput($_POST['name']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $is_verified = isset($_POST['is_verified']) ? 1 : 0;
                
                // Check if email already exists
                $existing = $db->fetch("SELECT id FROM newsletter_subscribers WHERE email = ?", [$email]);
                
                if ($existing) {
                    setFlashMessage('danger', 'Email already exists in newsletter subscribers!');
                } else {
                    $result = $db->execute(
                        "INSERT INTO newsletter_subscribers (email, name, is_active, is_verified) VALUES (?, ?, ?, ?)",
                        [$email, $name, $is_active, $is_verified]
                    );
                    
                    if ($result) {
                        setFlashMessage('success', 'Subscriber added successfully!');
                    } else {
                        setFlashMessage('danger', 'Failed to add subscriber!');
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $email = sanitizeInput($_POST['email']);
                $name = sanitizeInput($_POST['name']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $is_verified = isset($_POST['is_verified']) ? 1 : 0;
                
                // Check if email already exists for another subscriber
                $existing = $db->fetch("SELECT id FROM newsletter_subscribers WHERE email = ? AND id != ?", [$email, $id]);
                
                if ($existing) {
                    setFlashMessage('danger', 'Email already exists for another subscriber!');
                } else {
                    $result = $db->execute(
                        "UPDATE newsletter_subscribers SET email = ?, name = ?, is_active = ?, is_verified = ? WHERE id = ?",
                        [$email, $name, $is_active, $is_verified, $id]
                    );
                    
                    if ($result) {
                        setFlashMessage('success', 'Subscriber updated successfully!');
                    } else {
                        setFlashMessage('danger', 'Failed to update subscriber!');
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                $result = $db->execute("DELETE FROM newsletter_subscribers WHERE id = ?", [$id]);
                
                if ($result) {
                    setFlashMessage('success', 'Subscriber deleted successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to delete subscriber!');
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                $is_active = (int)$_POST['is_active'];
                
                $result = $db->execute("UPDATE newsletter_subscribers SET is_active = ? WHERE id = ?", [$is_active, $id]);
                
                if ($result) {
                    $status = $is_active ? 'activated' : 'deactivated';
                    setFlashMessage('success', "Subscriber {$status} successfully!");
                } else {
                    setFlashMessage('danger', 'Failed to update subscriber status!');
                }
                break;
                
            case 'export':
                // Export subscribers to CSV
                $subscribers = $db->fetchAll("SELECT email, name, is_active, is_verified, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Email', 'Name', 'Active', 'Verified', 'Subscribed Date']);
                
                foreach ($subscribers as $subscriber) {
                    fputcsv($output, [
                        $subscriber['email'],
                        $subscriber['name'],
                        $subscriber['is_active'] ? 'Yes' : 'No',
                        $subscriber['is_verified'] ? 'Yes' : 'No',
                        $subscriber['subscribed_at']
                    ]);
                }
                
                fclose($output);
                exit;
        }
        
        redirect('newsletter.php');
    }
}

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle search
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$whereClause = '';
$params = [];

if ($search) {
    $whereClause = "WHERE email LIKE ? OR name LIKE ?";
    $params = ["%{$search}%", "%{$search}%"];
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM newsletter_subscribers {$whereClause}";
$totalResult = $db->fetch($countSql, $params);
$totalSubscribers = $totalResult['total'];
$totalPages = ceil($totalSubscribers / $limit);

// Get subscribers
$sql = "SELECT * FROM newsletter_subscribers {$whereClause} ORDER BY subscribed_at DESC LIMIT {$limit} OFFSET {$offset}";
$subscribers = $db->fetchAll($sql, $params);

// Get subscriber for editing if ID is provided
$editSubscriber = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editSubscriber = $db->fetch("SELECT * FROM newsletter_subscribers WHERE id = ?", [$editId]);
}

// Get statistics
$stats = [
    'total' => $totalSubscribers,
    'active' => $db->fetch("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE is_active = 1")['count'],
    'verified' => $db->fetch("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE is_verified = 1")['count'],
    'today' => $db->fetch("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE DATE(subscribed_at) = CURDATE()")['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscribers - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Newsletter Subscribers</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#subscriberModal">
                                <i class="fas fa-plus"></i> Add Subscriber
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="export">
                                <button type="submit" class="btn btn-outline-success">
                                    <i class="fas fa-download"></i> Export CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h4><?php echo number_format($stats['total']); ?></h4>
                                <small class="text-muted">Total Subscribers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                                <h4><?php echo number_format($stats['active']); ?></h4>
                                <small class="text-muted">Active Subscribers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-shield-check fa-2x text-info mb-2"></i>
                                <h4><?php echo number_format($stats['verified']); ?></h4>
                                <small class="text-muted">Verified Subscribers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-calendar-day fa-2x text-warning mb-2"></i>
                                <h4><?php echo number_format($stats['today']); ?></h4>
                                <small class="text-muted">New Today</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" placeholder="Search by email or name..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <a href="newsletter.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Subscribers Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Verified</th>
                                        <th>Subscribed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subscribers)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No subscribers found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($subscribers as $subscriber): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($subscriber['email']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($subscriber['name'] ?: 'N/A'); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $subscriber['is_active'] ? 0 : 1; ?>">
                                                        <button type="submit" class="btn btn-sm <?php echo $subscriber['is_active'] ? 'btn-success' : 'btn-secondary'; ?>" 
                                                                onclick="return confirm('Are you sure you want to change the status?')">
                                                            <?php if ($subscriber['is_active']): ?>
                                                                <i class="fas fa-check"></i> Active
                                                            <?php else: ?>
                                                                <i class="fas fa-times"></i> Inactive
                                                            <?php endif; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <?php if ($subscriber['is_verified']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-shield-check"></i> Verified
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-shield-exclamation"></i> Unverified
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y g:i A', strtotime($subscriber['subscribed_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $subscriber['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete('Are you sure you want to delete this subscriber? This action cannot be undone.')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">
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
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Subscribers pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Subscriber Modal -->
    <div class="modal fade" id="subscriberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editSubscriber ? 'Edit' : 'Add'; ?> Subscriber</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editSubscriber ? 'edit' : 'add'; ?>">
                        <?php if ($editSubscriber): ?>
                            <input type="hidden" name="id" value="<?php echo $editSubscriber['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo $editSubscriber ? htmlspecialchars($editSubscriber['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name (Optional)</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo $editSubscriber ? htmlspecialchars($editSubscriber['name']) : ''; ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (!$editSubscriber || $editSubscriber['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified" 
                                           <?php echo ($editSubscriber && $editSubscriber['is_verified']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_verified">
                                        Verified
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editSubscriber ? 'Update' : 'Add'; ?> Subscriber
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
    
    <script>
        // Show modal if editing
        <?php if ($editSubscriber): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('subscriberModal')).show();
            });
        <?php endif; ?>
    </script>
</body>
</html>
