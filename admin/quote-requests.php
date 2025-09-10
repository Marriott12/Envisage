<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle status updates
if (isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = sanitizeInput($_POST['status']);
    
    $db->execute("UPDATE quote_requests SET status = ? WHERE id = ?", [$status, $request_id]);
    setFlashMessage('success', 'Quote request status updated successfully!');
    redirect('quote-requests.php');
}

// Get all quote requests
$quote_requests = $db->fetchAll("SELECT * FROM quote_requests ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Requests - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Quote Requests</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="quote-generator.php" class="btn btn-sm btn-outline-primary">Create Quote</a>
                        </div>
                    </div>
                </div>

                <?php displayFlashMessages(); ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Budget Range</th>
                            <th>Timeline</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quote_requests as $index => $request): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                <?php if (!empty($request['company'])): ?>
                                    <br><small><?php echo htmlspecialchars($request['company']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($request['project_title']); ?></strong><br>
                                <small class="text-muted"><?php echo truncateText(htmlspecialchars($request['project_description']), 50); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($request['budget_range']); ?></td>
                            <td><?php echo htmlspecialchars($request['timeline']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($request['status']) {
                                        'pending' => 'warning',
                                        'reviewing' => 'info',
                                        'quoted' => 'success',
                                        'rejected' => 'danger',
                                        'converted' => 'primary',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo ucfirst($request['status']); ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $request['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="quote-generator.php?request_id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Quote Request Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Client Information</h6>
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($request['name']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                                                <?php if (!empty($request['phone'])): ?>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($request['company'])): ?>
                                                    <p><strong>Company:</strong> <?php echo htmlspecialchars($request['company']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Project Information</h6>
                                                <p><strong>Title:</strong> <?php echo htmlspecialchars($request['project_title']); ?></p>
                                                <p><strong>Budget:</strong> <?php echo htmlspecialchars($request['budget_range']); ?></p>
                                                <p><strong>Timeline:</strong> <?php echo htmlspecialchars($request['timeline']); ?></p>
                                                <p><strong>Services:</strong> <?php echo htmlspecialchars($request['services']); ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <h6>Project Description</h6>
                                                <p><?php echo nl2br(htmlspecialchars($request['project_description'])); ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($request['attachments'])): ?>
                                            <div class="row">
                                                <div class="col-12">
                                                    <h6>Attachments</h6>
                                                    <?php 
                                                    $attachments = json_decode($request['attachments'], true);
                                                    foreach ($attachments as $attachment):
                                                        $filename = basename($attachment);
                                                    ?>
                                                        <a href="../<?php echo $attachment; ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">
                                                            <i class="fas fa-download"></i> <?php echo $filename; ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="quote-generator.php?request_id=<?php echo $request['id']; ?>" class="btn btn-primary">Create Quote</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Update Modal -->
                        <div class="modal fade" id="statusModal<?php echo $request['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <div class="mb-3">
                                                <label for="status<?php echo $request['id']; ?>" class="form-label">Status</label>
                                                <select class="form-select" name="status" id="status<?php echo $request['id']; ?>" required>
                                                    <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="reviewing" <?php echo $request['status'] == 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                                                    <option value="quoted" <?php echo $request['status'] == 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                                                    <option value="rejected" <?php echo $request['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    <option value="converted" <?php echo $request['status'] == 'converted' ? 'selected' : ''; ?>>Converted</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($quote_requests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No quote requests yet</h5>
                    <p class="text-muted">Quote requests from your website will appear here.</p>
                </div>
            <?php endif; ?>
                    </div>
                </div>
            </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
</body>
</html>
