<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/admin_auth.php';

requireAuth();

// Get quote request details if ID is provided
$quote_request = null;
if (isset($_GET['request_id'])) {
    $request_id = (int)$_GET['request_id'];
    $quote_request = $db->fetch("SELECT * FROM quote_requests WHERE id = ?", [$request_id]);
}

// Handle quote creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_quote'])) {
    $client_name = sanitizeInput($_POST['client_name']);
    $client_email = sanitizeInput($_POST['client_email']);
    $client_phone = sanitizeInput($_POST['client_phone'] ?? '');
    $client_company = sanitizeInput($_POST['client_company'] ?? '');
    $project_title = sanitizeInput($_POST['project_title']);
    $project_description = sanitizeInput($_POST['project_description']);
    $quote_number = 'QT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $valid_until = sanitizeInput($_POST['valid_until']);
    $terms = sanitizeInput($_POST['terms']);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Calculate totals
    $subtotal = 0;
    $items = [];
    
    if (isset($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description']) && !empty($item['quantity']) && !empty($item['price'])) {
                $item_total = (float)$item['quantity'] * (float)$item['price'];
                $subtotal += $item_total;
                $items[] = [
                    'description' => sanitizeInput($item['description']),
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price'],
                    'total' => $item_total
                ];
            }
        }
    }
    
    $tax_rate = 16; // 16% VAT in Zambia
    $tax_amount = $subtotal * ($tax_rate / 100);
    $total_amount = $subtotal + $tax_amount;
    
    if (!empty($items)) {
        // Insert quotation
        $quotation_id = $db->execute(
            "INSERT INTO quotations (quote_number, client_name, client_email, client_phone, client_company, project_title, project_description, subtotal, tax_rate, tax_amount, total_amount, valid_until, terms, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$quote_number, $client_name, $client_email, $client_phone, $client_company, $project_title, $project_description, $subtotal, $tax_rate, $tax_amount, $total_amount, $valid_until, $terms, $notes, $_SESSION['admin_user']['id']],
            true
        );
        
        if ($quotation_id) {
            // Insert quotation items
            foreach ($items as $item) {
                $db->execute(
                    "INSERT INTO quotation_items (quotation_id, description, quantity, price, total) VALUES (?, ?, ?, ?, ?)",
                    [$quotation_id, $item['description'], $item['quantity'], $item['price'], $item['total']]
                );
            }
            
            // Update quote request status if it came from a request
            if ($quote_request) {
                $db->execute("UPDATE quote_requests SET status = 'quoted' WHERE id = ?", [$quote_request['id']]);
            }
            
            setFlashMessage('success', 'Quote created successfully!');
            header("Location: quotations.php");
            exit;
        } else {
            setFlashMessage('error', 'Error creating quote. Please try again.');
        }
    } else {
        setFlashMessage('error', 'Please add at least one item to the quote.');
    }
}

include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Create Quote</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="quotations.php" class="btn btn-sm btn-outline-secondary">Back to Quotes</a>
                    </div>
                </div>
            </div>

            <?php displayFlashMessages(); ?>

            <form method="POST" id="quoteForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Client Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="client_name" class="form-label">Client Name *</label>
                                    <input type="text" class="form-control" id="client_name" name="client_name" 
                                           value="<?php echo $quote_request ? htmlspecialchars($quote_request['name']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="client_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="client_email" name="client_email" 
                                           value="<?php echo $quote_request ? htmlspecialchars($quote_request['email']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="client_phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="client_phone" name="client_phone" 
                                           value="<?php echo $quote_request ? htmlspecialchars($quote_request['phone']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="client_company" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="client_company" name="client_company" 
                                           value="<?php echo $quote_request ? htmlspecialchars($quote_request['company']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Project Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="project_title" class="form-label">Project Title *</label>
                                    <input type="text" class="form-control" id="project_title" name="project_title" 
                                           value="<?php echo $quote_request ? htmlspecialchars($quote_request['project_title']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="project_description" class="form-label">Project Description</label>
                                    <textarea class="form-control" id="project_description" name="project_description" rows="3"><?php echo $quote_request ? htmlspecialchars($quote_request['project_description']) : ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="valid_until" class="form-label">Valid Until *</label>
                                    <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                           value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Quote Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="items-container">
                            <!-- Items will be added here -->
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td class="text-end"><span id="subtotal">K 0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>VAT (16%):</strong></td>
                                        <td class="text-end"><span id="tax">K 0.00</span></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><strong>Total:</strong></td>
                                        <td class="text-end"><strong><span id="total">K 0.00</span></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Terms & Conditions</h5>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" name="terms" rows="6" placeholder="Enter terms and conditions...">1. 50% payment required to commence project
2. Balance payment upon project completion
3. Project timeline depends on client feedback and approvals
4. Additional features outside scope will be charged separately
5. All intellectual property rights transfer upon full payment</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Notes</h5>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" name="notes" rows="6" placeholder="Internal notes (optional)..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="quotations.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="create_quote" class="btn btn-primary">Create Quote</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
let itemCount = 0;

function addItem() {
    itemCount++;
    const container = document.getElementById('items-container');
    const itemHtml = `
        <div class="row align-items-end mb-3 item-row" id="item-${itemCount}">
            <div class="col-md-5">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="items[${itemCount}][description]" placeholder="Service/product description" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control quantity" name="items[${itemCount}][quantity]" min="1" value="1" onchange="calculateTotal()" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Price (K)</label>
                <input type="number" class="form-control price" name="items[${itemCount}][price]" min="0" step="0.01" onchange="calculateTotal()" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text" class="form-control total-field" readonly>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${itemCount})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
}

function removeItem(itemId) {
    document.getElementById(`item-${itemId}`).remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const price = parseFloat(row.querySelector('.price').value) || 0;
        const total = quantity * price;
        
        row.querySelector('.total-field').value = 'K ' + total.toFixed(2);
        subtotal += total;
    });
    
    const tax = subtotal * 0.16;
    const totalAmount = subtotal + tax;
    
    document.getElementById('subtotal').textContent = 'K ' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = 'K ' + tax.toFixed(2);
    document.getElementById('total').textContent = 'K ' + totalAmount.toFixed(2);
}

// Add initial item
document.addEventListener('DOMContentLoaded', function() {
    addItem();
});
</script>

<?php include '../includes/admin_footer.php'; ?>
