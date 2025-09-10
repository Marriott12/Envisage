<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/admin_auth.php';

requireAuth();

// Handle status updates
if (isset($_POST['update_status'])) {
    $quotation_id = (int)$_POST['quotation_id'];
    $status = sanitizeInput($_POST['status']);
    
    $db->execute("UPDATE quotations SET status = ? WHERE id = ?", [$status, $quotation_id]);
    setFlashMessage('success', 'Quotation status updated successfully!');
    header('Location: quotations.php');
    exit;
}

// Get all quotations
$quotations = $db->fetchAll("SELECT * FROM quotations ORDER BY created_at DESC");

include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quotations</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="quote-generator.php" class="btn btn-sm btn-primary">Create New Quote</a>
                    </div>
                </div>
            </div>

            <?php displayFlashMessages(); ?>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Quote #</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Valid Until</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotations as $quote): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($quote['quote_number']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($quote['client_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($quote['client_email']); ?></small>
                                <?php if (!empty($quote['client_company'])): ?>
                                    <br><small><?php echo htmlspecialchars($quote['client_company']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($quote['project_title']); ?></strong><br>
                                <small class="text-muted"><?php echo truncateText(htmlspecialchars($quote['project_description']), 50); ?></small>
                            </td>
                            <td><strong>K <?php echo number_format($quote['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($quote['status']) {
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'expired' => 'warning',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo ucfirst($quote['status']); ?></span>
                            </td>
                            <td>
                                <?php 
                                $valid_date = new DateTime($quote['valid_until']);
                                $today = new DateTime();
                                $is_expired = $valid_date < $today;
                                ?>
                                <span class="<?php echo $is_expired ? 'text-danger' : ''; ?>">
                                    <?php echo $valid_date->format('M d, Y'); ?>
                                </span>
                                <?php if ($is_expired): ?>
                                    <br><small class="text-danger">Expired</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $quote['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="quote-pdf.php?id=<?php echo $quote['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $quote['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="mailto:<?php echo htmlspecialchars($quote['client_email']); ?>?subject=Quote <?php echo htmlspecialchars($quote['quote_number']); ?>&body=Please find attached your quote for <?php echo htmlspecialchars($quote['project_title']); ?>" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?php echo $quote['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Quote <?php echo htmlspecialchars($quote['quote_number']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php include 'quote-preview.php'; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="quote-pdf.php?id=<?php echo $quote['id']; ?>" target="_blank" class="btn btn-primary">Download PDF</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Update Modal -->
                        <div class="modal fade" id="statusModal<?php echo $quote['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Quote Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="quotation_id" value="<?php echo $quote['id']; ?>">
                                            <div class="mb-3">
                                                <label for="status<?php echo $quote['id']; ?>" class="form-label">Status</label>
                                                <select class="form-select" name="status" id="status<?php echo $quote['id']; ?>" required>
                                                    <option value="draft" <?php echo $quote['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                    <option value="sent" <?php echo $quote['status'] == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                                    <option value="accepted" <?php echo $quote['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                    <option value="rejected" <?php echo $quote['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    <option value="expired" <?php echo $quote['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
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

            <?php if (empty($quotations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <h5>No quotations yet</h5>
                    <p class="text-muted">Create your first quotation to get started.</p>
                    <a href="quote-generator.php" class="btn btn-primary">Create Quote</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
