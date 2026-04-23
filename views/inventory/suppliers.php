<?php // views/inventory/suppliers.php ?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">Suppliers</h1>
        <div class="page-breadcrumb"><i class="fa-solid fa-truck" style="color:var(--green)"></i> Inventory</div>
    </div>
    <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" onclick="document.getElementById('addSupplierModal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add Supplier</button>
    </div>
</div>

<div class="card fade-in" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table" id="suppliersTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($suppliers)): ?>
                <tr><td colspan="7" style="text-align:center;padding:48px;color:var(--text2);">
                    <i class="fa-solid fa-truck" style="font-size:36px;display:block;margin-bottom:12px;opacity:.3;"></i>
                    No suppliers found. Add one to get started.
                </td></tr>
                <?php else: foreach($suppliers as $s): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
                    <td><span style="font-family:monospace;"><?= htmlspecialchars($s['phone'] ?? '—') ?></span></td>
                    <td><a href="mailto:<?= htmlspecialchars($s['email']) ?>" style="color:var(--blue);"><?= htmlspecialchars($s['email'] ?? '—') ?></a></td>
                    <td style="max-width:200px;"><?= htmlspecialchars($s['address'] ?? '—') ?></td>
                    <td><span class="badge <?= $s['is_active'] ? 'badge-green' : 'badge-red' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button class="btn btn-ghost btn-sm" onclick='openEditSupplier(<?= htmlspecialchars(json_encode($s)) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="<?= base_url("inventory/suppliers/delete/{$s['id']}") ?>" onsubmit="return confirm('Delete supplier <?= addslashes(htmlspecialchars($s['name'])) ?>?');" style="display:inline;">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal-overlay" id="addSupplierModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-plus" style="color:var(--green);margin-right:8px;"></i>Add Supplier</div>
            <button class="icon-btn" onclick="document.getElementById('addSupplierModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= base_url('inventory/suppliers/store') ?>">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="grid-column:1/-1;"><label class="form-label">Supplier Name *</label><input type="text" name="name" class="form-input" required></div>
                <div><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-input"></div>
                <div><label class="form-label">Phone</label><input type="tel" name="phone" class="form-input"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Address</label><textarea name="address" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addSupplierModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Add Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal-overlay" id="editSupplierModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--blue);margin-right:8px;"></i>Edit Supplier</div>
            <button class="icon-btn" onclick="document.getElementById('editSupplierModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" id="editSupplierForm">
            <input type="hidden" name="id" id="edit_supplier_id">
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="grid-column:1/-1;"><label class="form-label">Supplier Name *</label><input type="text" name="name" id="edit_name" class="form-input" required></div>
                <div><label class="form-label">Contact Person</label><input type="text" name="contact_person" id="edit_contact_person" class="form-input"></div>
                <div><label class="form-label">Phone</label><input type="tel" name="phone" id="edit_phone" class="form-input"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-input"></div>
                <div style="grid-column:1/-1;"><label class="form-label">Address</label><textarea name="address" id="edit_address" class="form-input" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editSupplierModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSupplier(supplier) {
    document.getElementById('edit_supplier_id').value = supplier.id;
    document.getElementById('edit_name').value = supplier.name;
    document.getElementById('edit_contact_person').value = supplier.contact_person || '';
    document.getElementById('edit_phone').value = supplier.phone || '';
    document.getElementById('edit_email').value = supplier.email || '';
    document.getElementById('edit_address').value = supplier.address || '';
    document.getElementById('editSupplierForm').action = '<?= base_url("inventory/suppliers/update/") ?>' + supplier.id;
    document.getElementById('editSupplierModal').classList.add('open');
}
</script>