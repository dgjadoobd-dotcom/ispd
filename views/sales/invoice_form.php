<?php
/**
 * Invoice Create / Edit Form
 * Requirements 5.2, 5.3
 */
$isEdit  = isset($invoice);
$inv    = $invoice ?? [];
$formUrl = $isEdit
    ? base_url('sales/update/' . $inv['id'])
    : base_url('sales/store');
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-file-invoice" style="color:var(--blue);margin-right:10px;"></i>
            <?= $isEdit ? 'Edit Invoice' : 'New Invoice' ?>
        </h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('sales/invoices') ?>">Sales Invoices</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span><?= $isEdit ? 'Edit' : 'New' ?></span>
        </div>
    </div>
    <a href="<?= base_url('sales/invoices') ?>" class="btn btn-ghost">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in fade-in-delay_1" style="max-width:900px;">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
        <h3 style="font-size:15px;font-weight:700;">Invoice Details</h3>
    </div>
    <form method="POST" action="<?= $formUrl ?>" id="invoiceForm">
        <div style="padding:24px;display:grid;gap:20px;">
            <?php if (!$isEdit): ?>
            <div>
                <label class="form-label">Customer <span style="color:var(--red);">*</span></label>
                <select name="customer_id" id="customer_id" class="form-input" required>
                    <option value="">— Select Customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)($inv['customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['customer_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="form-label">Invoice Type <span style="color:var(--red);">*</span></label>
                <select name="invoice_type" id="invoice_type" class="form-input" required>
                    <?php foreach (['installation' => 'Installation', 'product' => 'Product', 'service' => 'Service'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($inv['invoice_type'] ?? 'service') === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">Line Items <span style="color:var(--red);">*</span></label>
                <div id="lineItems">
                    <table class="data-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:50%;">Description</th>
                                <th style="width:15%;">Quantity</th>
                                <th style="width:20%;">Unit Price</th>
                                <th style="width:15%;">Total</th>
                                <th style="width:5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php if ($isEdit && !empty($inv['items'])): ?>
                            <?php foreach ($inv['items'] as $idx => $item): ?>
                            <tr>
                                <td><input type="text" name="items[<?= $idx ?>][description]" class="form-input" value="<?= htmlspecialchars($item['description']) ?>" required></td>
                                <td><input type="number" name="items[<?= $idx ?>][quantity]" class="form-input qty" value="<?= $item['quantity'] ?>" min="0.01" step="0.01"></td>
                                <td><input type="number" name="items[<?= $idx ?>][unit_price]" class="form-input price" value="<?= $item['unit_price'] ?>" min="0" step="0.01"></td>
                                <td><input type="text" class="form-input line-total" value="<?= number_format($item['line_total'], 2) ?>" readonly></td>
                                <td><button type="button" class="btn btn-ghost btn-xs" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td><input type="text" name="items[0][description]" class="form-input" placeholder="Item description" required></td>
                                <td><input type="number" name="items[0][quantity]" class="form-input qty" value="1" min="0.01" step="0.01"></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-input price" value="0" min="0" step="0.01"></td>
                                <td><input type="text" class="form-input line-total" value="0.00" readonly></td>
                                <td><button type="button" class="btn btn-ghost btn-xs" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-ghost btn-sm" style="margin-top:10px;" onclick="addRow()">
                        <i class="fa-solid fa-plus"></i> Add Item
                    </button>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="form-label">Discount</label>
                    <input type="number" name="discount" id="discount" class="form-input" value="<?= $inv['discount'] ?? 0 ?>" min="0" step="0.01">
                </div>
                <div>
                    <label class="form-label">OTC Amount (Installation)</label>
                    <input type="number" name="otc_amount" id="otc_amount" class="form-input" value="<?= $inv['otc_amount'] ?? 0 ?>" min="0" step="0.01">
                </div>
            </div>

            <?php if ($isEdit && $inv['invoice_type'] === 'installation'): ?>
            <div>
                <label class="form-label">Connection Date</label>
                <input type="date" name="connection_date" class="form-input" value="<?= $inv['connection_date'] ?? '' ?>">
            </div>
            <?php endif; ?>

            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3"><?= htmlspecialchars($inv['notes'] ?? '') ?></textarea>
            </div>

            <div style="background:var(--bg2);padding:16px;border-radius:8px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>Subtotal:</span>
                    <span id="subtotalDisplay">0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>Discount:</span>
                    <span id="discountDisplay">0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>VAT (15%):</span>
                    <span id="vatDisplay">0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span>OTC:</span>
                    <span id="otcDisplay">0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:700;font-size:18px;border-top:1px solid var(--border);padding-top:8px;">
                    <span>Total:</span>
                    <span id="totalDisplay">0.00</span>
                </div>
            </div>

            <div style="display:flex;gap:12px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> <?= $isEdit ? 'Update Invoice' : 'Create Invoice' ?>
                </button>
                <a href="<?= base_url('sales/invoices') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
let itemCount = <?= $isEdit && !empty($inv['items']) ? count($inv['items']) : 1 ?>;

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="items[${itemCount}][description]" class="form-input" placeholder="Item description" required></td>
        <td><input type="number" name="items[${itemCount}][quantity]" class="form-input qty" value="1" min="0.01" step="0.01"></td>
        <td><input type="number" name="items[${itemCount}][unit_price]" class="form-input price" value="0" min="0" step="0.01"></td>
        <td><input type="text" class="form-input line-total" value="0.00" readonly></td>
        <td><button type="button" class="btn btn-ghost btn-xs" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    itemCount++;
    attachListeners(row);
}

function removeRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    }
}

function attachListeners(row) {
    const qty = row.querySelector('.qty');
    const price = row.querySelector('.price');
    const total = row.querySelector('.line-total');
    
    [qty, price].forEach(el => {
        el.addEventListener('input', () => {
            const q = parseFloat(qty.value) || 0;
            const p = parseFloat(price.value) || 0;
            total.value = (q * p).toFixed(2);
            calculateTotals();
        });
    });
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.price')?.value) || 0;
        subtotal += qty * price;
    });

    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const otc = parseFloat(document.getElementById('otc_amount').value) || 0;
    const afterDiscount = subtotal - discount;
    const vat = afterDiscount * 0.15;
    const total = afterDiscount + vat + otc;

    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
    document.getElementById('discountDisplay').textContent = discount.toFixed(2);
    document.getElementById('vatDisplay').textContent = vat.toFixed(2);
    document.getElementById('otcDisplay').textContent = otc.toFixed(2);
    document.getElementById('totalDisplay').textContent = total.toFixed(2);
}

document.getElementById('discount').addEventListener('input', calculateTotals);
document.getElementById('otc_amount').addEventListener('input', calculateTotals);
document.querySelectorAll('#itemsBody tr').forEach(attachListeners);
calculateTotals();
</script>