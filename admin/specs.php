<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../index.html?action=login'); exit; }
$_role = strtolower($_SESSION['role'] ?? '');
if ($_role === 'client') { header('Location: ../client/'); exit; }
if ($_role !== 'admin' && $_role !== 'super_admin') { header('Location: ../index.html?action=login'); exit; }
$adminName  = htmlspecialchars($_SESSION['name']  ?? 'Admin');
$adminEmail = htmlspecialchars($_SESSION['email'] ?? '');
$adminInit  = strtoupper(substr($adminName, 0, 1));
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>PrintPro Admin — Print Specs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="../assets/css/printpro.css" rel="stylesheet">
  <style>
    /* ── DASHBOARD SHELL STYLES FOR SPECS.PHP ── */
    :root {
        --navy: #0d1b3e;
        --sidebar: #0f2057;
        --accent: #1d8cf8;
        --accent2: #00c6ff;
        --teal: #3ec6c6;
        --danger: #f5365c;
        --warning: #fb6340;
        --success: #2dce89;
        --purple: #7c4dff;
        --border: #dde3f0;
        --muted: #8898aa;
        --off: #f4f6fb;
        --dark-bg: #0a1628;
        --dark-card: #111e40;
        --dark-card2: #162048;
    }

    [data-theme="dark"] {
        --navy: #e0e6ed;
        --off: #0d1b3e;
        --border: rgba(255, 255, 255, 0.08);
        --dark-bg: #0a1628;
        --dark-card: #111e40;
        --dark-card2: #162048;
        --muted: #8898aa;
        --accent: #fb6340;
    }

    html[data-theme="dark"] body {
        background: var(--dark-bg) !important;
        color: var(--navy) !important;
    }

    html[data-theme="dark"] .a-sidebar,
    html[data-theme="dark"] .a-topbar,
    html[data-theme="dark"] .card {
        background: var(--dark-card) !important;
        border-color: var(--border) !important;
        color: var(--navy) !important;
    }

    html[data-theme="dark"] .a-content {
        background: var(--dark-bg) !important;
    }

    html[data-theme="dark"] .a-nav-item:hover {
        background: rgba(255, 255, 255, 0.03) !important;
        color: #fff !important;
    }

    html[data-theme="dark"] table th {
        background: rgba(255, 255, 255, 0.02) !important;
        color: var(--muted) !important;
        border-bottom: 1px solid var(--border) !important;
    }

    html[data-theme="dark"] table td {
        border-bottom: 1px solid var(--border) !important;
        color: var(--navy) !important;
    }

    html[data-theme="dark"] .btn-light {
        background: var(--dark-card2) !important;
        border: 1px solid var(--border) !important;
        color: #fff !important;
    }

    html[data-theme="dark"] input,
    html[data-theme="dark"] select {
        background: var(--dark-card2) !important;
        border-color: var(--border) !important;
        color: #fff !important;
    }

    html, body {
        height: 100%;
        font-family: 'DM Sans', sans-serif;
        background: #f0f2f8;
        margin: 0;
        overflow: hidden;
    }

    .a-app {
        display: flex;
        height: 100vh;
        overflow: hidden;
    }

    .a-sidebar {
        width: 240px;
        flex-shrink: 0;
        background: linear-gradient(180deg, #0f2057 0%, #0d1b3e 100%);
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow-y: auto;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .a-brand {
        padding: 24px 24px 18px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        color: #fff;
    }

    .a-brand img {
        height: 28px;
        width: auto;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    .a-brand-badge {
        font-size: .65rem;
        background: #1d8cf8;
        color: #fff;
        padding: 2px 9px;
        border-radius: 6px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex-shrink: 0;
    }

    .a-nav {
        padding: 10px 0;
        flex: 1;
        overflow-y: auto;
    }

    .a-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 24px;
        font-size: .83rem;
        font-weight: 500;
        color: rgba(255, 255, 255, .55);
        cursor: pointer;
        border-left: 3px solid transparent;
        transition: all .15s;
        text-decoration: none;
    }

    .a-nav-item i {
        font-size: 1rem;
        width: 18px;
    }

    .a-nav-item:hover {
        color: #fff;
        background: rgba(255, 255, 255, .05);
    }

    .a-nav-item.active {
        color: #fff;
        background: rgba(29, 140, 248, .18);
        border-left-color: var(--accent);
        font-weight: 600;
    }

    .a-logout {
        padding: 16px 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .a-logout a {
        color: var(--muted);
        text-decoration: none;
        font-size: .85rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .a-logout a:hover {
        color: var(--danger);
    }

    .a-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .a-topbar {
        height: 48px;
        background: #fff;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        box-shadow: 0 1px 6px rgba(0, 0, 0, .06);
        position: relative;
        z-index: 100;
        flex-shrink: 0;
    }

    .a-content {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
    }

    /* Tabs */
    .spec-tabs { display:flex; gap:4px; background:#fff; border:1px solid var(--border); border-radius:10px; padding:4px; width:fit-content; margin-bottom:18px; }
    .spec-tab  { padding:8px 20px; border-radius:7px; border:none; background:transparent; font-family:'DM Sans',sans-serif; font-size:.82rem; font-weight:600; color:var(--muted); cursor:pointer; display:flex; align-items:center; gap:6px; transition:.15s; }
    .spec-tab.active { background:var(--navy); color:#fff; }

    /* Toggle switch */
    .toggle-wrap { position:relative; display:inline-block; width:38px; height:21px; }
    .toggle-wrap input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; inset:0; background:#dde3f0; border-radius:21px; cursor:pointer; transition:.2s; }
    .toggle-slider:before { content:''; position:absolute; width:15px; height:15px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
    input:checked + .toggle-slider { background:var(--success); }
    input:checked + .toggle-slider:before { transform:translateX(17px); }

    /* Icon buttons */
    .btn-icon { padding:5px 9px; border-radius:7px; border:1.5px solid var(--border); background:#fff; color:var(--muted); cursor:pointer; font-size:.85rem; transition:.15s; }
    .btn-icon:hover { background:var(--off); color:var(--navy); }
    .btn-icon.del:hover { border-color:var(--danger); color:var(--danger); background:rgba(245,54,92,.06); }

    /* Modal */
    .modal-bg { display:none; position:fixed; inset:0; background:rgba(10,22,40,.5); z-index:1500; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
    .modal-bg.open { display:flex; }
    .modal-box { background:#fff; border-radius:16px; padding:28px 30px; width:100%; max-width:440px; box-shadow:0 24px 80px rgba(13,27,62,.22); animation:fadeUp .2s ease; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
    .modal-title { font-family:'Sora',sans-serif; font-size:1.05rem; font-weight:700; color:var(--navy); margin-bottom:20px; }
    .fi { margin-bottom:15px; }
    .fi label { display:block; font-size:.75rem; font-weight:600; color:var(--navy); margin-bottom:5px; }
    .fi input { width:100%; padding:10px 13px; border:1.5px solid var(--border); border-radius:9px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:var(--navy); outline:none; transition:.15s; }
    .fi input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(29,140,248,.1); }
    .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:22px; }

    /* Confirm dialog */
    .confirm-bg { display:none; position:fixed; inset:0; background:rgba(10,22,40,.55); z-index:1600; align-items:center; justify-content:center; }
    .confirm-bg.open { display:flex; }
    .confirm-box { background:#fff; border-radius:14px; padding:30px 28px; max-width:340px; width:100%; text-align:center; box-shadow:0 24px 60px rgba(13,27,62,.2); }

    /* Toast */
    .toast-pp { position:fixed; bottom:24px; right:24px; background:var(--navy); color:#fff; padding:11px 18px; border-radius:10px; font-size:.82rem; font-weight:600; display:none; align-items:center; gap:8px; z-index:1900; box-shadow:0 8px 28px rgba(13,27,62,.22); }
    .toast-pp.show { display:flex; }

    /* Page header */
    .page-hdr { margin-bottom:18px; }
    .page-hdr h4 { font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:800; color:var(--navy); margin:0 0 2px; }
    .page-hdr p { font-size:.82rem; color:var(--muted); margin:0; }

    /* Empty state */
    .tbl-empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:.85rem; }
    .tbl-empty i { display:block; font-size:2rem; margin-bottom:8px; opacity:.4; }

    /* b-success / b-danger for is_active badges */
    .b-success { background:rgba(45,206,137,.15); color:#1aae6f; }
    .b-danger  { background:rgba(245,54,92,.12); color:var(--danger); }

    @media (max-width: 992px) {
        .a-sidebar {
            position: fixed;
            left: -260px;
            height: 100vh;
            box-shadow: 20px 0 50px rgba(0, 0, 0, 0.1);
        }

        .a-sidebar.show {
            left: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(4px);
        }

        .sidebar-overlay.show {
            display: block;
        }
    }

    @media (max-width: 768px) {
        .a-topbar {
            padding: 0 16px;
        }

        .a-content {
            padding: 16px;
        }

        .a-brand {
            padding: 16px;
        }
    }
  </style>
</head>
<body>

<div class="a-app">
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  <!-- Sidebar -->
  <aside class="a-sidebar" id="sidebar">
      <div class="a-brand">
          <img src="../assets/img/logo.png" alt="PrintPro">
          <span class="a-brand-badge">Admin</span>
      </div>
      <nav class="a-nav">
          <a class="a-nav-item" href="index.php?page=dashboard">
              <i class="bi bi-speedometer2"></i> Dashboard
          </a>
          <a class="a-nav-item" href="index.php?page=orders">
              <i class="bi bi-box-seam"></i> Orders
          </a>
          <a class="a-nav-item" href="index.php?page=users">
              <i class="bi bi-people"></i> Users
          </a>
          <a class="a-nav-item" href="index.php?page=subscriptions">
              <i class="bi bi-card-checklist"></i> Subscriptions
          </a>
          <a class="a-nav-item active" href="specs.php">
              <i class="bi bi-sliders"></i> Specifications
          </a>
          <a class="a-nav-item" href="index.php?page=settings">
              <i class="bi bi-gear"></i> Settings
          </a>
      </nav>
      <div class="a-logout">
          <a href="#" onclick="handleLogout(event)"><i class="bi bi-box-arrow-left"></i> Logout</a>
      </div>
  </aside>

  <div class="a-main">
      <!-- Topbar -->
      <header class="a-topbar">
          <div class="d-flex align-items-center gap-3">
              <button class="btn btn-light d-lg-none action-btn" onclick="toggleSidebar()">
                  <i class="bi bi-list fs-4"></i>
              </button>
              <h5 class="m-0 fw-bold">Specifications</h5>
          </div>
          <div class="d-flex align-items-center gap-3">
              <button class="btn border-0 p-0 text-muted fs-5" onclick="toggleTheme()">
                  <i class="bi bi-moon-stars" id="themeIcon"></i>
              </button>
              <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                      style="width:34px; height:34px; font-size:.85rem; font-weight:700; line-height:1;"
                      id="userAvatar"><?= $adminInit ?></div>
                  <div class="d-none d-sm-block">
                      <div class="fw-bold" style="font-size:.8rem;"><?= $adminName ?></div>
                      <div class="text-muted" style="font-size:.7rem;">Admin</div>
                  </div>
              </div>
          </div>
      </header>

      <div class="a-content">
        <div class="page active">

        <div class="page-hdr">
          <h4><i class="bi bi-sliders" style="color:var(--accent);margin-right:6px;"></i>Print Specifications</h4>
          <p>Manage materials, finishes, and sizes available for client orders.</p>
        </div>

        <!-- TABS -->
        <div class="spec-tabs">
          <button class="spec-tab active" onclick="switchTab('materials',this)"><i class="bi bi-layers"></i> Materials</button>
          <button class="spec-tab" onclick="switchTab('finishes',this)"><i class="bi bi-stars"></i> Finishes</button>
          <button class="spec-tab" onclick="switchTab('sizes',this)"><i class="bi bi-aspect-ratio"></i> Sizes</button>
        </div>

        <!-- MATERIALS -->
        <div id="tab-materials">
          <div class="card">
            <div class="card-hdr">
              <span class="card-title"><i class="bi bi-layers" style="color:var(--accent);"></i> Paper Materials</span>
              <button class="btn btn-primary btn-sm" onclick="openAdd('paper')"><i class="bi bi-plus-lg"></i> Add Material</button>
            </div>
            <table class="tbl" style="width:100%">
              <thead><tr><th>ID</th><th>Name</th><th>Multiplier</th><th>Status</th><th>Active</th><th>Actions</th></tr></thead>
              <tbody id="body-paper"><tr><td colspan="6"><div class="tbl-empty"><i class="bi bi-hourglass-split"></i>Loading...</div></td></tr></tbody>
            </table>
          </div>
        </div>

        <!-- FINISHES -->
        <div id="tab-finishes" style="display:none">
          <div class="card">
            <div class="card-hdr">
              <span class="card-title"><i class="bi bi-stars" style="color:var(--accent);"></i> Finishing Options</span>
              <button class="btn btn-primary btn-sm" onclick="openAdd('finish')"><i class="bi bi-plus-lg"></i> Add Finish</button>
            </div>
            <table class="tbl" style="width:100%">
              <thead><tr><th>ID</th><th>Name</th><th>Setup Fee (₱)</th><th>Per-Unit Fee (₱)</th><th>Status</th><th>Active</th><th>Actions</th></tr></thead>
              <tbody id="body-finish"><tr><td colspan="7"><div class="tbl-empty"><i class="bi bi-hourglass-split"></i>Loading...</div></td></tr></tbody>
            </table>
          </div>
        </div>

        <!-- SIZES -->
        <div id="tab-sizes" style="display:none">
          <div class="card">
            <div class="card-hdr">
              <span class="card-title"><i class="bi bi-aspect-ratio" style="color:var(--accent);"></i> Print Sizes</span>
              <button class="btn btn-primary btn-sm" onclick="openAdd('size')"><i class="bi bi-plus-lg"></i> Add Size</button>
            </div>
            <table class="tbl" style="width:100%">
              <thead><tr><th>ID</th><th>Name</th><th>Multiplier</th><th>Status</th><th>Active</th><th>Actions</th></tr></thead>
              <tbody id="body-size"><tr><td colspan="6"><div class="tbl-empty"><i class="bi bi-hourglass-split"></i>Loading...</div></td></tr></tbody>
            </table>
          </div>
        </div>

      </div><!-- .page -->
    </div><!-- .a-content -->
  </div><!-- .a-main -->
</div><!-- .a-app -->

<!-- ══ ADD / EDIT MODAL ══ -->
<div class="modal-bg" id="specModal">
  <div class="modal-box">
    <div class="modal-title" id="modalTitle">Add Spec</div>
    <input type="hidden" id="modalId">
    <input type="hidden" id="modalType">
    <div class="fi">
      <label>Name</label>
      <input type="text" id="modalName" placeholder="e.g. 14pt Cardstock">
    </div>
    <div id="fieldMultiplier" class="fi">
      <label>Multiplier</label>
      <input type="number" step="0.01" min="0" id="modalMultiplier" placeholder="1.00">
    </div>
    <div id="fieldSetup" class="fi" style="display:none">
      <label>Setup Fee (₱)</label>
      <input type="number" step="0.01" min="0" id="modalSetupFee" placeholder="0.00">
    </div>
    <div id="fieldPerUnit" class="fi" style="display:none">
      <label>Per-Unit Fee (₱)</label>
      <input type="number" step="0.01" min="0" id="modalPerUnit" placeholder="0.00">
    </div>
    <div class="modal-actions">
      <button class="btn btn-outline btn-sm" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary btn-sm" onclick="saveSpec()"><i class="bi bi-check-lg"></i> Save</button>
    </div>
  </div>
</div>

<!-- ══ DELETE CONFIRM ══ -->
<div class="confirm-bg" id="confirmModal">
  <div class="confirm-box">
    <i class="bi bi-trash3-fill" style="font-size:2.2rem;color:var(--danger);display:block;margin-bottom:12px;"></i>
    <div style="font-family:'Sora',sans-serif;font-weight:700;font-size:1rem;color:var(--navy);margin-bottom:8px;">Delete Record?</div>
    <div style="font-size:.83rem;color:var(--muted);margin-bottom:22px;line-height:1.6;">This cannot be undone. The record will be permanently removed.</div>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button class="btn btn-outline btn-sm" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger btn-sm" onclick="confirmDelete()"><i class="bi bi-trash3"></i> Delete</button>
    </div>
  </div>
</div>

<!-- ══ TOAST ══ -->
<div class="toast-pp" id="toastPP">
  <i class="bi bi-check-circle-fill" id="toastIcon" style="color:var(--success);"></i>
  <span id="toastMsg">Done</span>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar && overlay) {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  }
}

let currentTheme = localStorage.getItem('printpro-theme') || 'light';
document.documentElement.setAttribute('data-theme', currentTheme);

function toggleTheme() {
  currentTheme = currentTheme === 'light' ? 'dark' : 'light';
  localStorage.setItem('printpro-theme', currentTheme);
  document.documentElement.setAttribute('data-theme', currentTheme);
  const icon = document.getElementById('themeIcon');
  if (icon) icon.className = currentTheme === 'light' ? 'bi bi-moon-stars' : 'bi bi-sun';
}

function handleLogout(e) {
  e.preventDefault();
  if (confirm('Are you sure you want to log out?')) {
    window.location.href = '../api/logout.php';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const icon = document.getElementById('themeIcon');
  if (icon) icon.className = currentTheme === 'light' ? 'bi bi-moon-stars' : 'bi bi-sun';
});

const API = '../api/specs.php';
let allSpecs = { paper:[], finish:[], size:[] };
let pendingDelete = null;

/* ── TABS ─────────────────────────────────────── */
function switchTab(name, btn) {
  ['materials','finishes','sizes'].forEach(t => document.getElementById('tab-'+t).style.display='none');
  document.querySelectorAll('.spec-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-'+name).style.display='';
  btn.classList.add('active');
}

/* ── LOAD ─────────────────────────────────────── */
async function loadSpecs() {
  try {
    const res  = await fetch(API);
    const json = await res.json();
    if (!json.success) { showToast('Failed to load specs','error'); return; }
    allSpecs = { paper:[], finish:[], size:[] };
    json.data.forEach(s => {
      if (s.spec_type==='paper')  allSpecs.paper.push(s);
      if (s.spec_type==='finish') allSpecs.finish.push(s);
      if (s.spec_type==='size')   allSpecs.size.push(s);
    });
    renderTable('paper');
    renderTable('finish');
    renderTable('size');
  } catch(e) { showToast('Network error','error'); }
}

function renderTable(type) {
  const rows     = allSpecs[type];
  const isFinish = type === 'finish';
  const cols     = isFinish ? 7 : 6;
  const tbody    = document.getElementById('body-'+type);

  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="${cols}"><div class="tbl-empty"><i class="bi bi-inbox"></i>No records yet. Click "Add" to create one.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(r => {
    const isActive   = r.is_active == 1;
    const badge      = isActive
      ? '<span class="badge b-active">Active</span>'
      : '<span class="badge b-danger">Inactive</span>';
    const checked    = isActive ? 'checked' : '';
    const specCells  = isFinish
      ? `<td>₱${parseFloat(r.setup_fee   || 0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
         <td>₱${parseFloat(r.per_unit_fee|| 0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>`
      : `<td>${parseFloat(r.price_modifier||0).toFixed(2)}×</td>`;

    return `<tr>
      <td style="font-weight:700;color:var(--muted);font-size:.72rem;">#${r.id}</td>
      <td style="font-weight:600;">${esc(r.name)}</td>
      ${specCells}
      <td>${badge}</td>
      <td>
        <label class="toggle-wrap">
          <input type="checkbox" ${checked} onchange="toggleActive('${type}',${r.id},this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </td>
      <td style="display:flex;gap:6px;">
        <button class="btn-icon" onclick="openEdit('${type}',${r.id})" title="Edit"><i class="bi bi-pencil"></i></button>
        <button class="btn-icon del" onclick="askDelete('${type}',${r.id})" title="Delete"><i class="bi bi-trash3"></i></button>
      </td>
    </tr>`;
  }).join('');
}

/* ── TOGGLE ───────────────────────────────────── */
async function toggleActive(type, id, active) {
  const row = allSpecs[type].find(r => r.id == id);
  if (!row) return;
  const body = new URLSearchParams({
    id, spec_type: type, name: row.name, is_active: active ? 1 : 0,
    price_modifier: row.price_modifier || 0,
    per_unit_fee: row.per_unit_fee || 0
  });
  const res  = await fetch(API, { method:'PUT', body });
  const json = await res.json();
  if (json.success) { showToast(active ? 'Activated' : 'Deactivated'); loadSpecs(); }
  else showToast(json.message || 'Error','error');
}

/* ── ADD MODAL ────────────────────────────────── */
function openAdd(type) {
  document.getElementById('modalId').value   = '';
  document.getElementById('modalType').value = type;
  document.getElementById('modalTitle').textContent = 'Add ' + typeName(type);
  document.getElementById('modalName').value = '';
  document.getElementById('modalMultiplier').value = '';
  document.getElementById('modalSetupFee').value   = '';
  document.getElementById('modalPerUnit').value    = '';
  setFinishFields(type === 'finish');
  document.getElementById('specModal').classList.add('open');
  setTimeout(() => document.getElementById('modalName').focus(), 100);
}

/* ── EDIT MODAL ───────────────────────────────── */
function openEdit(type, id) {
  const row = allSpecs[type].find(r => r.id == id);
  if (!row) return;
  document.getElementById('modalId').value   = id;
  document.getElementById('modalType').value = type;
  document.getElementById('modalTitle').textContent = 'Edit ' + typeName(type);
  document.getElementById('modalName').value       = row.name;
  document.getElementById('modalMultiplier').value = row.price_modifier || '';
  document.getElementById('modalSetupFee').value   = row.setup_fee || '';
  document.getElementById('modalPerUnit').value    = row.per_unit_fee || '';
  setFinishFields(type === 'finish');
  document.getElementById('specModal').classList.add('open');
}

function setFinishFields(isFinish) {
  document.getElementById('fieldMultiplier').style.display = isFinish ? 'none' : '';
  document.getElementById('fieldSetup').style.display      = isFinish ? '' : 'none';
  document.getElementById('fieldPerUnit').style.display    = isFinish ? '' : 'none';
}

function closeModal() { document.getElementById('specModal').classList.remove('open'); }

/* ── SAVE ─────────────────────────────────────── */
async function saveSpec() {
  const id   = document.getElementById('modalId').value;
  const type = document.getElementById('modalType').value;
  const name = document.getElementById('modalName').value.trim();
  if (!name) { document.getElementById('modalName').focus(); return; }

  const isFinish = type === 'finish';
  const body = new URLSearchParams({
    spec_type: type, name, is_active: 1,
    price_modifier: isFinish
      ? document.getElementById('modalSetupFee').value
      : document.getElementById('modalMultiplier').value,
    per_unit_fee: document.getElementById('modalPerUnit').value
  });
  if (id) body.append('id', id);

  const res  = await fetch(API, { method: id ? 'PUT' : 'POST', body });
  const json = await res.json();
  closeModal();
  if (json.success) { showToast(id ? 'Updated!' : 'Added!'); loadSpecs(); }
  else showToast(json.message || 'Save failed','error');
}

/* ── DELETE ───────────────────────────────────── */
function askDelete(type, id) {
  pendingDelete = { type, id };
  document.getElementById('confirmModal').classList.add('open');
}
function closeConfirm() {
  pendingDelete = null;
  document.getElementById('confirmModal').classList.remove('open');
}
async function confirmDelete() {
  if (!pendingDelete) return;
  const { type, id } = pendingDelete;
  const body = new URLSearchParams({ id, spec_type: type });
  const res  = await fetch(API, { method:'DELETE', body });
  const json = await res.json();
  closeConfirm();
  if (json.success) { showToast('Deleted!'); loadSpecs(); }
  else showToast(json.message || 'Delete failed','error');
}

/* ── HELPERS ──────────────────────────────────── */
function typeName(t) { return t==='paper'?'Material':t==='finish'?'Finish':'Size'; }
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function showToast(msg, type='success') {
  const t = document.getElementById('toastPP');
  document.getElementById('toastIcon').className = type==='success'
    ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill';
  document.getElementById('toastIcon').style.color = type==='success' ? 'var(--success)' : 'var(--danger)';
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

/* Close on backdrop click */
document.getElementById('specModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });
document.getElementById('confirmModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeConfirm(); });

loadSpecs();
</script>
</body>
</html>
