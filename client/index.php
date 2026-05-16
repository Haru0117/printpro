<?php
session_start();
// Basic role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
  header('Location: ../index.html#login');
  exit;
}
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
        <div style="padding: 24px 24px 18px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255, 255, 255, .08);">
          <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255, 255, 255, .15); display: grid; place-items: center; color: #fff; font-size: 1.2rem;">P</div>
          <div>
            <div style="font-family: 'Sora', sans-serif; font-weight: 700; font-size: .95rem; color: #fff;">PrintPro</div>
            <div style="font-size: .6rem; color: rgba(255, 255, 255, .4); letter-spacing: .05em;">BUSINESS SOLUTIONS</div>
          </div>
          <div style="font-size: .65rem; background: #1d8cf8; color: #fff; padding: 2px 9px; border-radius: 6px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px; flex-shrink: 0;">APP</div>
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
            <h2 style="font-family:'Sora',sans-serif;font-weight:800;margin-bottom:8px;" id="heroGreeting">Welcome back!
            </h2>
            <p style="color:rgba(255,255,255,.7);margin-bottom:16px;">You have 3 orders ready for review and 2 active
              shipments.</p>
            <button class="btn btn-primary" onclick="showPage('create')">+ New Project →</button>
          </div>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
            <div class="kpi-card" onclick="showPage('corders')">
              <div class="kpi-icon" style="background:rgba(29,140,248,.1);color:var(--accent);"><i
                  class="bi bi-box-seam"></i></div>
              <div>
                <div class="kpi-lbl">Active Orders</div>
                <div class="kpi-val">12</div>
              </div>
            </div>
            <div class="kpi-card" onclick="showPage('cfiles')">
              <div class="kpi-icon" style="background:rgba(62,198,198,.1);color:var(--teal);"><i
                  class="bi bi-folder2"></i></div>
              <div>
                <div class="kpi-lbl">Files</div>
                <div class="kpi-val">48</div>
              </div>
            </div>
            <div class="kpi-card" onclick="showPage('cbilling')">
              <div class="kpi-icon" style="background:rgba(45,206,137,.1);color:var(--success);"><i
                  class="bi bi-credit-card"></i></div>
              <div>
                <div class="kpi-lbl">Credits</div>
                <div class="kpi-val">₱<?php echo number_format($credit_balance, 0); ?></div>
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
              <div style="font-size:2rem;font-weight:700;color:var(--success);margin-bottom:8px;">
                ₱<?php echo number_format($credit_balance, 2); ?>
              </div>
              <div style="color:var(--muted);font-size:.85rem;">
                Default: ₱10,000.00 | Used for order payments
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
                    <div class="form-row"><label class="form-label">Size</label><select class="form-ctrl"
                        id="sizeSelect" onchange="calcPrice()">
                        <option value="0">Select Size</option>
                        <option value="2">A4 (+₱2.00)</option>
                        <option value="1">4x6 (+₱1.00)</option>
                      </select></div>
                    <div class="form-row"><label class="form-label">Paper</label><select class="form-ctrl"
                        id="paperSelect" onchange="calcPrice()">
                        <option value="0">Select Paper</option>
                        <option value="0.05">Standard (+₱0.05)</option>
                      </select></div>
                    <div class="form-row"><label class="form-label">Finish</label><select class="form-ctrl"
                        id="finishSelect" onchange="calcPrice()">
                        <option value="0">None</option>
                        <option value="0.03">Gloss (+₱0.03)</option>
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
                  <input type="range" class="qty-slider" id="qtySlider" min="100" max="10000" step="100" value="100"
                    oninput="updateQty(this.value)">
                  <div id="qtyDisplay">100</div>
                </div>
              </div>
            </div>
            <div>
              <div class="summary-card">
                <div class="sum-title">PRICE PREVIEW</div>
                <div class="sum-row"><span>Product</span><span id="sumProduct">Flyers</span></div>
                <div class="sum-row"><span>Quantity</span><span id="sumQty">100 units</span></div>
                <hr class="sum-divider">
                <div class="sum-total"><span class="sum-total-lbl">Total</span><span class="sum-total-val"
                    id="sumTotal">₱—</span></div>
                <button class="btn-place" onclick="placeOrder()">Place Order →</button>
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
                </tr>
              </thead>
              <tbody>
                <tr data-status="active">
                  <td style="font-weight:700;">#PPR-48</td>
                  <td>3D Marketing Brochure</td>
                  <td>Brochure</td>
                  <td>1,000</td>
                  <td><span class="badge b-active">Active</span></td>
                  <td>Oct 24</td>
                  <td style="font-weight:700;">₱4,250</td>
                </tr>
                <tr data-status="done">
                  <td style="font-weight:700;">#PPR-47</td>
                  <td>Holiday Flyer Pack</td>
                  <td>Flyer</td>
                  <td>5,000</td>
                  <td><span class="badge b-done">Done</span></td>
                  <td>Oct 18</td>
                  <td style="font-weight:700;">₱2,450</td>
                </tr>
                <tr data-status="pending">
                  <td style="font-weight:700;">#PPR-45</td>
                  <td>Corporate Annual Report</td>
                  <td>Booklet</td>
                  <td>250</td>
                  <td><span class="badge b-pending">Reprint</span></td>
                  <td>—</td>
                  <td style="font-weight:700;">₱12,800</td>
                </tr>
                <tr data-status="delivered">
                  <td style="font-weight:700;">#PPR-43</td>
                  <td>Conference Banner Set</td>
                  <td>Banner</td>
                  <td>1,200</td>
                  <td><span class="badge b-done">Delivered</span></td>
                  <td>Oct 21</td>
                  <td style="font-weight:700;">₱15,500</td>
                </tr>
                <tr data-status="active">
                  <td style="font-weight:700;">#PPR-41</td>
                  <td>Promotional Mailers</td>
                  <td>Mailer</td>
                  <td>5,000</td>
                  <td><span class="badge b-active">Active</span></td>
                  <td>Nov 2</td>
                  <td style="font-weight:700;">₱8,750</td>
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

  <script src="../assets/js/printpro.js"></script>
  <script>
    // Load credits data
    function loadCredits() {
      fetch('../api/get_credits.php')
        .then(res => res.json())
        .then(data => {
          console.log('Credits API response:', data);
          if (data.success) {
            // Update topbar credits
            const topbarCredits = document.getElementById('topbarCredits');
            if (topbarCredits) {
              const balanceNum = parseFloat(data.balance_raw);
              const displayValue = balanceNum >= 1000 ? (balanceNum / 1000).toFixed(1) + 'k' : balanceNum.toFixed(2);
              topbarCredits.textContent = '₱' + displayValue;
              console.log('Updated topbar credits to:', topbarCredits.textContent);
            }

            // Update KPI card
            const creditKpi = document.querySelector('[onclick="showPage(\'cbilling\')"] .kpi-val');
            if (creditKpi) {
              creditKpi.textContent = '₱' + (parseFloat(data.balance_raw) / 1000).toFixed(1) + 'k';
            }

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

    // Local initialization
    document.addEventListener('DOMContentLoaded', () => {
      currentUser = { name: '<?php echo $userName; ?>', role: '<?php echo $userRole; ?>', email: '<?php echo $userEmail; ?>' };
      setupUI();
      loadCredits(); // Load credits on page load
      setInterval(loadCredits, 30000); // Refresh credits every 30 seconds
    });
  </script>
</body>

</html>