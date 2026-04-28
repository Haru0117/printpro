<?php
session_start();
// Basic role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
  header('Location: ../login.html');
  exit;
}
$userName = $_SESSION['name'] ?? 'Client';
$userEmail = $_SESSION['email'] ?? 'client@example.com';
$userRole = $_SESSION['role'] ?? 'client';
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
        <span class="logo-text">PrintPro</span>
        <span class="role-badge role-client" id="topRoleBadge">Client</span>
        <span style="font-size:.78rem;color:var(--muted);" id="topPageTitle">Dashboard</span>
      </div>
      <div class="topbar-right">
        <i class="bi bi-bell" style="color:var(--muted);font-size:1.05rem;cursor:pointer;"></i>
        <div class="avatar" id="topAvatar" style="background:linear-gradient(135deg,#7c4dff,#1d8cf8);cursor:pointer;"
          onclick="showPage('account')"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
        <button class="btn btn-outline btn-sm" onclick="location.href='../api/logout.php'"><i
            class="bi bi-box-arrow-right"></i> Logout</button>
      </div>
    </div>

    <div class="app-body">

      <!-- SIDEBAR -->
      <div class="sidebar" id="sidebarEl">
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
                <div class="kpi-val">₱14k</div>
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
    // Local initialization
    document.addEventListener('DOMContentLoaded', () => {
      currentUser = { name: '<?php echo $userName; ?>', role: '<?php echo $userRole; ?>', email: '<?php echo $userEmail; ?>' };
      setupUI();
    });
  </script>
</body>

</html>