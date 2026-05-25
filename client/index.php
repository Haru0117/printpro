<?php
session_start();
// Auth guard — must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html?action=login');
    exit;
}
// Redirect everyone to the proper dashboard files
$_role = strtolower($_SESSION['role'] ?? '');
if ($_role === 'admin' || $_role === 'super_admin') {
    header('Location: ../admin_dashboard.html');
    exit;
}
header('Location: ../client_dashboard.html');
exit;
$userName = $_SESSION['name'] ?? 'Client';
$userEmail = $_SESSION['email'] ?? 'client@example.com';
$userRole = $_SESSION['role'] ?? 'client';

// Get client credit balance
require_once '../includes/db.php';
$credit_balance = 0;
$recent_transactions = [];
try {
  $stmt = $pdo->prepare("SELECT cc.balance FROM client_credits cc JOIN clients c ON cc.client_id = c.id WHERE c.user_id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $row = $stmt->fetch();
  $credit_balance = $row ? floatval($row['balance']) : 0;

  // Get recent credit transactions
  $stmt = $pdo->prepare("
    SELECT ct.transaction_type, ct.amount, ct.description, ct.created_at, COALESCE(o.order_number, o.id) as order_number
    FROM credit_transactions ct
    JOIN clients c ON ct.client_id = c.id
    LEFT JOIN orders o ON ct.order_id = o.id
    WHERE c.user_id = ?
    ORDER BY ct.created_at DESC
    LIMIT 5
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $recent_transactions = $stmt->fetchAll();
} catch (Exception $e) {
  $credit_balance = 0;
  $recent_transactions = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PrintPro Client — Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link
    href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <link href="../assets/css/printpro.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

  <!-- ══════════ APP SHELL ══════════ -->
  <div id="appShell" style="height:100vh;display:flex;flex-direction:column;">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <span style="font-size:.78rem;color:var(--muted);font-weight:600;" id="topPageTitle">Dashboard</span>
      </div>
      <div class="topbar-right">
        <i class="bi bi-bell" style="color:var(--muted);font-size:1.05rem;cursor:pointer;"></i>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;background:rgba(45,206,137,0.05);border:1px solid rgba(45,206,137,0.2);border-radius:8px;cursor:pointer;" onclick="showPage('cbilling')" title="Click to view billing">
          <i class="bi bi-credit-card" style="color:var(--success);font-size:1.1rem;"></i>
          <div style="font-size:.85rem;line-height:1.2;">
            <div style="color:var(--muted);font-size:.7rem;font-weight:500;">CREDITS</div>
            <div style="font-weight:700;color:var(--success);font-size:.95rem;" id="topbarCredits">₱<?php echo number_format($credit_balance, 0); ?></div>
          </div>
        </div>
        <div class="avatar" id="topAvatar" style="background:linear-gradient(135deg,var(--accent),var(--teal));cursor:pointer;width:32px;height:32px;"
          onclick="showPage('account')"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
        <button class="btn btn-outline btn-sm" onclick="location.href='../api/logout.php'"><i
            class="bi bi-box-arrow-right"></i> Logout</button>
      </div>
    </div>

    <div class="app-body">

      <!-- SIDEBAR -->
      <div class="sidebar" id="sidebarEl">
        <div style="padding: 24px 24px 18px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255, 255, 255, .08);">
          <img src="../assets/img/logo.png" alt="PrintPro" style="height: 24px; width: auto; object-fit: contain; max-width: 90px; filter: brightness(0) invert(1); flex-shrink: 0;">
          <span style="font-size: .65rem; background: #1d8cf8; color: #fff; padding: 2px 9px; border-radius: 6px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px; flex-shrink: 0;">Client</span>
        </div>
        <div id="clientNav">
          <div class="nav-section">
            <div class="nav-label">Overview</div>
            <div class="nav-item active" onclick="showPage('cdashboard',this)"><i class="bi bi-speedometer2"></i>
              Dashboard</div>
            <div class="nav-item" onclick="showPage('corders',this)"><i class="bi bi-box-seam"></i> My Orders</div>
          </div>
          <div class="nav-section">
            <div class="nav-label">Order</div>
            <div class="nav-item" onclick="showPage('create',this)"><i class="bi bi-plus-circle"></i> Create Order</div>
            <div class="nav-item" onclick="showPage('templates',this)"><i class="bi bi-layout-text-window"></i>
              Templates</div>
            <div class="nav-item" onclick="showPage('cfiles',this)"><i class="bi bi-folder2"></i> Files</div>
            <div class="nav-item" onclick="showPage('ctrack',this)"><i class="bi bi-truck"></i> Track Orders</div>
          </div>
          <div class="nav-section">
            <div class="nav-label">Billing</div>
            <div class="nav-item" onclick="showPage('cbilling',this)"><i class="bi bi-credit-card"></i> Billing</div>
          </div>
          <div class="nav-section">
            <div class="nav-label">My Account</div>
            <div class="nav-item" onclick="showPage('account',this)"><i class="bi bi-person-circle"></i> Account</div>
          </div>
        </div>
        <div class="sidebar-logout">
          <a href="../api/logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
      </div>

      <!-- CONTENT AREA -->
      <div class="content">

        <!-- ════ CLIENT: DASHBOARD ════ -->
        <div class="page active" id="page-cdashboard">
          <div class="page-hdr" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <h4 id="clientGreeting">Welcome back!</h4>
              <p>Manage your printing operations</p>
            </div>
            <button class="btn btn-primary" onclick="showPage('create')"><i class="bi bi-plus-lg"></i> New
              Order</button>
          </div>
          <div
            style="background:linear-gradient(135deg,var(--navy),#2a3558);border-radius:16px;padding:24px;margin-bottom:20px;color:#fff;">
            <h2 style="font-family:'Sora',sans-serif;font-weight:800;margin-bottom:8px;" id="heroGreeting">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p style="color:rgba(255,255,255,.7);margin-bottom:16px;" id="heroBannerSubtitle">Loading your orders...</p>
            <button class="btn btn-primary" onclick="showPage('create')">+ New Project →</button>
          </div>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
            <div class="kpi-card" onclick="showPage('corders')">
              <div class="kpi-icon" style="background:rgba(29,140,248,.1);color:var(--accent);"><i
                  class="bi bi-box-seam"></i></div>
              <div>
                <div class="kpi-lbl">Active Orders</div>
                <div class="kpi-val" id="kpiActiveOrders">—</div>
              </div>
            </div>
            <div class="kpi-card" onclick="showPage('cfiles')">
              <div class="kpi-icon" style="background:rgba(62,198,198,.1);color:var(--teal);"><i
                  class="bi bi-folder2"></i></div>
              <div>
                <div class="kpi-lbl">Assets</div>
                <div class="kpi-val" id="kpiFiles">—</div>
              </div>
            </div>
            <div class="kpi-card" onclick="showPage('cbilling')">
              <div class="kpi-icon" style="background:rgba(45,206,137,.1);color:var(--success);"><i
                  class="bi bi-credit-card"></i></div>
              <div>
                <div class="kpi-lbl">Credits</div>
                <div class="kpi-val" id="kpiCredits">₱<?php echo number_format($credit_balance, 0); ?></div>
              </div>
            </div>
          </div>

          <!-- Credits Overview Section -->
          <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:20px;">
            <div class="card" style="padding:24px;">
              <div style="display:flex;align-items:center;margin-bottom:16px;">
                <div style="background:rgba(45,206,137,.1);color:var(--success);padding:12px;border-radius:12px;margin-right:16px;">
                  <i class="bi bi-credit-card" style="font-size:1.5rem;"></i>
                </div>
                <div>
                  <h5 style="margin:0;font-weight:600;">Credit Balance</h5>
                  <p style="margin:4px 0 0 0;color:var(--muted);font-size:.9rem;">Available for orders</p>
                </div>
              </div>
              <div id="dashCreditBalance" style="font-size:2rem;font-weight:700;color:var(--success);margin-bottom:8px;">
                ₱<?php echo number_format($credit_balance, 2); ?>
              </div>
              <div style="color:var(--muted);font-size:.85rem;">
                Used for order payments
              </div>
            </div>

            <div class="card" style="padding:24px;">
              <h5 style="margin-bottom:16px;font-weight:600;">Recent Transactions</h5>
              <div style="max-height:200px;overflow-y:auto;">
                <?php if (empty($recent_transactions)): ?>
                  <div style="text-align:center;color:var(--muted);padding:20px;">
                    <i class="bi bi-info-circle" style="font-size:2rem;margin-bottom:8px;"></i>
                    <p>No transactions yet</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($recent_transactions as $transaction): 
                    $order_num = $transaction['order_id'] ? 'PPR-' . str_pad($transaction['order_id'], 3, '0', STR_PAD_LEFT) : null;
                  ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
                      <div>
                        <div style="font-weight:500;">
                          <?php echo htmlspecialchars($transaction['description'] ?: 'Credit ' . $transaction['transaction_type']); ?>
                          <?php if ($order_num): ?>
                            <span style="color:var(--muted);font-size:.8rem;">(<?php echo $order_num; ?>)</span>
                          <?php endif; ?>
                        </div>
                        <div style="color:var(--muted);font-size:.8rem;">
                          <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                        </div>
                      </div>
                      <div style="font-weight:600;<?php echo $transaction['transaction_type'] === 'add' ? 'color:var(--success);' : 'color:var(--danger);'; ?>">
                        <?php echo $transaction['transaction_type'] === 'add' ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div style="margin-top:16px;text-align:center;">
                <button class="btn btn-outline btn-sm" onclick="showPage('cbilling')">View All Transactions</button>
              </div>
            </div>
          </div>

          <!-- Recent Orders on Dashboard -->
          <div class="card orders-card">
            <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;">
              <span class="card-title">Recent Orders</span>
              <div style="display:flex;gap:8px;">
                <button class="btn btn-primary btn-sm" style="padding:6px 14px;font-size:.78rem;" onclick="showPage('create')">+ New Order</button>
                <button class="btn btn-outline btn-sm" style="padding:6px 14px;font-size:.78rem;" onclick="showPage('corders')">View all</button>
              </div>
            </div>
            <table class="tbl orders-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>JOB NAME</th>
                  <th>QTY</th>
                  <th>STATUS</th>
                  <th>DUE</th>
                  <th>TOTAL</th>
                </tr>
              </thead>
              <tbody id="dashRecentOrdersTbody">
                <tr>
                  <td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
                    <i class="bi bi-arrow-repeat spin" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                    Loading...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ════ CLIENT: CREATE ORDER ════ -->
        <div class="page" id="page-create">
          <div class="page-hdr">
            <h4>Create Bulk Order</h4>
            <p>Fill in your specifications for an instant price estimate</p>
          </div>
          <div class="order-layout">
            <div>
              <div class="wizard-card">
                <div class="wizard-hdr">
                  <div class="step-num">1</div>
                  <div class="step-title">Product Type</div>
                </div>
                <div class="wizard-body">
                  <div class="product-types" id="productTypes">
                    <button class="pt-btn active" onclick="selectProduct(this,'Flyers')">Flyers</button>
                    <button class="pt-btn" onclick="selectProduct(this,'Brochures')">Brochures</button>
                    <button class="pt-btn" onclick="selectProduct(this,'Banners')">Banners</button>
                  </div>
                </div>
              </div>
              <div class="wizard-card">
                <div class="wizard-hdr">
                  <div class="step-num">2</div>
                  <div class="step-title">Specs</div>
                </div>
                <div class="wizard-body">
                  <div class="dim-grid">
                    <div class="form-row" style="grid-column: 1 / -1; margin-bottom: 12px;">
                      <label class="form-label">Job Name</label>
                      <input type="text" class="form-ctrl" id="jobNameInput" placeholder="e.g. Summer Promo Flyer">
                    </div>
                    <div class="form-row"><label class="form-label">Size</label><select class="form-ctrl"
                        id="sizeSelect" onchange="calcPrice()">
                        <option value="0">Loading sizes...</option>
                      </select></div>
                    <div class="form-row"><label class="form-label">Paper</label><select class="form-ctrl"
                        id="paperSelect" onchange="calcPrice()">
                        <option value="0">Loading materials...</option>
                      </select></div>
                    <div class="form-row"><label class="form-label">Finish</label><select class="form-ctrl"
                        id="finishSelect" onchange="calcPrice()">
                        <option value="0">Loading finishes...</option>
                      </select></div>
                    <div class="form-row"><label class="form-label">Sides</label><select class="form-ctrl"
                        id="sidesSelect" onchange="calcPrice()">
                        <option value="1">Single</option>
                        <option value="1.5">Double (+50%)</option>
                      </select></div>
                  </div>
                </div>
              </div>
              <div class="wizard-card">
                <div class="wizard-hdr">
                  <div class="step-num">3</div>
                  <div class="step-title">Quantity</div>
                </div>
                <div class="wizard-body">
                  <input type="range" class="qty-slider" id="qtySlider" min="100" max="10000" step="1" value="100">
                  <input type="number" id="qtyDisplay" min="100" max="10000" value="100" style="width: 100px; padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; text-align: center; font-size: 1rem; margin-top: 12px; cursor: pointer; background: white; pointer-events: auto;">
                </div>
              </div>
              <div class="wizard-card">
                <div class="wizard-hdr">
                  <div class="step-num">4</div>
                  <div class="step-title">Upload Artwork</div>
                </div>
                <div class="wizard-body">
                  <div id="upload-area" style="border: 2px dashed var(--border); border-radius: 12px; padding: 24px; text-align: center; background: var(--off); cursor: pointer; transition: all 0.2s;" onclick="document.getElementById('artworkUploadInput').click()">
                    <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2.5rem; color: var(--accent); display: block; margin-bottom: 8px;"></i>
                    <span style="font-size: .85rem; font-weight: 600; color: var(--navy); display: block;">Drag & Drop Artwork Here</span>
                    <span style="font-size: .75rem; color: var(--muted); display: block; margin-top: 4px;">Supports PDF, AI, PSD, PNG, JPG up to 500MB</span>
                    <input type="file" id="artworkUploadInput" accept=".pdf,.ai,.psd,.png,.jpg,.jpeg" style="display: none;" onchange="handleArtworkSelection(this)">
                  </div>
                  <div id="upload-preview" style="display: none; margin-top: 14px;">
                    <div style="font-size: .75rem; font-weight: 700; color: var(--navy); margin-bottom: 8px;">SELECTED FILE:</div>
                    <div class="file-card" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: rgba(29, 140, 248, 0.05); border: 1px solid rgba(29, 140, 248, 0.2); border-radius: 8px;">
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="previewFileIcon" style="font-size: 1.5rem;">📄</span>
                        <div style="text-align: left;">
                          <div id="previewFileName" style="font-size: .8rem; font-weight: 600; color: var(--navy); word-break: break-all; max-width: 250px;">artwork.pdf</div>
                          <div id="previewFileSize" style="font-size: .7rem; color: var(--muted);">0.00 MB</div>
                        </div>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-danger" id="removeArtworkBtn" style="padding: 2px 8px; font-size: .7rem;" onclick="removeSelectedArtwork(event)">Remove</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <div class="summary-card">
                <div class="sum-title">PRICE PREVIEW</div>
                <div class="sum-row"><span>Job Name</span><span id="sumJobName" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600;">Untitled</span></div>
                <div class="sum-row"><span>Product</span><span id="sumProduct">Flyers</span></div>
                <div class="sum-row"><span>Quantity</span><span id="sumQty">100 units</span></div>
                <div class="sum-row"><span>Artwork</span><span id="sumArtwork" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--muted);">None</span></div>
                <hr class="sum-divider">
                <div class="sum-total"><span class="sum-total-lbl">Total</span><span class="sum-total-val"
                    id="sumTotal">₱—</span></div>
                <button class="btn-place" id="placeOrderBtn" onclick="placeOrder()">Place Order →</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ════ CLIENT: MY ORDERS ════ -->
        <div class="page" id="page-corders">
          <div class="page-hdr" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <h4>All Orders</h4>
              <p>Your complete order history</p>
            </div>
            <button class="btn btn-primary" onclick="showPage('create')"><i class="bi bi-plus-lg"></i> New
              Order</button>
          </div>
          <div class="card orders-card">
            <div class="card-hdr">
              <span class="card-title">Order History</span>
              <div style="display:flex;gap:8px;">
                <select class="form-ctrl" style="font-size:.75rem;padding:4px 8px;width:auto;"
                  onchange="filterOrdersBySelect(this)">
                  <option>All Status</option>
                  <option>Active</option>
                  <option>Pending</option>
                  <option>Done</option>
                  <option>Delivered</option>
                </select>
              </div>
            </div>
            <table class="tbl orders-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>JOB NAME</th>
                  <th>TYPE</th>
                  <th>QTY</th>
                  <th>STATUS</th>
                  <th>DUE</th>
                  <th>TOTAL</th>
                  <th>ACTION</th>
                </tr>
              </thead>
              <tbody id="ordersTbody">
                <tr>
                  <td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">
                    <i class="bi bi-arrow-repeat spin" style="font-size:2rem;display:block;margin-bottom:12px;"></i>
                    Loading your orders history...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ════ CLIENT: BILLING ════ -->
        <div class="page" id="page-cbilling">
          <div class="page-hdr">
            <h4>Billing & Credits</h4>
            <p>Manage your credits and view transaction history</p>
          </div>

          <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:20px;">
            <div class="card" style="padding:24px;">
              <div style="display:flex;align-items:center;margin-bottom:16px;">
                <div style="background:rgba(45,206,137,.1);color:var(--success);padding:12px;border-radius:12px;margin-right:16px;">
                  <i class="bi bi-credit-card" style="font-size:1.5rem;"></i>
                </div>
                <div>
                  <h5 style="margin:0;font-weight:600;">Current Balance</h5>
                  <p style="margin:4px 0 0 0;color:var(--muted);font-size:.9rem;">Available credits</p>
                </div>
              </div>
              <div style="font-size:2.5rem;font-weight:700;color:var(--success);margin-bottom:8px;">
                ₱<?php echo number_format($credit_balance, 2); ?>
              </div>
              <div style="color:var(--muted);font-size:.85rem;">
                Credits are automatically deducted when placing orders
              </div>
            </div>

            <div class="card" style="padding:24px;">
              <h5 style="margin-bottom:16px;font-weight:600;">Credit Usage Summary</h5>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                <div style="text-align:center;">
                  <div style="font-size:1.5rem;font-weight:700;color:var(--primary);">₱10,000</div>
                  <div style="color:var(--muted);font-size:.8rem;">Initial Credits</div>
                </div>
                <div style="text-align:center;">
                  <div style="font-size:1.5rem;font-weight:700;color:var(--danger);">-₱<?php echo number_format(10000 - $credit_balance, 2); ?></div>
                  <div style="color:var(--muted);font-size:.8rem;">Used</div>
                </div>
                <div style="text-align:center;">
                  <div style="font-size:1.5rem;font-weight:700;color:var(--success);">₱<?php echo number_format($credit_balance, 2); ?></div>
                  <div style="color:var(--muted);font-size:.8rem;">Remaining</div>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-hdr">
              <span class="card-title">Credit Transaction History</span>
            </div>
            <div style="padding:20px;">
              <?php if (empty($recent_transactions)): ?>
                <div style="text-align:center;color:var(--muted);padding:40px;">
                  <i class="bi bi-receipt" style="font-size:3rem;margin-bottom:16px;"></i>
                  <h5>No transactions yet</h5>
                  <p>Your credit transaction history will appear here</p>
                </div>
              <?php else: ?>
                <div style="overflow-x:auto;">
                  <table class="tbl">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Order</th>
                        <th>Type</th>
                        <th>Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Get all transactions for billing page
                      try {
                        $stmt = $pdo->prepare("
                          SELECT ct.transaction_type, ct.amount, ct.description, ct.created_at, COALESCE(o.order_number, o.id) as order_number
                          FROM credit_transactions ct
                          JOIN clients c ON ct.client_id = c.id
                          LEFT JOIN orders o ON ct.order_id = o.id
                          WHERE c.user_id = ?
                          ORDER BY ct.created_at DESC
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $all_transactions = $stmt->fetchAll();
                      } catch (Exception $e) {
                        $all_transactions = [];
                      }

                      foreach ($all_transactions as $transaction): 
                        $order_num = $transaction['order_id'] ? 'PPR-' . str_pad($transaction['order_id'], 3, '0', STR_PAD_LEFT) : null;
                      ?>
                        <tr>
                          <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                          <td><?php echo htmlspecialchars($transaction['description'] ?: 'Credit ' . $transaction['transaction_type']); ?></td>
                          <td><?php echo $order_num ? '#' . $order_num : '—'; ?></td>
                          <td>
                            <span class="badge <?php echo $transaction['transaction_type'] === 'add' ? 'b-success' : 'b-danger'; ?>">
                              <?php echo ucfirst($transaction['transaction_type']); ?>
                            </span>
                          </td>
                          <td style="font-weight:600;<?php echo $transaction['transaction_type'] === 'add' ? 'color:var(--success);' : 'color:var(--danger);'; ?>">
                            <?php echo $transaction['transaction_type'] === 'add' ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- ════ ACCOUNT ════ -->
        <div class="page" id="page-account">
          <div class="account-header">
            <div class="acc-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
            <div>
              <div class="acc-name"><?php echo $userName; ?></div>
              <div class="acc-email"><?php echo $userEmail; ?></div><span class="acc-badge">Premium Client</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="toast" id="toast"><i class="bi bi-check-circle-fill"></i> <span id="toastMsg"></span></div>

  <!-- Proof Review Modal -->
  <div class="modal" id="proofModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(10,18,42,0.8); backdrop-filter:blur(8px); z-index:9999; justify-content:center; align-items:center; opacity:0; transition:opacity 0.2s ease-in-out;">
    <div style="background:var(--card); border:1px solid var(--border); border-radius:20px; width:95%; max-width:650px; padding:28px; box-shadow:0 15px 40px rgba(0,0,0,0.5); text-align:left; transform:scale(0.9); transition:transform 0.2s ease-in-out; max-height:90vh; overflow-y:auto;" id="proofModalContent">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h4 style="margin:0; font-family:'Sora',sans-serif; font-weight:800; display:flex; align-items:center; gap:8px;">
          <i class="bi bi-file-earmark-check-fill" style="color:var(--accent);"></i>
          Review Print Proof
        </h4>
        <button type="button" class="btn btn-sm btn-outline" style="border-radius:50%; width:32px; height:32px; padding:0; display:grid; place-items:center;" onclick="closeProofModal()"><i class="bi bi-x" style="font-size:1.2rem;"></i></button>
      </div>
      
      <div style="background:rgba(29, 140, 248, 0.05); border:1px solid rgba(29, 140, 248, 0.2); border-radius:12px; padding:16px; margin-bottom:20px;">
        <div style="font-size:.75rem; font-weight:700; color:var(--accent); letter-spacing:.05em; text-transform:uppercase; margin-bottom:6px;">Order Details</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:.85rem;">
          <div><strong>Order #:</strong> <span id="proofOrderNum">PPR-000</span></div>
          <div><strong>Job Name:</strong> <span id="proofJobName">Flyer Pack</span></div>
          <div><strong>Specs:</strong> <span id="proofSpecs">Flyers (100 units)</span></div>
          <div><strong>Turnaround:</strong> <span id="proofTurnaround">Standard</span></div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">
        <!-- Client Original Artwork -->
        <div style="border:1px solid var(--border); border-radius:12px; padding:16px; text-align:center; background:var(--off);">
          <div style="font-size:.75rem; font-weight:700; color:var(--muted); margin-bottom:10px; text-transform:uppercase;">Your Uploaded Artwork</div>
          <i class="bi bi-file-earmark-pdf" style="font-size:3rem; color:var(--muted); display:block; margin-bottom:8px;"></i>
          <span style="font-size:.78rem; font-weight:600; display:block; word-break:break-all; margin-bottom:10px;" id="proofOriginalFileName">artwork.pdf</span>
          <a id="proofOriginalDownload" href="#" target="_blank" class="btn btn-outline btn-sm" style="display:inline-flex; align-items:center; gap:6px;"><i class="bi bi-download"></i> View Artwork</a>
        </div>
        
        <!-- Admin Proof File -->
        <div style="border:2px dashed var(--accent); border-radius:12px; padding:16px; text-align:center; background:rgba(29,140,248,0.02);">
          <div style="font-size:.75rem; font-weight:700; color:var(--accent); margin-bottom:10px; text-transform:uppercase;">Admin's Ready Proof</div>
          <i class="bi bi-file-earmark-check" style="font-size:3rem; color:var(--accent); display:block; margin-bottom:8px;"></i>
          <span style="font-size:.78rem; font-weight:600; display:block; word-break:break-all; margin-bottom:10px;" id="proofAdminFileName">proof_version_1.pdf</span>
          <a id="proofAdminDownload" href="#" target="_blank" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px; background:var(--accent); color:#fff; border:none;"><i class="bi bi-eye"></i> View Print Proof</a>
        </div>
      </div>
      
      <div style="background:rgba(251, 99, 64, 0.05); border:1px solid rgba(251, 99, 64, 0.2); border-radius:12px; padding:16px; margin-bottom:24px; display:flex; gap:12px; align-items:center;">
        <i class="bi bi-exclamation-triangle-fill" style="color:var(--warning); font-size:1.5rem; flex-shrink:0;"></i>
        <div style="font-size:.8rem; color:var(--muted); line-height:1.4;">
          Please carefully inspect the proof before approval. Approved proofs move immediately to prepress and printing stages. If changes are needed, click Request Revision.
        </div>
      </div>

      <input type="hidden" id="proofOrderIdVal">

      <div style="display:flex; justify-content:flex-end; gap:12px;">
        <button type="button" class="btn btn-outline" style="border-color:var(--danger); color:var(--danger);" onclick="submitProofReview('revise')">
          <i class="bi bi-x-circle"></i> Request Revision
        </button>
        <button type="button" class="btn btn-primary" style="background:var(--success); border:none; color:#fff;" onclick="submitProofReview('approve')">
          <i class="bi bi-check-circle"></i> Approve Proof & Print
        </button>
      </div>
    </div>
  </div>

  <script src="../assets/js/printpro.js"></script>
  <script>
    // Debounce utility function to prevent excessive AJAX calls
    function debounce(func, wait) {
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }

    // Load credits
    function loadCredits() {
      fetch('../api/get_credits.php')
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const balanceNum = parseFloat(data.balance_raw) || 0;
            const formatted  = '₱' + balanceNum.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Topbar
            const topbarCredits = document.getElementById('topbarCredits');
            if (topbarCredits) topbarCredits.textContent = formatted;

            // KPI card — use stable ID
            const kpiCredits = document.getElementById('kpiCredits');
            if (kpiCredits) kpiCredits.textContent = formatted;

            // Dashboard credit balance card
            const dashCredit = document.getElementById('dashCreditBalance');
            if (dashCredit) dashCredit.textContent = formatted;

            // Update balance in credits overview card
            const overviewCards = document.querySelectorAll('div[style*="font-size:2rem"]');
            overviewCards.forEach(card => {
              if (card.textContent.includes('₱') && card.parentElement.parentElement.querySelector('h5')?.textContent.includes('Credit Balance')) {
                card.textContent = '₱' + data.balance;
              }
            });

            // Update remaining balance
            const remainingCards = document.querySelectorAll('div[style*="text-align:center"]');
            remainingCards.forEach(card => {
              const balanceDiv = card.querySelector('div:first-child');
              if (balanceDiv && balanceDiv.textContent.includes('₱') && card.querySelector('div:last-child')?.textContent.includes('Remaining')) {
                balanceDiv.textContent = '₱' + data.balance;
              }
              const usedDiv = card.querySelector('div:first-child');
              if (usedDiv && usedDiv.textContent.includes('-₱') && card.querySelector('div:last-child')?.textContent.includes('Used')) {
                usedDiv.textContent = '-₱' + data.used;
              }
            });

            // Update transactions in recent transactions list
            const recentTransList = document.querySelector('[style*="max-height:200px"]');
            if (recentTransList && data.transactions.length > 0) {
              recentTransList.innerHTML = data.transactions.map(t => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
                  <div>
                    <div style="font-weight:500;">
                      ${t.description || 'Credit ' + t.transaction_type}
                      ${t.order_number ? '<span style="color:var(--muted);font-size:.8rem;">(' + t.order_number + ')</span>' : ''}
                    </div>
                    <div style="color:var(--muted);font-size:.8rem;">
                      ${new Date(t.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'})}
                    </div>
                  </div>
                  <div style="font-weight:600;${t.transaction_type === 'add' ? 'color:var(--success);' : 'color:var(--danger);'}">
                    ${t.transaction_type === 'add' ? '+' : '-'}₱${parseFloat(t.amount).toFixed(2)}
                  </div>
                </div>
              `).join('');
            }

            // Update billing page transaction table
            const transTable = document.querySelector('table.tbl tbody');
            if (transTable && data.transactions.length > 0) {
              transTable.innerHTML = data.transactions.map(t => `
                <tr>
                  <td>${new Date(t.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'})}</td>
                  <td>${t.description || 'Credit ' + t.transaction_type}</td>
                  <td>${t.order_number ? '#' + t.order_number : '—'}</td>
                  <td>
                    <span class="badge ${t.transaction_type === 'add' ? 'b-success' : 'b-danger'}">
                      ${t.transaction_type.charAt(0).toUpperCase() + t.transaction_type.slice(1)}
                    </span>
                  </td>
                  <td style="font-weight:600;${t.transaction_type === 'add' ? 'color:var(--success);' : 'color:var(--danger);'}">
                    ${t.transaction_type === 'add' ? '+' : '-'}₱${parseFloat(t.amount).toFixed(2)}
                  </td>
                </tr>
              `).join('');
            }
          }
        })
        .catch(err => console.log('Credits load error:', err));
    }

    // ── LOAD ORDER SPECS (dynamic dropdowns) ──────────────────
    async function loadOrderSpecs() {
      try {
        const res  = await fetch('../api/specs.php');
        const json = await res.json();
        if (!json.success) return;

        const sizes    = json.data.filter(s => s.spec_type === 'size'   && s.is_active == 1);
        const papers   = json.data.filter(s => s.spec_type === 'paper'  && s.is_active == 1);
        const finishes = json.data.filter(s => s.spec_type === 'finish' && s.is_active == 1);

        const sizeEl   = document.getElementById('sizeSelect');
        const paperEl  = document.getElementById('paperSelect');
        const finishEl = document.getElementById('finishSelect');

        sizeEl.innerHTML = '<option value="0">Select Size</option>' +
          sizes.map(s => `<option value="${parseFloat(s.price_modifier).toFixed(2)}" data-id="${s.id}">${s.name} (${parseFloat(s.price_modifier).toFixed(2)}×)</option>`).join('');

        paperEl.innerHTML = '<option value="0">Select Paper</option>' +
          papers.map(p => `<option value="${parseFloat(p.price_modifier).toFixed(2)}" data-id="${p.id}">${p.name} (${parseFloat(p.price_modifier).toFixed(2)}×)</option>`).join('');

        finishEl.innerHTML = '<option value="0">None</option>' +
          finishes.map(f => `<option value="${parseFloat(f.per_unit_fee||0).toFixed(4)}" data-id="${f.id}" data-setup="${f.setup_fee||0}">${f.name} (+₱${parseFloat(f.per_unit_fee||0).toFixed(2)}/unit)</option>`).join('');

      } catch(e) {
        console.warn('Could not load order specs:', e);
      }
    }

    // ── CLIENT ORDER WIZARD STATE & LOGIC ──────────────────
    let selectedProduct = 'Flyers';
    let selectedArtworkFile = null;

    function selectProduct(btn, product) {
        document.querySelectorAll('#productTypes .pt-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedProduct = product;
        document.getElementById('sumProduct').textContent = product;
        
        const jobNameInput = document.getElementById('jobNameInput');
        if (jobNameInput && !jobNameInput.value.trim()) {
            document.getElementById('sumJobName').textContent = product + ' Project';
        }
        
        calcPrice();
    }

    function updateSummaryJobName(val) {
        document.getElementById('sumJobName').textContent = val.trim() ? val.trim() : (selectedProduct + ' Project');
    }

    function updateQty(val) {
        document.getElementById('qtyDisplay').textContent = val;
        document.getElementById('sumQty').textContent = val + ' units';
        updatePricing();
    }

    let currentPricingData = { grand_total: 0 };

    async function updatePricing() {
        const qtySlider = document.getElementById('qtySlider');
        const qty = parseInt(qtySlider.value) || 100;

        // Update quantity display
        document.getElementById('qtyDisplay').textContent = qty;
        document.getElementById('sumQty').textContent = qty + ' units';

        const sizeSelect = document.getElementById('sizeSelect');
        const paperSelect = document.getElementById('paperSelect');
        const finishSelect = document.getElementById('finishSelect');

        if (!sizeSelect || !paperSelect || !finishSelect) return;

        const sizeText = sizeSelect.options[sizeSelect.selectedIndex]?.text || '';
        const paperText = paperSelect.options[paperSelect.selectedIndex]?.text || '';
        const finishText = finishSelect.options[finishSelect.selectedIndex]?.text || '';

        // Get custom dimensions (default to 4x6 if not available)
        let custom_width = 4.0;
        let custom_height = 6.0;

        // Try to extract dimensions from size text if it contains dimensions
        const sizeMatch = sizeText.match(/(\d+\.?\d*)\s*[x×]\s*(\d+\.?\d*)/i);
        if (sizeMatch) {
            custom_width = parseFloat(sizeMatch[1]);
            custom_height = parseFloat(sizeMatch[2]);
        }

        // Get bleed value (default to 0.125 if bleedSelect doesn't exist)
        let bleed = 0.125;
        const bleedSelect = document.getElementById('bleedSelect');
        if (bleedSelect) {
            const bleedText = bleedSelect.value;
            if (bleedText.includes('0.125')) bleed = 0.125;
            else if (bleedText.includes('0.25')) bleed = 0.25;
            else if (bleedText.includes('No Bleed') || bleedText === '0') bleed = 0;
        }

        // Resolve product_id from selectedProduct
        const productIdMap = { 'Flyers': 1, 'Brochures': 2, 'Booklets': 3, 'Cards': 4, 'Posters': 5, 'Mailers': 6, 'Banners': 1 };
        const resolvedProductId = productIdMap[selectedProduct] || 1;

        // Get turnaround and shipping (default to standard/free if selects don't exist)
        const turnaroundSelect = document.getElementById('turnaroundSelect');
        const turnaround = turnaroundSelect ? turnaroundSelect.value : 'standard';

        const shippingSelect = document.getElementById('shippingSelect');
        const shipping = shippingSelect ? shippingSelect.value : 'free';

        // Prepare form data
        const formData = new URLSearchParams();
        formData.append('product_id', resolvedProductId);
        formData.append('quantity', qty);
        formData.append('custom_width', custom_width);
        formData.append('custom_height', custom_height);
        formData.append('bleed', bleed);
        formData.append('material', paperText);
        formData.append('finish', finishText);
        formData.append('turnaround', turnaround);
        formData.append('shipping', shipping);

        try {
            const response = await fetch('../api/calculate_quote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });

            const result = await response.json();

            if (result.success && result.breakdown) {
                const d = result.breakdown;
                window.currentPricingData = d;

                // Update summary card
                const sumTotal = document.getElementById('sumTotal');
                if (sumTotal) {
                    sumTotal.textContent = '₱' + d.grand_total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    sumTotal.setAttribute('data-total-raw', d.grand_total.toFixed(2));
                }

                // Update additional summary fields if they exist
                const sumSubtotal = document.getElementById('sumSubtotal');
                if (sumSubtotal) sumSubtotal.textContent = '₱' + d.subtotal_final.toLocaleString('en-US', { minimumFractionDigits: 2 });

                const sumTax = document.getElementById('sumTax');
                if (sumTax) sumTax.textContent = '₱' + d.vat_tax.toLocaleString('en-US', { minimumFractionDigits: 2 });

                const sumShipping = document.getElementById('sumShipping');
                if (sumShipping) sumShipping.textContent = '₱' + d.shipping_cost.toLocaleString('en-US', { minimumFractionDigits: 2 });

                const sumSize = document.getElementById('sumSize');
                if (sumSize) sumSize.textContent = custom_width + '" × ' + custom_height + '"';

                const sumStock = document.getElementById('sumStock');
                if (sumStock) sumStock.textContent = paperText;

                const sumFinish = document.getElementById('sumFinish');
                if (sumFinish) sumFinish.textContent = finishText;

                // Call orderWalletCheck if it exists
                if (typeof orderWalletCheck === 'function') {
                    orderWalletCheck();
                }
            }
        } catch (err) {
            console.error('Pricing error:', err);
        }
    }

    // Keep calcPrice as an alias for backward compatibility
    function calcPrice() {
        updatePricing();
    }

    function handleArtworkSelection(input) {
        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            selectedArtworkFile = file;
            
            document.getElementById('previewFileName').textContent = file.name;
            document.getElementById('previewFileSize').textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            document.getElementById('sumArtwork').textContent = file.name;
            document.getElementById('sumArtwork').style.color = 'var(--success)';
            document.getElementById('upload-preview').style.display = 'block';
            document.getElementById('upload-area').style.borderColor = 'var(--success)';
            document.getElementById('upload-area').style.background = 'rgba(45, 206, 137, 0.05)';
        }
    }

    function removeSelectedArtwork(e) {
        if (e) e.stopPropagation();
        selectedArtworkFile = null;
        document.getElementById('artworkUploadInput').value = '';
        document.getElementById('upload-preview').style.display = 'none';
        document.getElementById('sumArtwork').textContent = 'None';
        document.getElementById('sumArtwork').style.color = 'var(--muted)';
        
        const uploadArea = document.getElementById('upload-area');
        if (uploadArea) {
            uploadArea.style.borderColor = 'var(--border)';
            uploadArea.style.background = 'var(--off)';
        }
    }

    async function placeOrder() {
        const sizeSelect = document.getElementById('sizeSelect');
        const paperSelect = document.getElementById('paperSelect');
        const finishSelect = document.getElementById('finishSelect');
        const sidesSelect = document.getElementById('sidesSelect');
        const qtySlider = document.getElementById('qtySlider');
        const jobNameInput = document.getElementById('jobNameInput');

        if (!sizeSelect || sizeSelect.value === '0') {
            showToast('Please select a size multiplier.');
            return;
        }
        if (!paperSelect || paperSelect.value === '0') {
            showToast('Please select a paper multiplier.');
            return;
        }
        if (!selectedArtworkFile) {
            showToast('Please upload an artwork file to complete Step 4.');
            return;
        }

        const placeBtn = document.getElementById('placeOrderBtn');
        placeBtn.disabled = true;
        placeBtn.textContent = 'Placing Order...';

        const totalCost = currentPricingData.grand_total;

        const formData = new FormData();
        formData.append('product_type', selectedProduct);
        formData.append('job_name', jobNameInput.value.trim());
        formData.append('size_width', sizeSelect.options[sizeSelect.selectedIndex].text.includes('8.5') ? 8.5 : 4.0);
        formData.append('size_height', sizeSelect.options[sizeSelect.selectedIndex].text.includes('11') ? 11.0 : 6.0);
        formData.append('paper_weight', paperSelect.options[paperSelect.selectedIndex].text.split(' (')[0]);
        formData.append('finish', finishSelect.options[finishSelect.selectedIndex].text.split(' (+₱')[0]);
        formData.append('bleed', 'With Bleed');
        formData.append('quantity', qtySlider.value);
        formData.append('turnaround', 'standard');
        formData.append('shipping', 'free');
        formData.append('total_price', totalCost);
        formData.append('artwork_file', selectedArtworkFile);

        try {
            const res = await fetch('../api/place_order.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(`Order Placed! ID: ${data.order_number}`);
                
                // Reset form fields
                jobNameInput.value = '';
                removeSelectedArtwork();
                sizeSelect.selectedIndex = 0;
                paperSelect.selectedIndex = 0;
                finishSelect.selectedIndex = 0;
                sidesSelect.selectedIndex = 0;
                qtySlider.value = 100;
                updateQty(100);
                
                // Refresh data
                loadCredits();
                loadMyOrders();
                
                // Switch to orders page
                showPage('corders');
            } else {
                showToast(data.message || 'Error placing order');
            }
        } catch (e) {
            showToast('Network error while placing order.');
        } finally {
            placeBtn.disabled = false;
            placeBtn.textContent = 'Place Order →';
        }
    }

    // ── CLIENT MY ORDERS HISTORY & PROOF ACTIONS ──────────
    let clientOrders = [];

    async function loadMyOrders() {
      try {
        const res = await fetch('../api/get_orders.php');
        const json = await res.json();
        if (json.success) {
          clientOrders = json.data;
          renderOrdersTable();
          updateDashboardKpis();
        } else {
          console.warn('Failed to load client orders:', json.message);
          updateDashboardKpis(); // still update with 0
        }
      } catch (e) {
        console.warn('Network error loading orders:', e);
        updateDashboardKpis();
      }
    }

    function updateDashboardKpis() {
      const activeStatuses = ['Prepress','Printing','Finishing','Shipping','Proof Pending','Proof Pending Review'];
      const activeOrders = clientOrders.filter(o => activeStatuses.includes(o.status));
      const proofPending = clientOrders.filter(o => o.status === 'Proof Pending Review' && o.proof_file);
      const inShipping  = clientOrders.filter(o => o.status === 'Shipping');

      // Active Orders KPI
      const kpiActive = document.getElementById('kpiActiveOrders');
      if (kpiActive) kpiActive.textContent = activeOrders.length;

      // Hero banner subtitle
      const subtitle = document.getElementById('heroBannerSubtitle');
      if (subtitle) {
        if (clientOrders.length === 0) {
          subtitle.textContent = 'You have no orders yet. Start your first project by clicking the button below!';
        } else {
          const parts = [];
          if (proofPending.length > 0) parts.push(`${proofPending.length} proof${proofPending.length > 1 ? 's' : ''} ready for review`);
          if (inShipping.length > 0)  parts.push(`${inShipping.length} active shipment${inShipping.length > 1 ? 's' : ''}`);
          if (activeOrders.length > 0 && parts.length === 0) parts.push(`${activeOrders.length} order${activeOrders.length > 1 ? 's' : ''} in progress`);
          subtitle.textContent = parts.length > 0 ? 'You have ' + parts.join(' and ') + '.' : 'All caught up! Place a new order anytime.';
        }
      }

      // Load file count separately
      loadFileCount();

      // Render recent orders on dashboard (last 5)
      renderDashboardRecentOrders();
    }

    function renderDashboardRecentOrders() {
      const tbody = document.getElementById('dashRecentOrdersTbody');
      if (!tbody) return;

      const recent = clientOrders.slice(0, 5);

      if (recent.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">
              <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
              No orders yet. Place your first order!
            </td>
          </tr>`;
        return;
      }

      tbody.innerHTML = recent.map(o => {
        const orderNum = o.order_number || ('PPR-' + String(o.id).padStart(3, '0'));
        const jobName  = o.job_name || (o.product_type + ' Project');
        const total    = parseFloat(o.total_price || o.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const dateStr  = o.due_date ? new Date(o.due_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}) : '—';

        let badgeClass = 'b-active';
        if (['Proof Pending','Proof Pending Review'].includes(o.status)) badgeClass = 'b-pending';
        if (['Delivered','Done'].includes(o.status)) badgeClass = 'b-done';
        if (o.status === 'Reprint') badgeClass = 'b-pending';

        return `
          <tr>
            <td style="font-weight:700;">#${orderNum}</td>
            <td style="font-weight:500;">${escapeHtml(jobName)}</td>
            <td>${parseInt(o.quantity).toLocaleString()}</td>
            <td><span class="badge ${badgeClass}">${o.status}</span></td>
            <td>${dateStr}</td>
            <td style="font-weight:700;">₱${total}</td>
          </tr>`;
      }).join('');
    }

    async function loadFileCount() {
      try {
        const res = await fetch('../api/user_files.php');
        const json = await res.json();
        const kpiFiles = document.getElementById('kpiFiles');
        if (kpiFiles) {
          const count = (json.success && Array.isArray(json.data)) ? json.data.length : 0;
          kpiFiles.textContent = count;
        }
      } catch (e) {
        const kpiFiles = document.getElementById('kpiFiles');
        if (kpiFiles) kpiFiles.textContent = '0';
      }
    }

    function renderOrdersTable() {
      const tbody = document.getElementById('ordersTbody');
      if (!tbody) return;

      const filterSelect = document.querySelector('[onchange="filterOrdersBySelect(this)"]');
      const filter = filterSelect ? filterSelect.value : 'All Status';

      let filtered = clientOrders;
      if (filter === 'Active') {
        filtered = clientOrders.filter(o => ['Proof Pending', 'Proof Pending Review', 'Prepress', 'Printing', 'Finishing', 'Shipping'].includes(o.status));
      } else if (filter === 'Pending') {
        filtered = clientOrders.filter(o => ['Proof Pending', 'Proof Pending Review'].includes(o.status));
      } else if (filter === 'Done' || filter === 'Delivered') {
        filtered = clientOrders.filter(o => o.status === 'Delivered' || o.status === 'Reprint' || o.status === 'Done');
      }

      if (filtered.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">
              <i class="bi bi-info-circle" style="font-size:2rem;display:block;margin-bottom:12px;"></i>
              No orders found matching this status.
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = filtered.map(o => {
        const orderNum = o.order_number || ('PPR-' + String(o.id).padStart(3, '0'));
        const jobName = o.job_name || (o.product_type + ' Project');
        const formattedTotal = parseFloat(o.total_price || o.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        let badgeClass = 'b-active';
        if (o.status === 'Proof Pending') badgeClass = 'b-pending';
        if (o.status === 'Proof Pending Review') badgeClass = 'b-pending';
        if (o.status === 'Prepress') badgeClass = 'b-active';
        if (o.status === 'Printing') badgeClass = 'b-active';
        if (o.status === 'Finishing') badgeClass = 'b-active';
        if (o.status === 'Shipping') badgeClass = 'b-active';
        if (o.status === 'Delivered' || o.status === 'Done') badgeClass = 'b-done';
        if (o.status === 'Reprint') badgeClass = 'b-pending';

        let actionCell = '—';
        if (o.status === 'Proof Pending Review' && o.proof_file) {
          actionCell = `<button class="btn btn-sm btn-primary" style="padding:4px 10px; font-size:.78rem; border-radius:6px; background:var(--accent); color:#fff; border:none; display:inline-flex; align-items:center; gap:4px; font-weight:600;" onclick="openClientProofModal(${o.id})"><i class="bi bi-file-earmark-check"></i> Review Proof</button>`;
        }

        const dateStr = o.due_date ? new Date(o.due_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) : '—';

        return `
          <tr data-status="${o.status.toLowerCase()}">
            <td style="font-weight:700;">#${orderNum}</td>
            <td style="font-weight:500;">${escapeHtml(jobName)}</td>
            <td>${o.product_type}</td>
            <td>${parseInt(o.quantity).toLocaleString()}</td>
            <td><span class="badge ${badgeClass}">${o.status}</span></td>
            <td>${dateStr}</td>
            <td style="font-weight:700; color:var(--navy);">₱${formattedTotal}</td>
            <td>${actionCell}</td>
          </tr>
        `;
      }).join('');
    }

    function filterOrdersBySelect(select) {
      renderOrdersTable();
    }

    function escapeHtml(text) {
      if (!text) return '';
      return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function openClientProofModal(orderId) {
      const order = clientOrders.find(o => parseInt(o.id) === parseInt(orderId));
      if (!order) return;

      const modal = document.getElementById('proofModal');
      
      document.getElementById('proofOrderNum').textContent = order.order_number || ('PPR-' + String(order.id).padStart(3, '0'));
      document.getElementById('proofJobName').textContent = order.job_name || (order.product_type + ' Project');
      document.getElementById('proofSpecs').textContent = `${order.product_type} (${parseInt(order.quantity).toLocaleString()} units)`;
      document.getElementById('proofTurnaround').textContent = order.turnaround || 'Standard';
      
      const artworkFileName = order.artwork_file ? order.artwork_file.split('/').pop() : 'artwork.pdf';
      document.getElementById('proofOriginalFileName').textContent = artworkFileName;
      
      const originalPath = order.artwork_file ? '../' + order.artwork_file.replace(/^\.\.\//, '') : '#';
      document.getElementById('proofOriginalDownload').href = originalPath;
      
      const proofFileName = order.proof_file ? order.proof_file.split('/').pop() : 'proof.pdf';
      document.getElementById('proofAdminFileName').textContent = proofFileName;
      
      const proofPath = order.proof_file ? '../' + order.proof_file.replace(/^\.\.\//, '') : '#';
      document.getElementById('proofAdminDownload').href = proofPath;
      
      document.getElementById('proofOrderIdVal').value = order.id;
      
      modal.style.display = 'flex';
      setTimeout(() => {
        modal.style.opacity = '1';
        document.getElementById('proofModalContent').style.transform = 'scale(1)';
      }, 10);
    }

    function closeProofModal() {
      const modal = document.getElementById('proofModal');
      modal.style.opacity = '0';
      document.getElementById('proofModalContent').style.transform = 'scale(0.9)';
      setTimeout(() => {
        modal.style.display = 'none';
      }, 200);
    }

    async function submitProofReview(action) {
      const orderId = document.getElementById('proofOrderIdVal').value;
      if (!orderId) return;
      
      const confirmation = action === 'approve' 
        ? 'Are you sure you want to APPROVE this proof? The order will move directly to Prepress and production.' 
        : 'Are you sure you want to REQUEST REVISIONS on this proof? The order status will revert to Proof Pending.';
      
      if (!confirm(confirmation)) return;
      
      const formData = new FormData();
      formData.append('order_id', orderId);
      formData.append('action', action);
      
      try {
        const res = await fetch('../api/review_proof.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          showToast(action === 'approve' ? 'Proof approved! Status updated to Prepress.' : 'Revision requested successfully.');
          closeProofModal();
          loadCredits();
          loadMyOrders();
        } else {
          showToast(data.message || 'Error processing proof review');
        }
      } catch (e) {
        showToast('Network error during proof review.');
      }
    }

    // Local initialization
    document.addEventListener('DOMContentLoaded', () => {
      currentUser = { name: '<?php echo $userName; ?>', role: '<?php echo $userRole; ?>', email: '<?php echo $userEmail; ?>' };
      setupUI();
      loadCredits();
      loadOrderSpecs();
      loadMyOrders();
      setInterval(loadCredits, 30000);

      // Add debounced event listeners for input fields to prevent excessive calls
      const jobNameInput = document.getElementById('jobNameInput');
      if (jobNameInput) {
        const debouncedUpdateJobName = debounce((val) => updateSummaryJobName(val), 300);
        jobNameInput.addEventListener('input', (e) => debouncedUpdateJobName(e.target.value));
      }

      const qtySlider = document.getElementById('qtySlider');
      const qtyDisplay = document.getElementById('qtyDisplay');

      if (qtySlider && qtyDisplay) {
        const debouncedUpdatePricing = debounce(() => updatePricing(), 300);

        // Slider change updates input
        qtySlider.addEventListener('input', (e) => {
          qtyDisplay.value = e.target.value;
          document.getElementById('sumQty').textContent = e.target.value + ' units';
          debouncedUpdatePricing();
        });

        // Input change updates slider
        qtyDisplay.addEventListener('input', (e) => {
          let val = parseInt(e.target.value) || 100;
          val = Math.max(100, Math.min(10000, val));
          qtySlider.value = val;
          document.getElementById('sumQty').textContent = val + ' units';
          debouncedUpdatePricing();
        });

        // Ensure input is within bounds on blur
        qtyDisplay.addEventListener('blur', (e) => {
          let val = parseInt(e.target.value) || 100;
          val = Math.max(100, Math.min(10000, val));
          qtyDisplay.value = val;
          qtySlider.value = val;
        });
      }

      // Setup upload area drag and drop listeners
      const uploadArea = document.getElementById('upload-area');
      if (uploadArea) {
          uploadArea.addEventListener('dragover', (e) => {
              e.preventDefault();
              uploadArea.style.borderColor = 'var(--accent)';
              uploadArea.style.background = 'rgba(29, 140, 248, 0.05)';
          });
          uploadArea.addEventListener('dragleave', () => {
              if (selectedArtworkFile) {
                  uploadArea.style.borderColor = 'var(--success)';
                  uploadArea.style.background = 'rgba(45, 206, 137, 0.05)';
              } else {
                  uploadArea.style.borderColor = 'var(--border)';
                  uploadArea.style.background = 'var(--off)';
              }
          });
          uploadArea.addEventListener('drop', (e) => {
              e.preventDefault();
              const files = e.dataTransfer.files;
              if (files && files.length > 0) {
                  const fileInput = document.getElementById('artworkUploadInput');
                  if (fileInput) {
                      fileInput.files = files;
                      handleArtworkSelection(fileInput);
                  }
              }
          });
      }
    });
  </script>
</body>

</html>