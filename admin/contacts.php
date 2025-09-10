<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';
// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                $id = (int)$_POST['id'];
                $db->execute("UPDATE contact_submissions SET is_read = 1 WHERE id = ?", [$id]);
                setFlashMessage('success', 'Message marked as read');
                break;
                
            case 'mark_unread':
                $id = (int)$_POST['id'];
                $db->execute("UPDATE contact_submissions SET is_read = 0 WHERE id = ?", [$id]);
                setFlashMessage('success', 'Message marked as unread');
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $db->execute("DELETE FROM contact_submissions WHERE id = ?", [$id]);
                setFlashMessage('success', 'Message deleted');
                break;
        }
        redirect('contacts.php');
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$totalContacts = $db->fetch("SELECT COUNT(*) as count FROM contact_submissions")['count'];
$totalPages = ceil($totalContacts / $perPage);

// Get contacts
$contacts = $db->fetchAll(
    "SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT $offset, $perPage"
);

// Get contact details if viewing individual
$viewContact = null;
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    $viewContact = $db->fetch("SELECT * FROM contact_submissions WHERE id = ?", [$viewId]);
    if ($viewContact && !$viewContact['is_read']) {
        // Mark as read when viewed
        $db->execute("UPDATE contact_submissions SET is_read = 1 WHERE id = ?", [$viewId]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Contact Messages</h1>
                    <div>
                        <span class="badge bg-primary">Total: <?php echo $totalContacts; ?></span>
                        <?php
                        $unreadCount = $db->fetch("SELECT COUNT(*) as count FROM contact_submissions WHERE is_read = 0")['count'];
                        if ($unreadCount > 0): ?>
                            <span class="badge bg-warning">Unread: <?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php $flashMessage = getFlashMessage(); ?>
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                        <?php echo $flashMessage['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($viewContact): ?>
                    <!-- Single Contact View -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5>Message Details</h5>
                                    <a href="contacts.php" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($viewContact['name']); ?></p>
                                            <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($viewContact['email']); ?>"><?php echo htmlspecialchars($viewContact['email']); ?></a></p>
                                            <?php if ($viewContact['phone']): ?>
                                                <p><strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($viewContact['phone']); ?>"><?php echo htmlspecialchars($viewContact['phone']); ?></a></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($viewContact['subject']); ?></p>
                                            <p><strong>Date:</strong> <?php echo formatDate($viewContact['created_at'], 'F j, Y g:i A'); ?></p>
                                            <p><strong>Status:</strong> 
                                                <?php if ($viewContact['is_read']): ?>
                                                    <span class="badge bg-success">Read</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Unread</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="mb-3">
                                        <strong>Message:</strong>
                                    </div>
                                    <div class="p-3 bg-light border rounded">
                                        <?php echo nl2br(htmlspecialchars($viewContact['message'])); ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="mailto:<?php echo htmlspecialchars($viewContact['email']); ?>?subject=Re: <?php echo urlencode($viewContact['subject']); ?>" class="btn btn-primary">
                                            <i class="fas fa-reply"></i> Reply via Email
                                        </a>
                                        
                                        <?php if (!$viewContact['is_read']): ?>
                                            <form method="POST" style="display: inline-block;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="id" value="<?php echo $viewContact['id']; ?>">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Mark as Read
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline-block;">
                                                <input type="hidden" name="action" value="mark_unread">
                                                <input type="hidden" name="id" value="<?php echo $viewContact['id']; ?>">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-eye-slash"></i> Mark as Unread
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete('Are you sure you want to delete this message?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $viewContact['id']; ?>">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Contact List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($contacts)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5>No contact messages yet</h5>
                                    <p class="text-muted">Contact messages will appear here when visitors submit the contact form.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Subject</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($contacts as $contact): ?>
                                                <tr class="<?php echo $contact['is_read'] ? '' : 'table-warning'; ?>">
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($contact['name']); ?></strong>
                                                        <?php if (!$contact['is_read']): ?>
                                                            <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                                    <td><?php echo htmlspecialchars(truncateText($contact['subject'], 30)); ?></td>
                                                    <td><?php echo formatDate($contact['created_at'], 'M j, Y'); ?></td>
                                                    <td>
                                                        <?php if ($contact['is_read']): ?>
                                                            <span class="badge bg-success">Read</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Unread</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="?view=<?php echo $contact['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if (!$contact['is_read']): ?>
                                                            <form method="POST" style="display: inline-block;">
                                                                <input type="hidden" name="action" value="mark_read">
                                                                <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as Read">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <form method="POST" style="display: inline-block;" onsubmit="return confirmDelete()">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $contact['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="d-flex justify-content-center">
                                        <?php echo generatePagination($page, $totalPages, 'contacts.php'); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>
