<?php
/**
 * Bill Form (Create)
 */
?>

<div class="page-header fade-in">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--blue);margin-right:10px;"></i>New Purchase Bill</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <a href="<?= base_url('purchases/bills') ?>">Purchase Bills</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
            <span>New</span>
        </div>
    </div>
    <a href="<?= base_url('purchases/bills') ?>" class="btn btn-ghost">Back</a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']) ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card fade-in" style="max-width:900px;">
    <form method="POST" action="<?= base_url('purchases/bills/store') ?>" id="billForm">
        <div style="padding:24px;display:flex;flex-direction:column;gap:20px;">
            <div>
                <label class="form-label">Vendor <span style="color:var(--red);">*</span></label>
                <select name="vendor_id" class="form-input" required>
                    <option value="">— Select Vendor —</option>
                    <?php foreach ($vendors as $v): ?>
                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">Line Items <span style="color:var(--red);">*</span></label>
                <table class="data-table" id="itemsTable">
                    <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th><th></th></tr></thead>
                    <tbody id="itemsBody">
                    <tr>
                        <td><input type="text" name="items[0][description]" class="form-input" placeholder="Item description" required></td>
                        <td><input type="number" name="items[0][quantity]" class="form-input qty" value="1" min="0.01" step="0.01"></td>
                        <td><input type="number" name="items[0][unit_price]" class="form-input price" value="0" min="0" step="0.01"></td>
                        <td><input type="text" class="form-input line-total" value="0.00" readonly></td>
                        <td><button type="button" class="btn btn-ghost btn-xs" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-ghost btn-sm" style="margin-top:10px;" onclick="addRow()"><i class="fa-solid fa-plus"></i> Add Item</button>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label class="form-label">Discount</label>
                    <input type="number" name="discount" id="discount" class="form-input" value="0" min="0" step="0.01">
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="3"></textarea>
            </div>

            <div style="background:var(--bg2);padding:16px;border-radius:8px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Subtotal:</span><span id="subtotalDisplay">0.00</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span>Discount:</span><span id="discountDisplay">0.00</span></div>
                <div style="display:flex;justify-content:space-between;font-weight:700;font-size:18px;border-top:1px solid var(--border);padding-top:8px;"><span>Total:</span><span id="totalDisplay">0.00</span></div>
            </div>

            <div style="display:flex;gap:12px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Create Bill</button>
                <a href="<?= base_url('purchases/bills') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
let itemCount = 1;
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
    if (document.querySelectorAll('#itemsBody tr').length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    }
}
function attachListeners(row) {
    row.querySelectorAll('.qty, .price').forEach(el => {
        el.addEventListener('input', () => {
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            row.querySelector('.line-total').value = (qty * price).toFixed(2);
            calculateTotals();
        });
    });
}
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        subtotal += parseFloat(row.querySelector('.line-total').value) || 0;
    });
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const total = subtotal - discount;
    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
    document.getElementById('discountDisplay').textContent = discount.toFixed(2);
    document.getElementById('totalDisplay').textContent = total.toFixed(2);
}
document.getElementById('discount').addEventListener('input', calculateTotals);
document.querySelectorAll('#itemsBody tr').forEach(attachListeners);
</script>