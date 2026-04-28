<?php
session_start();
// Basic role check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'operator')) {
    header('Location: ../login.html');
    exit;
}
$userName = $_SESSION['name'] ?? 'Admin';
$userEmail = $_SESSION['email'] ?? 'admin@printpro.com';
$userRole = $_SESSION['role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintPro Admin — Control Center</title>
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
                <span class="role-badge role-admin" id="topRoleBadge"><?php echo ucfirst($userRole); ?></span>
                <span style="font-size:.78rem;color:var(--muted);" id="topPageTitle">Dashboard</span>
            </div>
            <div class="topbar-right">
                <i class="bi bi-bell" style="color:var(--muted);font-size:1.05rem;cursor:pointer;"></i>
                <div class="avatar" id="topAvatar"
                    style="background:linear-gradient(135deg,#7c4dff,#1d8cf8);cursor:pointer;"
                    onclick="showPage('account')"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                <button class="btn btn-outline btn-sm" onclick="location.href='../api/logout.php'"><i
                        class="bi bi-box-arrow-right"></i> Logout</button>
            </div>
        </div>

        <div class="app-body">

            <!-- SIDEBAR -->
            <div class="sidebar" id="sidebarEl">
                <div id="adminNav">
                    <div class="nav-section">
                        <div class="nav-label">Overview</div>
                        <div class="nav-item active" onclick="showPage('dashboard',this)"><i
                                class="bi bi-speedometer2"></i> Dashboard</div>
                        <div class="nav-item" onclick="showPage('orders',this)"><i class="bi bi-box-seam"></i> All
                            Orders</div>
                    </div>
                    <div class="nav-section">
                        <div class="nav-label">Management</div>
                        <div class="nav-item" onclick="showPage('specs',this)"><i class="bi bi-sliders"></i> Print Specs
                        </div>
                        <div class="nav-item" onclick="showPage('users',this)"><i class="bi bi-people"></i> Users</div>
                        <div class="nav-item" onclick="showPage('devices',this)"><i class="bi bi-printer"></i> Devices
                        </div>
                        <div class="nav-item" onclick="showPage('subscriptions',this)"><i
                                class="bi bi-card-checklist"></i> Subscriptions</div>
                    </div>
                    <div class="nav-section">
                        <div class="nav-label">My Account</div>
                        <div class="nav-item" onclick="showPage('account',this)"><i class="bi bi-person-circle"></i>
                            Account</div>
                        <div class="nav-item" onclick="showPage('settings',this)"><i class="bi bi-gear"></i> Settings
                        </div>
                    </div>
                </div>
                <div class="sidebar-logout">
                    <a href="../api/logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
                </div>
            </div>

            <!-- CONTENT AREA -->
            <div class="content">

                <!-- ════ ADMIN: DASHBOARD ════ -->
                <div class="page active" id="page-dashboard">
                    <div class="page-hdr">
                        <h4>Dashboard</h4>
                        <p>Here's what's happening today at PrintPro</p>
                    </div>
                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-icon" style="background:rgba(29,140,248,.1);color:var(--accent);"><i
                                    class="bi bi-graph-up"></i></div>
                            <div>
                                <div class="kpi-lbl">Total Revenue</div>
                                <div class="kpi-val">₱2.4M</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon" style="background:rgba(45,206,137,.1);color:var(--success);"><i
                                    class="bi bi-check2-circle"></i></div>
                            <div>
                                <div class="kpi-lbl">Completed</div>
                                <div class="kpi-val">428</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon" style="background:rgba(251,99,64,.1);color:var(--warning);"><i
                                    class="bi bi-hourglass-split"></i></div>
                            <div>
                                <div class="kpi-lbl">Pending</div>
                                <div class="kpi-val">14</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon" style="background:rgba(124,77,255,.1);color:var(--purple);"><i
                                    class="bi bi-people"></i></div>
                            <div>
                                <div class="kpi-lbl">Active Clients</div>
                                <div class="kpi-val">87</div>
                            </div>
                        </div>
                    </div>
                    <div class="charts-row">
                        <div class="chart-card">
                            <div class="chart-title">Print Category Distribution</div>
                            <div class="chart-sub">Most popular print categories this month</div>
                            <canvas id="categoryChart" height="200"></canvas>
                        </div>
                        <div class="chart-card">
                            <div class="chart-title">Order Status Overview</div>
                            <div class="chart-sub">Current production pipeline</div>
                            <canvas id="statusChart" height="200"></canvas>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:14px;">
                        <div class="card">
                            <div class="card-hdr">
                                <span class="card-title">Recent Orders</span>
                                <button class="btn btn-outline btn-sm" onclick="showPage('orders')">View All →</button>
                            </div>
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Client</th>
                                        <th>Product</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="font-weight:700;">#PPR-048</td>
                                        <td>Gamma Agency</td>
                                        <td>Brochure</td>
                                        <td><span class="badge b-active">Active</span></td>
                                        <td style="font-weight:700;">₱4,250</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:700;">#PPR-045</td>
                                        <td>Urban Core</td>
                                        <td>Mailer</td>
                                        <td><span class="badge b-reprint">Reprint</span></td>
                                        <td style="font-weight:700;">₱12,800</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:700;">#PPR-043</td>
                                        <td>TechStart Inc</td>
                                        <td>Poster</td>
                                        <td><span class="badge b-done">Printing</span></td>
                                        <td style="font-weight:700;">₱15,500</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:700;">#PPR-038</td>
                                        <td>Citybeats</td>
                                        <td>Banner</td>
                                        <td><span class="badge b-done">Done</span></td>
                                        <td style="font-weight:700;">₱2,450</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card">
                            <div class="card-hdr"><span class="card-title">Admin Commands</span></div>
                            <div style="padding:16px;display:flex;flex-direction:column;gap:10px;">
                                <button class="btn btn-primary"
                                    style="width:100%;text-align:left;display:flex;align-items:center;gap:10px;padding:12px;"
                                    onclick="showPage('specs')">
                                    <i class="bi bi-sliders fs-5"></i>
                                    <div>
                                        <div style="font-weight:700;">Print Spec Management</div>
                                        <div style="font-size:.7rem;opacity:.7;">Manage materials & pricing</div>
                                    </div>
                                </button>
                                <button class="btn btn-outline"
                                    style="width:100%;text-align:left;display:flex;align-items:center;gap:10px;padding:12px;"
                                    onclick="window.print()">
                                    <i class="bi bi-printer fs-5"></i>
                                    <div>
                                        <div style="font-weight:700;">Export Production Report</div>
                                        <div style="font-size:.7rem;opacity:.7;">Print today's job summary</div>
                                    </div>
                                </button>
                                <button class="btn btn-outline"
                                    style="width:100%;text-align:left;display:flex;align-items:center;gap:10px;padding:12px;"
                                    onclick="showPage('orders')">
                                    <i class="bi bi-ticket-perforated fs-5"></i>
                                    <div>
                                        <div style="font-weight:700;">Generate Job Tickets</div>
                                        <div style="font-size:.7rem;opacity:.7;">Create printable floor tickets</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ ADMIN: ALL ORDERS ════ -->
                <div class="page" id="page-orders">
                    <div class="page-hdr" style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div>
                            <h4>All Orders</h4>
                            <p>Manage and track all print jobs</p>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-outline btn-sm"><i class="bi bi-download"></i> Export</button>
                            <button class="btn btn-primary btn-sm" onclick="openTicketModal(null)"><i
                                    class="bi bi-plus-lg"></i> New Order</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hdr">
                            <div class="filter-bar">
                                <button class="filter-btn active" onclick="filterOrders(this,'all')">All</button>
                                <button class="filter-btn" onclick="filterOrders(this,'active')">Active</button>
                                <button class="filter-btn" onclick="filterOrders(this,'printing')">Printing</button>
                                <button class="filter-btn" onclick="filterOrders(this,'done')">Done</button>
                                <button class="filter-btn" onclick="filterOrders(this,'reprint')">Reprint</button>
                            </div>
                            <div
                                style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:8px;padding:5px 12px;">
                                <i class="bi bi-search" style="font-size:.75rem;color:var(--muted);"></i>
                                <input type="text" placeholder="Search orders…"
                                    style="border:none;outline:none;font-size:.8rem;font-family:'DM Sans',sans-serif;width:180px;"
                                    oninput="searchOrders(this.value)">
                            </div>
                        </div>
                        <table class="tbl" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Client</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTbody">
                                <tr data-status="active">
                                    <td style="font-weight:700;">#PPR-048</td>
                                    <td>Gamma Agency</td>
                                    <td>Brochure</td>
                                    <td>1,000</td>
                                    <td>
                                        <div class="progress-wrap">
                                            <div class="progress-fill" style="width:65%;background:var(--accent);">
                                            </div>
                                        </div>
                                    </td>
                                    <td><select class="status-select" onchange="updateStatus(this,'PPR-048')">
                                            <option>Prepress</option>
                                            <option>Printing</option>
                                            <option>Finishing</option>
                                            <option>Shipping</option>
                                            <option>Delivered</option>
                                        </select></td>
                                    <td>Oct 24</td>
                                    <td>₱4,250</td>
                                    <td><button class="btn btn-outline btn-sm"
                                            onclick="openTicketModal({id:'PPR-048',client:'Gamma Agency',product:'Brochure',qty:'1,000',size:'4×6 in',paper:'100lb Gloss',finish:'Matte UV'})"><i
                                                class="bi bi-ticket-perforated"></i> Ticket</button></td>
                                </tr>
                                <!-- More rows here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ════ ADMIN: PRINT SPEC MANAGEMENT ════ -->
                <div class="page" id="page-specs">
                    <div class="page-hdr" style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div>
                            <h4>Print Specifications</h4>
                            <p>Manage tiered pricing, multipliers, and fees</p>
                        </div>
                        <button class="btn btn-primary" onclick="openSpecModal()"><i class="bi bi-plus-lg"></i> Add
                            New</button>
                    </div>

                    <div class="filter-bar mb-3" id="specTabs">
                        <button class="filter-btn active" onclick="switchSpecTab('base_prices', this)">Base
                            Prices</button>
                        <button class="filter-btn" onclick="switchSpecTab('materials', this)">Materials</button>
                        <button class="filter-btn" onclick="switchSpecTab('sizes', this)">Sizes</button>
                        <button class="filter-btn" onclick="switchSpecTab('finishes', this)">Finishes</button>
                    </div>

                    <div class="card">
                        <div class="card-hdr"><span class="card-title" id="specTableTitle">Base Prices</span></div>
                        <table class="tbl">
                            <thead id="specThead">
                                <tr>
                                    <th>Product</th>
                                    <th>Min Qty</th>
                                    <th>Base Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="specsTbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- ════ ADMIN: USERS ════ -->
                <div class="page" id="page-users">
                    <div class="page-hdr">
                        <h4>User Management</h4>
                        <p>Manage registered accounts</p>
                    </div>
                    <div class="card">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Alexander Wright</td>
                                    <td><span class="role-badge role-admin">Admin</span></td>
                                    <td><span class="badge b-active">Active</span></td>
                                    <td>Jan 12</td>
                                    <td><button class="btn btn-outline btn-sm"><i class="bi bi-pencil"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ════ ADMIN: DEVICES ════ -->
                <div class="page" id="page-devices">
                    <div class="page-hdr">
                        <h4>Devices</h4>
                        <p>Monitor connected print machines</p>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
                        <div class="card" style="padding:18px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                                <div
                                    style="width:44px;height:44px;background:rgba(29,140,248,.1);color:var(--accent);border-radius:10px;display:grid;place-items:center;">
                                    <i class="bi bi-printer-fill"></i>
                                </div>
                                <span class="badge b-active">Online</span>
                            </div>
                            <div style="font-weight:700;">Heidelberg Speedmaster</div>
                            <div style="font-size:.72rem;color:var(--muted);">Unit #01</div>
                        </div>
                    </div>
                </div>

                <!-- ════ ACCOUNT & SETTINGS ════ -->
                <div class="page" id="page-account">
                    <div class="account-header">
                        <div class="acc-avatar" id="accAvatarBig"><?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                        <div>
                            <div class="acc-name"><?php echo $userName; ?></div>
                            <div class="acc-email"><?php echo $userEmail; ?></div><span
                                class="acc-badge"><?php echo ucfirst($userRole); ?></span>
                        </div>
                    </div>
                </div>

                <div class="page" id="page-settings">
                    <div class="page-hdr">
                        <h4>Settings</h4>
                        <p>Configure system preferences</p>
                    </div>
                    <div class="settings-layout">
                        <div class="settings-nav">
                            <div class="settings-nav-item active" onclick="switchSettings('general',this)">General</div>
                            <div class="settings-nav-item" onclick="switchSettings('security',this)">Security</div>
                        </div>
                        <div class="settings-section active" id="settings-general">
                            <div class="settings-card">
                                <div class="settings-hdr">
                                    <h5>Business Information</h5>
                                </div>
                                <div class="settings-body">...</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ════ MODALS ════ -->
    <div class="modal-backdrop" id="specModal">
        <div class="modal-box">
            <div class="modal-head">
                <h5>Manage Specification</h5><button class="modal-close" onclick="closeSpecModal()"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="specEditId">

                <!-- Base Price Fields -->
                <div id="fields-base_prices">
                    <div class="form-row"><label class="form-label">Product</label>
                        <select class="form-ctrl" id="specProductId">
                            <option value="1">Flyers</option>
                            <option value="2">Brochures</option>
                            <option value="3">Booklets</option>
                            <option value="4">Cards</option>
                            <option value="5">Posters</option>
                            <option value="6">Mailers</option>
                        </select>
                    </div>
                    <div class="form-row"><label class="form-label">Min Quantity</label><input type="number"
                            class="form-ctrl" id="specMinQty"></div>
                    <div class="form-row"><label class="form-label">Base Price (₱)</label><input type="number"
                            class="form-ctrl" id="specBasePrice"></div>
                </div>

                <!-- Material / Size Fields -->
                <div id="fields-common" style="display:none;">
                    <div class="form-row"><label class="form-label">Name</label><input type="text" class="form-ctrl"
                            id="specName"></div>
                    <div class="form-row"><label class="form-label">Multiplier (e.g. 1.25)</label><input type="number"
                            class="form-ctrl" id="specMultiplier" step="0.01"></div>
                </div>

                <!-- Finish Fields -->
                <div id="fields-finishes" style="display:none;">
                    <div class="form-row"><label class="form-label">Finish Name</label><input type="text"
                            class="form-ctrl" id="specFinishName"></div>
                    <div class="form-row"><label class="form-label">Setup Fee (₱)</label><input type="number"
                            class="form-ctrl" id="specSetupFee"></div>
                    <div class="form-row"><label class="form-label">Per Unit Fee (₱)</label><input type="number"
                            class="form-ctrl" id="specUnitFee" step="0.01"></div>
                </div>

                <button class="btn btn-primary mt-3" style="width:100%;" onclick="saveSpec()">Save
                    Specification</button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="ticketModal">
        <div class="modal-box" style="max-width:700px;">
            <div class="modal-head">
                <h5>Job Ticket</h5>
                <div style="display:flex;gap:8px;"><button class="btn btn-primary btn-sm"
                        onclick="printTicket()">Print</button><button class="modal-close"
                        onclick="closeTicketModal()"><i class="bi bi-x-lg"></i></button></div>
            </div>
            <div class="modal-body" id="ticketContent">
                <div class="ticket-wrap" id="ticketPrintArea">
                    <div class="ticket-header">
                        <div class="ticket-id" id="ticketIdBig">#PPR-000</div>
                    </div>
                    <div class="ticket-section"><strong>Product:</strong> <span id="tProduct"></span></div>
                    <div class="barcode-text" id="ticketBarcode">*PPR-000*</div>
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
            initCharts();
            renderSpecs();
        });
    </script>
</body>

</html>