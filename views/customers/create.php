<?php
// views/customers/create.php
?>
<div class="page-header fade-in">
    <div>
        <h1 class="page-title">New Customer</h1>
        <div class="page-breadcrumb">
            <a href="<?= base_url('customers') ?>" style="color:var(--blue);text-decoration:none;">Customers</a>
            <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i> New Connection
        </div>
    </div>
    <a href="<?= base_url('customers') ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="card fade-in" style="padding:14px 18px;margin-bottom:16px;border-color:rgba(239,68,68,0.4);background:rgba(239,68,68,0.08);">
    <span style="color:var(--red);"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']) ?></span>
    <?php unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= base_url('customers/store') ?>" enctype="multipart/form-data" id="newCustomerForm">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">

    <!-- LEFT: Main form -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Personal Info -->
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-user" style="color:var(--blue)"></i> Personal Information
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div style="grid-column:1/-1;">
                    <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="full_name" class="form-input" placeholder="Customer full name" required value="<?= htmlspecialchars($_POST['full_name']??'') ?>">
                </div>
                <div>
                    <label class="form-label">Father's Name</label>
                    <input type="text" name="father_name" class="form-input" placeholder="Father's name" value="<?= htmlspecialchars($_POST['father_name']??'') ?>">
                </div>
                <div>
                    <label class="form-label">NID Number</label>
                    <input type="text" name="nid_number" class="form-input" placeholder="National ID">
                </div>
                <div>
                    <label class="form-label">Primary Phone <span style="color:var(--red)">*</span></label>
                    <input type="tel" name="phone" class="form-input" placeholder="01XXXXXXXXX" required>
                </div>
                <div>
                    <label class="form-label">Alternate Phone</label>
                    <input type="tel" name="phone_alt" class="form-input" placeholder="Optional">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="Optional">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Address <span style="color:var(--red)">*</span></label>
                    <textarea name="address" class="form-input" rows="2" placeholder="Installation address" required></textarea>
                </div>
            </div>
        </div>

        <!-- Connection Info -->
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-wifi" style="color:var(--purple)"></i> Connection Details
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">Connection Type</label>
                    <select name="connection_type" class="form-input" id="connType" onchange="toggleConnFields()">
                        <option value="pppoe">PPPoE</option>
                        <option value="hotspot">Hotspot</option>
                        <option value="static">Static IP</option>
                        <option value="cgnat">CGNAT</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Package</label>
                    <select name="package_id" class="form-input" id="packageSel" onchange="updateCharge()">
                        <option value="">Select Package</option>
                        <?php foreach($packages as $p): ?>
                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-profile="<?= $p['mikrotik_profile'] ?? '' ?>"><?= htmlspecialchars($p['name']) ?> — ৳<?= number_format($p['price'],0) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:14px;background:var(--bg2);padding:14px;border-radius:12px;border:1px solid var(--border);">
                    <div>
                        <label class="form-label">MikroTik Server (NAS) <span style="color:var(--red)">*</span></label>
                        <select name="nas_id" id="nasId" class="form-input" onchange="loadProfiles()">
                            <option value="">Select MikroTik</option>
                            <?php foreach($nasDevices as $nas): ?>
                            <option value="<?= $nas['id'] ?>"><?= htmlspecialchars($nas['name']) ?> (<?= $nas['ip_address'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">PPPoE Profile <span style="color:var(--red)">*</span></label>
                        <select name="mikrotik_profile" id="profileSel" class="form-input">
                            <option value="">Select Profile</option>
                        </select>
                        <div id="profileLoading" style="display:none;font-size:11px;color:var(--blue);margin-top:4px;"><i class="fa-solid fa-sync fa-spin"></i> Fetching profiles...</div>
                    </div>
                </div>
                <div id="pppoeFields" style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label class="form-label">PPPoE Username <span style="color:var(--red)">*</span></label>
                        <input type="text" name="pppoe_username" id="pppoeUser" class="form-input" placeholder="username@domain" value="<?= htmlspecialchars($_GET['pppoe_username'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">PPPoE Password</label>
                        <input type="text" name="pppoe_password" class="form-input" placeholder="Leave blank for auto">
                    </div>
                </div>
                <div id="staticIpField" style="display:<?= !empty($_GET['static_ip']) ? 'block' : 'none' ?>;">
                    <label class="form-label">Static IP</label>
                    <input type="text" name="static_ip" class="form-input" placeholder="e.g. 192.168.1.100" value="<?= htmlspecialchars($_GET['static_ip'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Connection Date</label>
                    <input type="date" name="connection_date" class="form-input" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="form-label">Billing Day (of month)</label>
                    <select name="billing_day" class="form-input">
                        <?php for($d=1;$d<=28;$d++): ?>
                        <option value="<?=$d?>" <?=$d===1?'selected':''?>><?=$d?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-id-card" style="color:var(--yellow)"></i> KYC Documents
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">NID / Document Photo</label>
                    <input type="file" name="nid_photo" class="form-input" accept="image/*,.pdf">
                </div>
                <div>
                    <label class="form-label">Customer Photo</label>
                    <input type="file" name="customer_photo" class="form-input" accept="image/*">
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="card fade-in" style="padding:20px;">
            <label class="form-label">Notes / Remarks</label>
            <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes about this customer"></textarea>
        </div>

        <!-- Extended Client Info -->
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-list-check" style="color:#f97316"></i> Additional Client Details
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" class="form-input" placeholder="AA:BB:CC:DD:EE:FF"
                           oninput="this.value=this.value.toUpperCase()"
                           value="<?= htmlspecialchars($_POST['mac_address'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Client Type</label>
                    <select name="client_type" class="form-input">
                        <option value="home">Home</option>
                        <option value="business">Business</option>
                        <option value="corporate">Corporate</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Road No</label>
                    <input type="text" name="road_no" class="form-input" placeholder="Road number"
                           value="<?= htmlspecialchars($_POST['road_no'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">House No</label>
                    <input type="text" name="house_no" class="form-input" placeholder="House number"
                           value="<?= htmlspecialchars($_POST['house_no'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Sub Zone</label>
                    <input type="text" name="sub_zone" class="form-input" placeholder="Sub zone / area"
                           value="<?= htmlspecialchars($_POST['sub_zone'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Box No</label>
                    <input type="text" name="box_no" class="form-input" placeholder="Distribution box"
                           value="<?= htmlspecialchars($_POST['box_no'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Thana</label>
                    <input type="text" name="thana" class="form-input" placeholder="Thana / Upazila"
                           value="<?= htmlspecialchars($_POST['thana'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">District</label>
                    <input type="text" name="district" class="form-input" placeholder="District"
                           value="<?= htmlspecialchars($_POST['district'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Device Name</label>
                    <input type="text" name="device_name" class="form-input" placeholder="e.g. TP-Link TL-WR840N"
                           value="<?= htmlspecialchars($_POST['device_name'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Device Purchase Date</label>
                    <input type="date" name="device_purchase_date" class="form-input"
                           value="<?= htmlspecialchars($_POST['device_purchase_date'] ?? '') ?>">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Assigned Employee</label>
                    <input type="text" name="assigned_employee" class="form-input" placeholder="Employee name or ID"
                           value="<?= htmlspecialchars($_POST['assigned_employee'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Charges + Location -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Branch / Zone -->
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-location-dot" style="color:var(--green)"></i> Location
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Branch <span style="color:var(--red)">*</span></label>
                    <select name="branch_id" class="form-input" required>
                        <?php foreach($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($b['id']==$_SESSION['branch_id'])?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Zone</label>
                    <select name="zone_id" class="form-input">
                        <option value="">Select Zone</option>
                        <?php foreach($zones as $z): ?>
                        <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['name']) ?> (<?= $z['branch_name'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Charges Summary -->
        <div class="card fade-in" style="padding:20px;">
            <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-money-bill" style="color:var(--green)"></i> Charges
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div>
                    <label class="form-label">Monthly Charge (৳)</label>
                    <input type="number" name="monthly_charge" id="monthlyCharge" class="form-input" placeholder="0.00" step="0.01">
                </div>
                <div style="background:var(--bg3);border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:var(--text2);">Monthly Fee</span>
                        <span id="displayMonthly">৳0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:var(--text2);">OTC (One-time)</span>
                        <span id="displayOtc">৳0</span>
                    </div>
                    <div style="border-top:1px solid var(--border);padding-top:8px;display:flex;justify-content:space-between;font-size:14px;font-weight:700;">
                        <span>Total</span>
                        <span id="displayTotal" style="color:var(--green);">৳0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;">
            <i class="fa-solid fa-user-plus"></i> Create Customer
        </button>
        <a href="<?= base_url('customers') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;">Cancel</a>
    </div>
</div>
</form>

<script>
const packageData = {};
<?php foreach($packages as $p): ?>
packageData[<?= $p['id'] ?>] = { price: <?= $p['price'] ?>, otc: <?= $p['otc'] ?> };
<?php endforeach; ?>

function updateCharge() {
    const sel = document.getElementById('packageSel');
    const id = parseInt(sel.value);
    const monthly = document.getElementById('monthlyCharge');
    if (packageData[id]) {
        monthly.value = packageData[id].price;
        document.getElementById('displayMonthly').textContent = '৳' + packageData[id].price.toLocaleString();
        document.getElementById('displayOtc').textContent = '৳' + packageData[id].otc.toLocaleString();
        document.getElementById('displayTotal').textContent = '৳' + (packageData[id].price + packageData[id].otc).toLocaleString();
    }
}

function toggleConnFields() {
    const type = document.getElementById('connType').value;
    document.getElementById('pppoeFields').style.display = ['pppoe','hotspot'].includes(type) ? 'grid' : 'none';
    document.getElementById('staticIpField').style.display = type === 'static' ? 'block' : 'none';
}

// Auto-generate username from name
document.querySelector('[name="full_name"]').addEventListener('input', function() {
    const u = document.getElementById('pppoeUser');
    // Only auto-generate if it's empty AND not provided via query string
    if (!u.value) {
        u.value = this.value.toLowerCase().replace(/\s+/g, '.').replace(/[^a-z0-9.]/g,'');
    }
});

// Run toggle on load to handle pre-filled static IP
window.addEventListener('load', toggleConnFields);

async function loadProfiles() {
    const nasId = document.getElementById('nasId').value;
    const profileSel = document.getElementById('profileSel');
    const loading = document.getElementById('profileLoading');
    
    if (!nasId) {
        profileSel.innerHTML = '<option value="">Select Profile</option>';
        return;
    }

    loading.style.display = 'block';
    profileSel.disabled = true;

    try {
        const response = await fetch('<?= base_url('network/nas-profiles') ?>?nas_id=' + nasId);
        const profiles = await response.json();
        
        let html = '<option value="">Select Profile</option>';
        profiles.sort((a,b) => a.name.localeCompare(b.name)).forEach(p => {
            html += `<option value="${p.name}">${p.name}</option>`;
        });
        profileSel.innerHTML = html;
        
        // Auto-select if package matches
        const pkgProfile = document.getElementById('packageSel').selectedOptions[0]?.dataset.profile;
        if (pkgProfile) {
            Array.from(profileSel.options).forEach(opt => {
                if (opt.value === pkgProfile) opt.selected = true;
            });
        }
    } catch (e) {
        console.error('Failed to load profiles:', e);
    } finally {
        loading.style.display = 'none';
        profileSel.disabled = false;
    }
}
</script>
