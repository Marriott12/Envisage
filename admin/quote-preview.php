<?php
// This file should be included within a context where $quote is available
if (!isset($quote)) return;

// Get quote items
$quote_items = $db->fetchAll("SELECT * FROM quotation_items WHERE quotation_id = ?", [$quote['id']]);
?>

<div class="quote-preview">
    <div class="text-center mb-4">
        <h3>QUOTATION</h3>
        <h4><?php echo htmlspecialchars($quote['quote_number']); ?></h4>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <h5>From:</h5>
            <strong>Envisage Technology Zambia</strong><br>
            Plot 123, Independence Avenue<br>
            Lusaka, Zambia<br>
            Email: info@envisagezm.com<br>
            Phone: +260 974 297 313
        </div>
        <div class="col-md-6 text-end">
            <h5>To:</h5>
            <strong><?php echo htmlspecialchars($quote['client_name']); ?></strong><br>
            <?php if (!empty($quote['client_company'])): ?>
                <?php echo htmlspecialchars($quote['client_company']); ?><br>
            <?php endif; ?>
            Email: <?php echo htmlspecialchars($quote['client_email']); ?><br>
            <?php if (!empty($quote['client_phone'])): ?>
                Phone: <?php echo htmlspecialchars($quote['client_phone']); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <strong>Project:</strong> <?php echo htmlspecialchars($quote['project_title']); ?>
        </div>
        <div class="col-md-6 text-end">
            <strong>Date:</strong> <?php echo date('F d, Y', strtotime($quote['created_at'])); ?><br>
            <strong>Valid Until:</strong> <?php echo date('F d, Y', strtotime($quote['valid_until'])); ?>
        </div>
    </div>
    
    <?php if (!empty($quote['project_description'])): ?>
    <div class="mb-4">
        <h5>Project Description:</h5>
        <p><?php echo nl2br(htmlspecialchars($quote['project_description'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Description</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Unit Price</th>
                    <th width="15%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quote_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end">K <?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-end">K <?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                    <td class="text-end"><strong>K <?php echo number_format($quote['subtotal'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>VAT (<?php echo $quote['tax_rate']; ?>%):</strong></td>
                    <td class="text-end"><strong>K <?php echo number_format($quote['tax_amount'], 2); ?></strong></td>
                </tr>
                <tr class="table-primary">
                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                    <td class="text-end"><strong>K <?php echo number_format($quote['total_amount'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if (!empty($quote['terms'])): ?>
    <div class="mb-4">
        <h5>Terms & Conditions:</h5>
        <div style="white-space: pre-line;"><?php echo htmlspecialchars($quote['terms']); ?></div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($quote['notes'])): ?>
    <div class="mb-4">
        <h5>Notes:</h5>
        <p><?php echo nl2br(htmlspecialchars($quote['notes'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="text-center mt-5">
        <p><strong>Thank you for considering Envisage Technology Zambia for your project!</strong></p>
        <p>For any questions about this quote, please contact us at info@envisagezm.com or +260 974 297 313</p>
    </div>
</div>
