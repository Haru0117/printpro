// Global API Path Helper for Live Server (Port 5500)
function getApiUrl(path) {
    const isLiveServer = window.location.port === '5500';
    // Clean up relative path markers for absolute URL construction
    const cleanPath = path.replace(/^\.\.\//, '').replace(/^\.\//, '');
    if (isLiveServer) {
        return 'http://localhost/printpro/' + cleanPath;
    }
    return path;
}

// ══════════════════════════════════════════════════
// STATE
// ══════════════════════════════════════════════════
let currentUser = null;
let currentPortal = 'client';
let masterSpecs = []; // From DB
let currentSpecTab = 'base_prices';

// ══════════════════════════════════════════════════
// SPECS CRUD (ADMIN)
// ══════════════════════════════════════════════════

async function switchSpecTab(tab, el) {
  currentSpecTab = tab;
  document.querySelectorAll('#specTabs .filter-btn').forEach(b => b.classList.remove('active'));
  if (el) el.classList.add('active');

  const titles = {
    'base_prices': 'Base Pricing Tiers',
    'materials': 'Material Multipliers',
    'sizes': 'Size Multipliers',
    'finishes': 'Finishing Fees'
  };
  document.getElementById('specTableTitle').textContent = titles[tab];

  // Update Table Headers
  const thead = document.getElementById('specThead');
  if (tab === 'base_prices') {
    thead.innerHTML = '<tr><th>Product</th><th>Min Qty</th><th>Base Price</th><th>Actions</th></tr>';
  } else if (tab === 'finishes') {
    thead.innerHTML = '<tr><th>Finish Name</th><th>Setup Fee</th><th>Per Unit Fee</th><th>Actions</th></tr>';
  } else {
    thead.innerHTML = '<tr><th>Name</th><th>Multiplier</th><th>Actions</th></tr>';
  }

  renderSpecs();
}

async function renderSpecs() {
  try {
    const res = await fetch(getApiUrl(`api/specs.php?type=${currentSpecTab}`));
    const data = await res.json();
    if (!data.success) return;

    const tbody = document.getElementById('specsTbody');
    if (!tbody) return;

    tbody.innerHTML = data.data.map(s => {
      let cols = '';
      if (currentSpecTab === 'base_prices') {
        cols = `<td>${s.product_name}</td><td>${s.min_qty}</td><td>₱${parseFloat(s.base_price).toLocaleString()}</td>`;
      } else if (currentSpecTab === 'finishes') {
        cols = `<td>${s.name}</td><td>₱${parseFloat(s.setup_fee).toLocaleString()}</td><td>₱${parseFloat(s.per_unit_fee).toLocaleString()}</td>`;
      } else {
        cols = `<td>${s.name}</td><td>${s.multiplier}x</td>`;
      }

      return `<tr>${cols}<td>
                <button class="btn btn-outline btn-sm" onclick="openSpecModal(${s.id}, ${JSON.stringify(s).replace(/"/g, '&quot;')})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-danger" style="margin-left:4px;" onclick="deleteSpec(${s.id})"><i class="bi bi-trash"></i></button>
            </td></tr>`;
    }).join('');
  } catch (e) { console.error(e); }
}

function openSpecModal(id = null, data = null) {
  const modal = document.getElementById('specModal');
  document.getElementById('specEditId').value = id || '';

  // Hide all field groups first
  document.getElementById('fields-base_prices').style.display = 'none';
  document.getElementById('fields-common').style.display = 'none';
  document.getElementById('fields-finishes').style.display = 'none';

  if (currentSpecTab === 'base_prices') {
    document.getElementById('fields-base_prices').style.display = 'block';
    if (data) {
      document.getElementById('specProductId').value = data.product_id;
      document.getElementById('specMinQty').value = data.min_qty;
      document.getElementById('specBasePrice').value = data.base_price;
    } else {
      document.getElementById('specMinQty').value = '';
      document.getElementById('specBasePrice').value = '';
    }
  } else if (currentSpecTab === 'finishes') {
    document.getElementById('fields-finishes').style.display = 'block';
    if (data) {
      document.getElementById('specFinishName').value = data.name;
      document.getElementById('specSetupFee').value = data.setup_fee;
      document.getElementById('specUnitFee').value = data.per_unit_fee;
    } else {
      document.getElementById('specFinishName').value = '';
      document.getElementById('specSetupFee').value = '';
      document.getElementById('specUnitFee').value = '';
    }
  } else {
    document.getElementById('fields-common').style.display = 'block';
    if (data) {
      document.getElementById('specName').value = data.name;
      document.getElementById('specMultiplier').value = data.multiplier;
    } else {
      document.getElementById('specName').value = '';
      document.getElementById('specMultiplier').value = '1.00';
    }
  }

  modal.classList.add('show');
}

function closeSpecModal() {
  document.getElementById('specModal').classList.remove('show');
}

async function saveSpec() {
  const id = document.getElementById('specEditId').value;
  const formData = new FormData();
  formData.append('type', currentSpecTab);
  if (id) formData.append('id', id);

  if (currentSpecTab === 'base_prices') {
    formData.append('product_id', document.getElementById('specProductId').value);
    formData.append('min_qty', document.getElementById('specMinQty').value);
    formData.append('base_price', document.getElementById('specBasePrice').value);
  } else if (currentSpecTab === 'finishes') {
    formData.append('name', document.getElementById('specFinishName').value);
    formData.append('setup_fee', document.getElementById('specSetupFee').value);
    formData.append('per_unit_fee', document.getElementById('specUnitFee').value);
  } else {
    formData.append('name', document.getElementById('specName').value);
    formData.append('multiplier', document.getElementById('specMultiplier').value);
  }

  try {
    const res = await fetch(getApiUrl('api/specs.php'), { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showToast('Specification saved successfully!');
      closeSpecModal();
      renderSpecs();
    } else {
      showToast(data.message || 'Error saving specification');
    }
  } catch (e) { showToast('Network error'); }
}

async function deleteSpec(id) {
  if (!confirm('Delete this specification?')) return;
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('type', currentSpecTab);
  formData.append('id', id);

  try {
    const res = await fetch(getApiUrl('api/specs.php'), { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showToast('Deleted successfully');
      renderSpecs();
    }
  } catch (e) { showToast('Network error'); }
}

// ══════════════════════════════════════════════════
// COMMON UI
// ══════════════════════════════════════════════════

function showPage(id, navEl) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  const pg = document.getElementById('page-' + id);
  if (pg) pg.classList.add('active');

  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (navEl) navEl.classList.add('active');

  if (id === 'specs') renderSpecs();
}

function showToast(msg) {
  const toast = document.getElementById('toast');
  const toastMsg = document.getElementById('toastMsg');
  if (toastMsg) toastMsg.textContent = msg;
  if (toast) {
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }
}

// Placeholder for other functions to avoid breakage
function setupUI() { }
function initCharts() { }
