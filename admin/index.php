<?php
// â”€â”€ AUTH GUARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
session_start();

// Not logged in → login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html?action=login');
    exit;
}

// Clients hitting this page → bounce to client dashboard
$_role = strtolower($_SESSION['role'] ?? '');
if ($_role === 'client') {
    header('Location: ../client/');
    exit;
}

// Only admin / super_admin allowed past here
if ($_role !== 'admin' && $_role !== 'super_admin') {
    header('Location: ../index.html?action=login');
    exit;
}

$userName = htmlspecialchars($_SESSION['name'] ?? 'Admin');
$userEmail = htmlspecialchars($_SESSION['email'] ?? '');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintPro Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon.png">
    <style>
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

        /* ── GLOBAL DARK MODE OVERRIDES ── */
        html[data-theme="dark"] body {
            background: var(--dark-bg) !important;
            color: var(--navy) !important;
        }

        html[data-theme="dark"] .a-sidebar,
        html[data-theme="dark"] .a-topbar,
        html[data-theme="dark"] .card,
        html[data-theme="dark"] .kpi-card,
        html[data-theme="dark"] .chart-card,
        html[data-theme="dark"] .orders-card,
        html[data-theme="dark"] .notif-card,
        html[data-theme="dark"] .actions-card,
        html[data-theme="dark"] .users-card {
            background: var(--dark-card) !important;
            border-color: var(--border) !important;
            color: var(--navy) !important;
        }

        html[data-theme="dark"] .a-content {
            background: var(--dark-bg) !important;
        }

        html[data-theme="dark"] .a-nav-item:hover,
        html[data-theme="dark"] .a-tab:hover {
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
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea {
            background: var(--dark-card2) !important;
            border-color: var(--border) !important;
            color: #fff !important;
        }

        html,
        body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: #f0f2f8;
            transition: background 0.3s ease;
        }

        [data-theme="dark"] body {
            background: var(--dark-bg);
            color: #fff;
        }

        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6 {
            color: #fff;
        }

        [data-theme="dark"] .text-muted {
            color: #a0aec0 !important;
        }

        /* ── APP SHELL ── */
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
            border-top: 1px solid var(--border);
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

        .a-page {
            display: none;
        }

        .a-page.active {
            display: block;
        }

        /* ── COMPONENTS ── */
        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            margin-bottom: 24px;
            overflow: hidden;
        }

        [data-theme="dark"] .card {
            background: var(--dark-card);
            border-color: var(--border);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            transition: transform .2s, background-color 0.3s ease, border-color 0.3s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .kpi-val {
            font-family: 'Sora', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
            line-height: 1.2;
        }

        .kpi-lbl {
            font-size: .75rem;
            color: var(--muted);
            margin-top: 1px;
            font-weight: 500;
        }

        .table-responsive {
            border-radius: 12px;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            padding: 8px 14px;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: .68rem;
            letter-spacing: .05em;
            background: var(--off);
            border-bottom: 1px solid var(--border);
        }

        .table td {
            padding: 9px 14px;
            border-bottom: 1px solid #f0f2f8;
            color: #344767;
            vertical-align: middle;
            font-size: .78rem;
        }

        .table-dark-themed {
            background: transparent !important;
        }

        [data-theme="dark"] .table {
            --bs-table-bg: transparent;
            --bs-table-color: #cbd5e0;
            --bs-table-border-color: var(--border);
            color: #cbd5e0;
        }

        [data-theme="dark"] .table tr {
            background: transparent !important;
        }

        [data-theme="dark"] .table tr:hover {
            background: rgba(255, 255, 255, 0.02) !important;
        }

        [data-theme="dark"] .table-responsive {
            background: transparent !important;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .02em;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .badge-success {
            background: #2dce89 !important;
            color: #fff !important;
        }

        .badge-warning {
            background: #fb6340 !important;
            color: #fff !important;
        }

        .badge-primary {
            background: #1d8cf8 !important;
            color: #fff !important;
        }

        .badge-danger {
            background: #f5365c !important;
            color: #fff !important;
        }

        [data-theme="dark"] .table td {
            border-color: var(--border);
            color: #cbd5e0;
        }

        [data-theme="dark"] .table th {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        [data-theme="dark"] .bg-white,
        [data-theme="dark"] .bg-light,
        [data-theme="dark"] .bg-white-subtle,
        [data-theme="dark"] .bg-light-subtle {
            background-color: var(--dark-card) !important;
            color: #fff !important;
        }

        [data-theme="dark"] .card-header {
            background-color: var(--dark-card) !important;
            border-bottom: 1px solid var(--border) !important;
            box-shadow: none !important;
        }

        [data-theme="dark"] .nav-tabs {
            border-color: var(--border) !important;
            background-color: transparent !important;
        }

        [data-theme="dark"] .nav-tabs .nav-link {
            color: #a0aec0 !important;
        }

        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: transparent !important;
            color: var(--accent) !important;
            border-bottom: 2px solid var(--accent) !important;
        }

        [data-theme="dark"] .modal-content {
            background-color: var(--dark-card) !important;
            border: 1px solid var(--border) !important;
            color: #fff !important;
        }

        [data-theme="dark"] .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-theme="dark"] .shadow-sm {
            box-shadow: none !important;
        }

        [data-theme="dark"] .nav-link {
            color: #a0aec0 !important;
        }

        [data-theme="dark"] .nav-link:hover {
            color: #fff !important;
        }

        /* Button Refinements */
        .btn {
            white-space: nowrap !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            height: auto !important;
            width: auto !important;
        }

        .btn-sm {
            padding: 8px 16px !important;
            font-size: 0.75rem;
            border-radius: 8px;
        }

        .btn-primary {
            box-shadow: 0 4px 12px rgba(29, 140, 248, 0.2);
        }

        .btn-light {
            background: #f1f3f5;
            border: 1px solid #e9ecef;
            color: #495057;
        }

        .btn-light:hover {
            background: #e9ecef;
        }

        [data-theme="dark"] .btn-light {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            color: #cbd5e0;
        }

        [data-theme="dark"] .btn-light:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .action-btn {
            width: 34px !important;
            height: 34px !important;
            padding: 0 !important;
            flex-shrink: 0;
            border-radius: 8px;
        }

        [data-theme="dark"] .form-control {
            background: var(--dark-card2);
            border-color: var(--border);
            color: #fff;
        }

        [data-theme="dark"] .form-control:focus {
            background: var(--dark-card2);
            border-color: var(--accent);
            color: #fff;
        }

        [data-theme="dark"] .btn-light {
            background: rgba(255, 255, 255, 0.1);
            border: 0;
            color: #fff;
        }

        [data-theme="dark"] .modal-content {
            background: var(--dark-card);
            border-color: var(--border);
            color: #fff;
        }

        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer {
            border-color: var(--border);
        }

        [data-theme="dark"] label,
        [data-theme="dark"] .form-label {
            color: #cbd5e0;
        }

        [data-theme="dark"] .nav-tabs .nav-link:not(.active) {
            color: #a0aec0 !important;
        }

        [data-theme="dark"] .table th {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #fff !important;
        }

        [data-theme="dark"] .table td {
            color: #cbd5e0 !important;
        }

        [data-theme="dark"] ::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        [data-theme="dark"] .text-muted {
            color: #a0aec0 !important;
        }

        [data-theme="dark"] .small {
            color: #a0aec0 !important;
        }

        .btn-sm {
            width: 32px;
            height: 32px;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 8px;
        }

        .btn-sm i {
            font-size: 1rem;
            line-height: 1;
            margin: 0;
        }

        .progress-bar-wrap {
            width: 100%;
            height: 6px;
            background: var(--off);
            border-radius: 3px;
            overflow: hidden;
        }

        [data-theme="dark"] .progress-bar-wrap {
            background: rgba(255, 255, 255, 0.05);
        }

        .progress-bar-fill {
            height: 100%;
            transition: width 0.5s ease;
        }

        .status-select {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: .75rem;
            font-weight: 600;
            border: 1px solid var(--border);
            background: transparent;
            outline: none;
            cursor: pointer;
        }

        [data-theme="dark"] .status-select {
            color: #fff;
            background: var(--dark-card2);
        }

        /* ── LOADING STATES ── */
        .skeleton {
            background: linear-gradient(90deg, #f0f2f8 25%, #e0e6ed 50%, #f0f2f8 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* ── TOAST ── */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 2000;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1200px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .user-mgmt-layout {
                grid-template-columns: 1fr;
            }

            .profile-panel {
                position: static;
            }
        }

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

            .kpi-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .kpi-card {
                padding: 16px;
            }

            .a-brand {
                padding: 16px;
            }

            .btn:not(.action-btn):not(.btn-sm) {
                width: 100%;
                justify-content: center;
            }

            .action-btn {
                width: 34px !important;
            }

            /* Keep small */
            .d-flex.gap-2 {
                flex-wrap: wrap;
            }

            #orderSearch {
                width: 100% !important;
                margin-bottom: 10px;
            }
        }

        /* Custom Toggle switch style to override standard dashboard checkbox display issues */
        .toggle-wrap { position:relative; display:inline-block; width:38px; height:21px; vertical-align:middle; }
        .toggle-wrap input { opacity:0; width:0; height:0; display:inline-block !important; }
        .toggle-slider { position:absolute; inset:0; background:#dde3f0; border-radius:21px; cursor:pointer; transition:.2s; }
        .toggle-slider:before { content:''; position:absolute; width:15px; height:15px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
        input:checked + .toggle-slider { background: #2dce89; }
        input:checked + .toggle-slider:before { transform:translateX(17px); }
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
                <div class="a-nav-item active" onclick="showPage('dashboard')">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </div>
                <div class="a-nav-item" onclick="showPage('orders')">
                    <i class="bi bi-box-seam"></i> Orders
                </div>
                <div class="a-nav-item" onclick="showPage('users')">
                    <i class="bi bi-people"></i> Users
                </div>
                <div class="a-nav-item" onclick="showPage('subscriptions')">
                    <i class="bi bi-card-checklist"></i> Subscriptions
                </div>
                <div class="a-nav-item" onclick="showPage('specs')">
                    <i class="bi bi-sliders"></i> Specifications
                </div>
                <div class="a-nav-item" onclick="showPage('settings')">
                    <i class="bi bi-gear"></i> Settings
                </div>
            </nav>
            <div class="a-logout">
                <a href="#" onclick="handleLogout(event)"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </aside>

        <main class="a-main">
            <!-- Topbar -->
            <header class="a-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none action-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <h5 class="m-0 fw-bold" id="pageTitle">Dashboard</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn border-0 p-0 text-muted fs-5" onclick="toggleTheme()">
                        <i class="bi bi-moon-stars" id="themeIcon"></i>
                    </button>
                    <div class="dropdown">
                        <div class="d-flex align-items-center gap-2 cursor-pointer" data-bs-toggle="dropdown">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                style="width:34px; height:34px; font-size:.85rem; font-weight:700; line-height:1;"
                                id="userAvatar">A</div>
                            <div class="d-none d-sm-block">
                                <div class="fw-bold" style="font-size:.8rem;" id="userName">Admin</div>
                                <div class="text-muted" style="font-size:.7rem;">Admin</div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="a-content">
                <!-- Dashboard Page -->
                <div class="a-page active" id="page-dashboard">
                    <div class="kpi-grid" id="dashboardKpis">
                        <div class="kpi-card">
                            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-currency-dollar"></i>
                            </div>
                            <div>
                                <div class="kpi-lbl">Total Revenue</div>
                                <div class="kpi-val">₱0.00</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <div class="kpi-lbl">Completed</div>
                                <div class="kpi-val">0</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="kpi-lbl">Pending</div>
                                <div class="kpi-val">0</div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-person-plus"></i></div>
                            <div>
                                <div class="kpi-lbl">New Users</div>
                                <div class="kpi-val">0</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h6 class="fw-bold m-0">Revenue Trends</h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle border-0"
                                            type="button" data-bs-toggle="dropdown" id="revenueFilterBtn">
                                            Last 6 Months
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0"
                                            style="border-radius:12px;">
                                            <li><a class="dropdown-item small fw-bold" href="#"
                                                    onclick="updateRevenueChart('week', 'Last Week')">Last Week</a></li>
                                            <li><a class="dropdown-item small fw-bold" href="#"
                                                    onclick="updateRevenueChart('month', 'Last Month')">Last Month</a>
                                            </li>
                                            <li><a class="dropdown-item small fw-bold active" href="#"
                                                    onclick="updateRevenueChart('6months', 'Last 6 Months')">Last 6
                                                    Months</a></li>
                                            <li><a class="dropdown-item small fw-bold" href="#"
                                                    onclick="updateRevenueChart('year', 'Last Year')">Last Year</a></li>
                                            <li><a class="dropdown-item small fw-bold" href="#"
                                                    onclick="updateRevenueChart('all', 'All Time')">All Time</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div style="height: 300px;"><canvas id="revenueChart"></canvas></div>
                            </div>
                            <div class="card">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold m-0">Recent Orders</h6>
                                    <button class="btn btn-sm btn-link text-decoration-none p-0"
                                        onclick="showPage('orders')">View All</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table" id="recentOrdersTable">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Client</th>
                                                <th>Product</th>
                                                <th>Status</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dynamic Content -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card p-4">
                                <h6 class="fw-bold mb-4">Status Distribution</h6>
                                <div style="height: 250px;"><canvas id="statusChart"></canvas></div>
                            </div>
                            <div class="card p-4">
                                <h6 class="fw-bold mb-3">Live Activity</h6>
                                <div id="activityFeed">
                                    <!-- Dynamic Content -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Page -->
                <div class="a-page" id="page-orders">
                    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:16px;">
                        <div class="card-header bg-white border-bottom p-0">
                            <ul class="nav nav-tabs border-0 px-4" id="orderTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active py-3 border-0 fw-bold small text-uppercase"
                                        onclick="filterOrdersByStatus('All')">All Orders</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        onclick="filterOrdersByStatus('Prepress')">Prepress</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        onclick="filterOrdersByStatus('Printing')">Printing</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        onclick="filterOrdersByStatus('Finishing')">Finishing</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        onclick="filterOrdersByStatus('Shipping')">Shipping</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        onclick="filterOrdersByStatus('Delivered')">Delivered</button>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light-subtle">
                            <h6 class="fw-bold m-0" id="orderListTitle">All Orders</h6>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-sm" id="orderSearch"
                                    placeholder="Search orders..." style="width:250px;"
                                    oninput="searchOrders(this.value)">
                                <button class="btn btn-primary btn-sm" onclick="loadOrders()"><i
                                        class="bi bi-arrow-clockwise"></i></button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Client</th>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="a-page" id="page-users">
                    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:16px;">
                        <div class="p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center bg-light-subtle gap-3">
                            <h6 class="fw-bold m-0">User Management</h6>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <select class="form-select form-select-sm" id="userRoleFilter" style="width: 130px;" onchange="filterAndSortUsers()">
                                    <option value="all">All Roles</option>
                                    <option value="client">Client</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <select class="form-select form-select-sm" id="userSortSelect" style="width: 165px;" onchange="filterAndSortUsers()">
                                    <option value="newest">Newest Joined</option>
                                    <option value="oldest">Oldest Joined</option>
                                    <option value="name_asc">Name (A-Z)</option>
                                    <option value="name_desc">Name (Z-A)</option>
                                    <option value="role_admin">Role (Admin first)</option>
                                    <option value="role_client">Role (Client first)</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" id="userSearchInput" placeholder="Search name, email, role..."
                                    style="width:250px;" oninput="filterAndSortUsers()">
                                <button class="btn btn-primary btn-sm" onclick="loadUsers()" title="Refresh"><i
                                        class="bi bi-arrow-clockwise"></i></button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Business</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Subscriptions Page -->
                <div class="a-page" id="page-subscriptions">
                    <div class="card">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0">Client Subscriptions</h6>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-sm" id="subSearch"
                                    placeholder="Search client or business..." style="width:250px;"
                                    oninput="searchSubscriptions(this.value)">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="subsTable">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Email</th>
                                        <th>Business</th>
                                        <th>Plan</th>
                                        <th>Status</th>
                                        <th>Renews On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Specifications Page -->
                <div class="a-page" id="page-specs">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0 text-navy">Print Specifications</h5>
                    </div>
                    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:16px;">
                        <div class="card-header bg-white border-bottom p-0">
                            <ul class="nav nav-tabs border-0 px-4" id="specTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-3 border-0 fw-bold small text-uppercase"
                                        id="spec-materials-tab" data-bs-toggle="tab" data-bs-target="#spec-materials" type="button"
                                        role="tab"><i class="bi bi-layers me-2"></i>Materials</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        id="spec-finishes-tab" data-bs-toggle="tab" data-bs-target="#spec-finishes" type="button"
                                        role="tab"><i class="bi bi-stars me-2"></i>Finishes</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        id="spec-sizes-tab" data-bs-toggle="tab" data-bs-target="#spec-sizes" type="button"
                                        role="tab"><i class="bi bi-aspect-ratio"></i>Sizes</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-4">
                            <div class="tab-content" id="specTabContent">
                                <!-- MATERIALS TAB -->
                                <div class="tab-pane fade show active" id="spec-materials" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold m-0 text-navy"><i class="bi bi-layers text-primary me-2"></i>Paper Materials</h6>
                                        <button class="btn btn-primary btn-sm px-3" onclick="openSpecEditModal('paper')"><i class="bi bi-plus-lg"></i> Add Material</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-hover" id="table-paper">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Multiplier</th>
                                                    <th>Status</th>
                                                    <th>Active</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-hourglass-split me-2"></i>Loading...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- FINISHES TAB -->
                                <div class="tab-pane fade" id="spec-finishes" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold m-0 text-navy"><i class="bi bi-stars text-primary me-2"></i>Finishing Options</h6>
                                        <button class="btn btn-primary btn-sm px-3" onclick="openSpecEditModal('finish')"><i class="bi bi-plus-lg"></i> Add Finish</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-hover" id="table-finish">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Setup Fee</th>
                                                    <th>Per-Unit Fee</th>
                                                    <th>Status</th>
                                                    <th>Active</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-hourglass-split me-2"></i>Loading...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- SIZES TAB -->
                                <div class="tab-pane fade" id="spec-sizes" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold m-0 text-navy"><i class="bi bi-aspect-ratio text-primary me-2"></i>Print Sizes</h6>
                                        <button class="btn btn-primary btn-sm px-3" onclick="openSpecEditModal('size')"><i class="bi bi-plus-lg"></i> Add Size</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-hover" id="table-size">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Multiplier</th>
                                                    <th>Status</th>
                                                    <th>Active</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-hourglass-split me-2"></i>Loading...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Page -->
                <div class="a-page" id="page-settings">
                    <div class="card border-0 shadow-sm overflow-hidden" style="border-radius:16px;">
                        <div class="card-header bg-white border-bottom p-0">
                            <ul class="nav nav-tabs border-0 px-4" id="settingsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-3 border-0 fw-bold small text-uppercase"
                                        id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button"
                                        role="tab">Account</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button"
                                        role="tab">Security</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase"
                                        id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button"
                                        role="tab">Business Profile</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-3 border-0 fw-bold small text-uppercase" id="system-tab"
                                        data-bs-toggle="tab" data-bs-target="#system" type="button"
                                        role="tab">System</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-4">
                            <div class="tab-content" id="settingsTabsContent">
                                <!-- Account Tab -->
                                <div class="tab-pane fade show active" id="account" role="tabpanel">
                                    <form id="adminProfileForm" onsubmit="saveAdminProfile(event)">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Full Name</label>
                                                <input type="text" class="form-control" name="name" id="setAdminName"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Email Address</label>
                                                <input type="email" class="form-control" name="email" id="setAdminEmail"
                                                    required>
                                            </div>
                                            <div class="col-12 mt-4 d-flex flex-column flex-sm-row justify-content-between gap-3">
                                                <button type="submit" class="btn btn-primary px-4 fw-bold">Update Account</button>
                                                <button type="button" class="btn btn-outline-danger px-4 fw-bold"
                                                    onclick="handleLogout(event)">
                                                    <i class="bi bi-box-arrow-left me-2"></i>Logout from Session
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- System Tab -->
                                <div class="tab-pane fade" id="system" role="tabpanel">
                                    <form id="systemSettingsForm" onsubmit="saveSystemSettings(event)">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Site Name</label>
                                                <input type="text" class="form-control" name="site_name"
                                                    value="PrintPro" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Contact Email</label>
                                                <input type="email" class="form-control" name="contact_email"
                                                    value="admin@printpro.ph" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Currency Symbol</label>
                                                <input type="text" class="form-control" name="currency_symbol" value="₱"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Default Theme</label>
                                                <select class="form-select" name="default_theme">
                                                    <option value="light">Light Mode</option>
                                                    <option value="dark">Dark Mode</option>
                                                </select>
                                            </div>
                                            <div class="col-12 mt-4">
                                                <button type="submit" class="btn btn-primary px-4 fw-bold">Save System
                                                    Settings</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- Business Profile Tab -->
                                <div class="tab-pane fade" id="business" role="tabpanel">
                                    <form id="businessSettingsForm" onsubmit="saveBusinessSettings(event)">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label small fw-bold">Company Address</label>
                                                <textarea class="form-control" name="company_address" rows="2"
                                                    placeholder="Street, City, Zip Code"></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Tax ID / TIN</label>
                                                <input type="text" class="form-control" name="tax_id"
                                                    placeholder="000-000-000-000">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Contact Number</label>
                                                <input type="text" class="form-control" name="contact_phone"
                                                    placeholder="+63 900 000 0000">
                                            </div>
                                            <div class="col-12 mt-4">
                                                <button type="submit" class="btn btn-primary px-4 fw-bold">Update
                                                    Profile</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- Security Tab -->
                                <div class="tab-pane fade" id="security" role="tabpanel">
                                    <form id="securitySettingsForm" onsubmit="saveSecuritySettings(event)">
                                        <div class="row g-3" style="max-width: 400px;">
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Current Password</label>
                                                <input type="password" class="form-control" name="current_password"
                                                    required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">New Password</label>
                                                <input type="password" class="form-control" name="new_password"
                                                    required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Confirm New Password</label>
                                                <input type="password" class="form-control" name="confirm_password"
                                                    required>
                                            </div>
                                            <div class="col-12 mt-4">
                                                <button type="submit" class="btn btn-primary px-4 fw-bold">Change
                                                    Password</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold m-0">Order Details <span id="detailOrderId" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="mb-4">
                                <h6 class="fw-bold small text-uppercase text-muted mb-3">Client Information</h6>
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                                    <div class="rounded-circle bg-white shadow-sm d-flex align-items-center justify-content-center fw-bold text-primary" style="width:45px; height:45px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" id="detailClientName">Loading...</div>
                                        <div class="text-muted small" id="detailBusiness">Loading...</div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6 class="fw-bold small text-uppercase text-muted mb-3">Specifications</h6>
                                <div class="row g-2" id="detailSpecList">
                                    <!-- Dynamic Specs -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="card border-0 bg-primary text-white p-4 h-100" style="border-radius:20px;">
                                <h6 class="text-white-50 small text-uppercase fw-bold mb-4">Payment Summary</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Product:</span>
                                    <span class="fw-bold" id="detailProduct">Loading...</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Quantity:</span>
                                    <span class="fw-bold" id="detailQuantity">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Status:</span>
                                    <span class="badge bg-white text-primary rounded-pill small" id="detailStatus">Loading...</span>
                                </div>
                                <div class="mt-auto pt-4 border-top border-white-50">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h6 m-0">Total Amount</span>
                                        <span class="h4 m-0 fw-bold" id="detailTotal">₱0.00</span>
                                    </div>
                                    <div class="small text-white-50 mt-2" id="detailDate">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="specEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold m-0" id="specEditModalTitle">Add Specification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="specEditForm" onsubmit="handleSpecEditSubmit(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" id="specEditId">
                        <input type="hidden" id="specEditType">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Name</label>
                            <input type="text" class="form-control" id="specEditName" required placeholder="e.g. 14pt Cardstock">
                        </div>

                        <div class="mb-3" id="specEditMultiplierGroup">
                            <label class="form-label small fw-bold">Price Modifier (Multiplier)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="specEditMultiplier" placeholder="1.00">
                        </div>

                        <div id="specEditFinishGroup" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Setup Fee (₱)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="specEditSetupFee" placeholder="0.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Per-Unit Fee (₱)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="specEditPerUnitFee" placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select class="form-select" id="specEditActive">
                                <option value="1">Available</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="specEditSubmitBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold m-0">Edit User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="userEditForm" onsubmit="handleUserUpdate(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" class="form-control" id="editUserName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Role</label>
                            <select class="form-select" id="editUserRole" required>
                                <option value="client">Client</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select class="form-select" id="editUserStatus" required>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Proof Upload Modal -->
    <div class="modal fade" id="adminProofModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold m-0">Upload Print Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="adminProofForm" onsubmit="handleAdminProofUpload(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" id="adminProofOrderId">
                        <div class="mb-3 bg-light p-3 rounded" style="font-size: .85rem;">
                            <div><strong>Order #:</strong> <span id="adminProofOrderNum">PPR-000</span></div>
                            <div><strong>Client:</strong> <span id="adminProofClientName">Client Name</span></div>
                            <div><strong>Product:</strong> <span id="adminProofProduct">Flyers</span></div>
                            <div><strong>Qty:</strong> <span id="adminProofQty">100</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Print Proof File (PDF, Images, AI, PSD up to 500MB)</label>
                            <input type="file" class="form-control" id="adminProofFileInput" accept=".pdf,.png,.jpg,.jpeg,.ai,.psd" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="adminProofSubmitBtn">Upload Proof</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global State
        const state = {
            user: JSON.parse(sessionStorage.getItem('pp_user') || '{}'),
            theme: localStorage.getItem('printpro-theme') || 'light',
            charts: { status: null, revenue: null },
            orders: [],
            users: [],
            specs: [],
            subscriptions: []
        };

        // UI Initialization
        document.addEventListener('DOMContentLoaded', () => {
            const userRole = (state.user.role || '').toLowerCase();
            if (!state.user.id || (userRole !== 'admin' && userRole !== 'super_admin')) {
                window.location.href = 'index.html#login';
                return;
            }

            initTheme();
            initCharts();
            updateDashboardData();
            loadOrders();
            loadUsers();
            loadSubscriptions();
            loadSpecs();

            document.getElementById('userAvatar').textContent = state.user.name ? state.user.name[0].toUpperCase() : 'A';
            document.getElementById('userName').textContent = state.user.name || 'Admin';
            const roleEl = document.querySelector('.text-muted[style*="font-size:.7rem"]');
            if (roleEl) roleEl.textContent = state.user.role || 'Admin';

            // Fill settings inputs
            if (document.getElementById('setAdminName')) {
                document.getElementById('setAdminName').value = state.user.name || '';
                document.getElementById('setAdminEmail').value = state.user.email || '';
            }
            // Route to initial page from URL query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const pageParam = urlParams.get('page');
            if (pageParam && ['dashboard', 'orders', 'users', 'subscriptions', 'specs', 'settings'].includes(pageParam)) {
                showPage(pageParam);
            }
        });

        // Theme Toggle
        function initTheme() {
            document.documentElement.setAttribute('data-theme', state.theme);
            updateThemeIcon();
        }

        function toggleTheme() {
            state.theme = state.theme === 'light' ? 'dark' : 'light';
            localStorage.setItem('printpro-theme', state.theme);
            document.documentElement.setAttribute('data-theme', state.theme);
            updateThemeIcon();
            if (state.charts.status) state.charts.status.update();
            if (state.charts.revenue) state.charts.revenue.update();
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        }

        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            if (icon) icon.className = state.theme === 'light' ? 'bi bi-moon-stars' : 'bi bi-sun';
        }

        async function handleLogout(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '../api/logout.php';
            }
        }

        // Navigation
        function showPage(id) {
            const page = document.getElementById('page-' + id);
            if (!page) return;

            document.querySelectorAll('.a-page').forEach(p => p.classList.remove('active'));
            page.classList.add('active');

            document.querySelectorAll('.a-nav-item').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.a-nav-item').forEach(item => {
                if (item.textContent.trim().toLowerCase() === id.toLowerCase()) {
                    item.classList.add('active');
                }
            });

            const titles = { dashboard: 'Dashboard', orders: 'All Orders', users: 'User Management', subscriptions: 'Subscriptions', specs: 'Specifications', settings: 'Settings' };
            const titleEl = document.getElementById('pageTitle');
            if (titleEl) titleEl.textContent = titles[id] || id;

            if (id === 'dashboard') updateDashboardData();
            if (id === 'orders') loadOrders();
            if (id === 'users') loadUsers();
            if (id === 'subscriptions') loadSubscriptions();
            if (id === 'specs') loadSpecs();

            if (window.innerWidth < 992) {
                const sb = document.getElementById('sidebar');
                if (sb && sb.classList.contains('show')) toggleSidebar();
            }
        }

        // Charts
        function initCharts() {
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            state.charts.status = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Prepress', 'Printing', 'Finishing', 'Delivered'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: ['#fb6340', '#1171ef', '#7c4dff', '#2dce89'],
                        borderWidth: 0
                    }]
                },
                options: { maintainAspectRatio: false, cutout: '80%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 10 } } } } }
            });

            const revCtx = document.getElementById('revenueChart').getContext('2d');
            state.charts.revenue = new Chart(revCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        data: [],
                        borderColor: '#1d8cf8',
                        backgroundColor: 'rgba(29, 140, 248, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } } }
            });
        }

        async function updateDashboardData(period = '6months') {
            try {
                const res = await fetch(`../api/get_dashboard_stats.php?period=${period}&_=${Date.now()}`);
                const json = await res.json();
                if (json.success) {
                    const d = json.data;
                    const kpis = document.querySelectorAll('#dashboardKpis .kpi-val');
                    if (kpis.length >= 4) {
                        kpis[0].textContent = '₱' + parseFloat(d.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
                        kpis[1].textContent = d.status_counts['Delivered'] || 0;
                        kpis[2].textContent = (parseInt(d.status_counts['Prepress'] || 0)) + (parseInt(d.status_counts['Printing'] || 0)) + (parseInt(d.status_counts['Finishing'] || 0));
                        kpis[3].textContent = d.new_users || 0;
                    }

                    // Update Charts
                    state.charts.status.data.datasets[0].data = [
                        parseInt(d.status_counts['Prepress'] || 0),
                        parseInt(d.status_counts['Printing'] || 0),
                        parseInt(d.status_counts['Finishing'] || 0),
                        parseInt(d.status_counts['Delivered'] || 0)
                    ];
                    state.charts.status.update();

                    if (d.revenue_chart) {
                        state.charts.revenue.data.labels = d.revenue_chart.map(r => r.label);
                        state.charts.revenue.data.datasets[0].data = d.revenue_chart.map(r => parseFloat(r.value) || 0);
                        state.charts.revenue.update();
                    }

                    // Fix chart colors for dark mode
                    if (state.theme === 'dark') {
                        Chart.defaults.color = '#a0aec0';
                        state.charts.revenue.options.scales.x.grid.color = 'rgba(255,255,255,0.05)';
                        state.charts.revenue.options.scales.y.grid.color = 'rgba(255,255,255,0.05)';
                    } else {
                        Chart.defaults.color = '#666';
                        state.charts.revenue.options.scales.x.grid.color = 'rgba(0,0,0,0.05)';
                        state.charts.revenue.options.scales.y.grid.color = 'rgba(0,0,0,0.05)';
                    }

                    state.charts.revenue.update();
                } else {
                    showToast(json.message || 'Failed to load stats', 'danger');
                }
            } catch (e) { 
                console.error(e);
                showToast('Network error loading dashboard stats', 'danger'); 
            }
        }

        async function updateRevenueChart(period, label) {
            const btn = document.getElementById('revenueFilterBtn');
            btn.textContent = label;

            // Highlight active dropdown item
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.remove('active');
                if (item.textContent === label) item.classList.add('active');
            });

            await updateDashboardData(period);
        }

        async function loadOrders() {
            try {
                const res = await fetch('../api/get_orders.php?_=' + Date.now());
                const json = await res.json();
                if (json.success) {
                    state.orders = json.data;
                    renderOrders();
                    renderRecentOrders();
                    renderActivityFeed();
                } else {
                    showToast(json.message || 'Failed to load orders', 'danger');
                }
            } catch (e) { showToast('Network error loading orders', 'danger'); }
        }

        function renderOrders(data = state.orders) {
            const tbody = document.querySelector('#ordersTable tbody');
            tbody.innerHTML = data.map(o => {
                const progress = getProgress(o.status);
                const sColor = getStatusColor(o.status);
                const orderNum = o.order_number || ('PPR-' + String(o.id).padStart(3, '0'));
                
                // Highlight rows that are Proof Pending or Proof Pending Review
                let rowStyle = '';
                if (o.status === 'Proof Pending') {
                    rowStyle = 'style="background: rgba(251, 99, 64, 0.05);"';
                } else if (o.status === 'Proof Pending Review') {
                    rowStyle = 'style="background: rgba(29, 140, 248, 0.03);"';
                }

                // Custom actions for proof files
                let proofBtn = '';
                if (o.status === 'Proof Pending') {
                    proofBtn = `<button class="btn btn-warning action-btn text-white" style="background:var(--warning); border:none;" onclick="openAdminProofUploadModal(${o.id})" title="Upload Print Proof"><i class="bi bi-cloud-arrow-up"></i></button>`;
                } else if (o.proof_file) {
                    const proofPath = '../' + o.proof_file.replace(/^\.\.\//, '');
                    proofBtn = `<a href="${proofPath}" target="_blank" class="btn btn-success action-btn text-white" style="background:#2dce89; border:none;" title="View Print Proof"><i class="bi bi-file-earmark-check"></i></a>`;
                }

                return `
                    <tr ${rowStyle}>
                        <td class="fw-bold">#${orderNum}</td>
                        <td>${o.business_name || o.client_name || 'Walk-in'}</td>
                        <td>${o.product_type || 'Custom'}</td>
                        <td>${parseInt(o.quantity).toLocaleString()}</td>
                        <td style="width:120px;">
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="width:${progress}%; background:${sColor};"></div>
                            </div>
                        </td>
                        <td>
                            <select class="status-select" style="color:${sColor}; border-color:${sColor}; font-size: .73rem; padding: 2px 6px;" onchange="updateOrderStatus(${o.id}, this.value)">
                                ${['Proof Pending', 'Proof Pending Review', 'Prepress', 'Printing', 'Finishing', 'Shipping', 'Delivered', 'Reprint'].map(s => `<option value="${s}" ${o.status === s ? 'selected' : ''}>${s}</option>`).join('')}
                            </select>
                        </td>
                        <td class="text-muted small">${o.due_date ? new Date(o.due_date).toLocaleDateString() : '-'}</td>
                        <td class="fw-bold">₱${parseFloat(o.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light action-btn" onclick="viewOrderDetails(${o.id})" title="View Details"><i class="bi bi-eye"></i></button>
                                <a href="../api/generate_ticket.php?order_id=${o.id}" target="_blank" class="btn btn-light action-btn" title="Job Ticket"><i class="bi bi-ticket-perforated"></i></a>
                                ${proofBtn}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('') || '<tr><td colspan="9" class="text-center p-5 text-muted">No orders found.</td></tr>';
        }

        function renderRecentOrders() {
            const tbody = document.querySelector('#recentOrdersTable tbody');
            tbody.innerHTML = state.orders.slice(0, 5).map(o => `
                <tr>
                    <td class="fw-bold">#ORD-${o.id}</td>
                    <td>${o.business_name || o.client_name || 'Client'}</td>
                    <td>${o.product_type || 'Job'}</td>
                    <td><span class="badge-status" style="background:${getStatusColor(o.status)}20; color:${getStatusColor(o.status)}; border: 1px solid ${getStatusColor(o.status)}40;">${o.status}</span></td>
                    <td class="fw-bold">₱${parseFloat(o.total_amount).toLocaleString()}</td>
                </tr>
            `).join('');
        }

        function renderActivityFeed() {
            const feed = document.getElementById('activityFeed');
            feed.innerHTML = state.orders.slice(0, 4).map(o => `
                <div class="d-flex gap-3 mb-3 align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:40px; height:40px; flex-shrink:0; font-size: 1.1rem;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="small fw-bold">Order #ORD-${o.id} placed</div>
                        <div class="text-muted" style="font-size:.7rem;">By ${o.business_name || o.client_name} • ${timeAgo(o.created_at)}</div>
                    </div>
                </div>
            `).join('');
        }

        function filterOrdersByStatus(status) {
            // Update UI tabs
            document.querySelectorAll('#orderTabs .nav-link').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.trim() === status || (status === 'All' && btn.textContent.trim() === 'All Orders')) {
                    btn.classList.add('active');
                }
            });

            document.getElementById('orderListTitle').textContent = status === 'All' ? 'All Orders' : status + ' Orders';

            if (status === 'All') {
                renderOrders(state.orders);
            } else {
                const filtered = state.orders.filter(o => o.status === status);
                renderOrders(filtered);
            }
        }

        function searchOrders(q) {
            const query = q.toLowerCase();
            const filtered = state.orders.filter(o =>
                o.id.toString().includes(query) ||
                (o.business_name || '').toLowerCase().includes(query) ||
                (o.product_type || '').toLowerCase().includes(query)
            );
            renderOrders(filtered);
        }

        async function viewOrderDetails(id) {
            try {
                const res = await fetch(`../api/get_order_details.php?id=${id}&_=${Date.now()}`);
                const json = await res.json();
                if (json.success) {
                    const o = json.data;
                    document.getElementById('detailOrderId').textContent = o.id;
                    document.getElementById('detailClientName').textContent = o.client_name || 'N/A';
                    document.getElementById('detailBusiness').textContent = o.business_name || 'Personal Account';
                    document.getElementById('detailProduct').textContent = o.product_type || 'N/A';
                    document.getElementById('detailQuantity').textContent = parseInt(o.quantity).toLocaleString();
                    document.getElementById('detailTotal').textContent = '₱' + parseFloat(o.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 });
                    document.getElementById('detailStatus').textContent = o.status;
                    document.getElementById('detailDate').textContent = new Date(o.created_at).toLocaleString();

                    // Render Specs
                    const specList = document.getElementById('detailSpecList');
                    specList.innerHTML = '';
                    if (o.specs && Array.isArray(o.specs)) {
                        o.specs.forEach(s => {
                            specList.innerHTML += `
                                <div class="col-6 mb-2">
                                    <div class="text-muted small text-capitalize">${s.spec_type}</div>
                                    <div class="fw-bold">${s.name}</div>
                                </div>
                            `;
                        });
                    } else {
                        specList.innerHTML = '<div class="col-12 text-muted">No specifications recorded.</div>';
                    }

                    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                } else {
                    showToast(json.message || 'Failed to load details', 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        async function updateOrderStatus(orderId, status) {
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('status', status);
                const res = await fetch('../api/update_order_status.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    showToast(`Order #ORD-${orderId} updated to ${status}`, 'success');
                    loadOrders();
                    updateDashboardData();
                } else {
                    showToast(json.message || 'Update failed', 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        async function loadUsers() {
            try {
                const res = await fetch('../api/get_users.php?_=' + Date.now());
                const json = await res.json();
                if (json.success) {
                    state.users = json.data;
                    filterAndSortUsers();
                } else {
                    showToast(json.message || 'Failed to load users', 'danger');
                }
            } catch (e) { showToast('Network error loading users', 'danger'); }
        }

        function filterAndSortUsers() {
            const roleFilter = (document.getElementById('userRoleFilter')?.value || 'all').toLowerCase();
            const sortVal = document.getElementById('userSortSelect')?.value || 'newest';
            const query = (document.getElementById('userSearchInput')?.value || '').toLowerCase();

            let filtered = state.users.filter(u => {
                const matchRoleFilter = roleFilter === 'all' || (u.role || '').toLowerCase() === roleFilter;
                const matchQuery = (u.name || '').toLowerCase().includes(query) ||
                    (u.email || '').toLowerCase().includes(query) ||
                    (u.business_name || '').toLowerCase().includes(query) ||
                    (u.role || '').toLowerCase().includes(query);
                return matchRoleFilter && matchQuery;
            });

            filtered.sort((a, b) => {
                if (sortVal === 'newest') {
                    return new Date(b.created_at) - new Date(a.created_at);
                } else if (sortVal === 'oldest') {
                    return new Date(a.created_at) - new Date(b.created_at);
                } else if (sortVal === 'name_asc') {
                    return (a.name || '').localeCompare(b.name || '');
                } else if (sortVal === 'name_desc') {
                    return (b.name || '').localeCompare(a.name || '');
                } else if (sortVal === 'role_admin') {
                    if (a.role === b.role) return (a.name || '').localeCompare(b.name || '');
                    return (a.role || '').toLowerCase() === 'admin' ? -1 : 1;
                } else if (sortVal === 'role_client') {
                    if (a.role === b.role) return (a.name || '').localeCompare(b.name || '');
                    return (a.role || '').toLowerCase() === 'client' ? -1 : 1;
                }
                return 0;
            });

            renderUsers(filtered);
        }

        function renderUsers(data = state.users) {
            const tbody = document.querySelector('#usersTable tbody');
            tbody.innerHTML = data.map(u => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px; flex-shrink:0;">
                                ${u.name ? u.name[0].toUpperCase() : 'U'}
                            </div>
                            <div>
                                <div class="fw-bold">${u.name}</div>
                                <div class="text-muted small">${u.email}</div>
                            </div>
                        </div>
                    </td>
                    <td>${u.business_name || '-'}</td>
                    <td><span class="badge bg-primary-subtle text-primary text-uppercase" style="font-size: .65rem;">${u.role}</span></td>
                    <td><span class="badge-status ${(u.status || '').toLowerCase() === 'active' ? 'badge-success' : 'badge-danger'}">${u.status}</span></td>
                    <td class="text-muted small">${new Date(u.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-light" onclick="openUserEditModal(${u.id})"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-sm btn-light text-danger" onclick="deleteUser(${u.id})"><i class="bi bi-trash3"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="6" class="text-center p-4 text-muted">No users found.</td></tr>';
        }

        function openUserEditModal(id) {
            const u = state.users.find(x => x.id == id);
            if (!u) return;
            document.getElementById('editUserId').value = u.id;
            document.getElementById('editUserName').value = u.name;
            document.getElementById('editUserRole').value = u.role;
            document.getElementById('editUserStatus').value = u.status;
            new bootstrap.Modal(document.getElementById('userEditModal')).show();
        }

        function searchUsers(q) {
            const input = document.getElementById('userSearchInput');
            if (input) input.value = q;
            filterAndSortUsers();
        }

        async function handleUserUpdate(e) {
            e.preventDefault();
            const id = document.getElementById('editUserId').value;
            const formData = new FormData();
            formData.append('id', id);
            formData.append('name', document.getElementById('editUserName').value);
            formData.append('role', document.getElementById('editUserRole').value);
            formData.append('status', document.getElementById('editUserStatus').value);

            try {
                const res = await fetch('../api/update_user.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    showToast('User updated successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('userEditModal')).hide();
                    loadUsers();
                } else {
                    showToast(json.message || 'Update failed', 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        async function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user? This may affect associated orders.')) return;
            try {
                const res = await fetch(`../api/delete_user.php?id=${id}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.success) {
                    showToast('User deleted successfully', 'success');
                    loadUsers();
                } else {
                    showToast(json.message || 'Delete failed', 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        function viewUserProfile(id) {
            const u = state.users.find(x => x.id == id);
            if (!u) return;
            document.getElementById('profileAvatar').textContent = u.name[0];
            document.getElementById('profileName').textContent = u.name;
            document.getElementById('profileEmail').textContent = u.email;
            document.getElementById('profileRole').textContent = u.role;
            document.getElementById('profileStatus').textContent = u.status;
            document.getElementById('profileBusiness').textContent = u.business_name || 'Personal Account';
            document.getElementById('profileJoined').textContent = new Date(u.created_at).toLocaleDateString();
        }

        async function loadSubscriptions() {
            try {
                const res = await fetch('../api/get_subscriptions.php?_=' + Date.now());
                const json = await res.json();
                if (json.success) {
                    state.subscriptions = json.data;
                    renderSubscriptions();
                } else {
                    showToast(json.message || 'Failed to load subscriptions', 'danger');
                }
            } catch (e) { showToast('Network error loading subscriptions', 'danger'); }
        }

        function renderSubscriptions(data = state.subscriptions) {
            const tbody = document.querySelector('#subsTable tbody');
            tbody.innerHTML = data.map(s => `
                <tr>
                    <td class="fw-bold">${s.name}</td>
                    <td>${s.email}</td>
                    <td>${s.business_name || 'N/A'}</td>
                    <td>
                        <span class="badge bg-primary text-uppercase">${s.active_plan || s.base_plan}</span>
                    </td>
                    <td>
                        <span class="badge-status ${(s.sub_status || '').toLowerCase() === 'active' ? 'badge-success' :
                    (s.sub_status || '').toLowerCase() === 'suspended' ? 'badge-danger' : 'badge-warning'
                }">
                            ${s.sub_status || 'Inactive'}
                        </span>
                    </td>
                    <td>${s.renews_on ? new Date(s.renews_on).toLocaleDateString() : '-'}</td>
                    <td>
                        <button class="btn btn-sm ${(s.sub_status || '').toLowerCase() === 'suspended' ? 'btn-success' : 'btn-outline-danger'} px-3" 
                                onclick="toggleUserAccountStatus(${s.user_id}, '${s.sub_status}')">
                            ${(s.sub_status || '').toLowerCase() === 'suspended' ? 'Activate' : 'Suspend'}
                        </button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="text-center p-4 text-muted">No matching subscriptions.</td></tr>';
        }

        function searchSubscriptions(q) {
            const query = q.toLowerCase();
            const filtered = state.subscriptions.filter(s =>
                (s.name || '').toLowerCase().includes(query) ||
                (s.business_name || '').toLowerCase().includes(query) ||
                (s.email || '').toLowerCase().includes(query)
            );
            renderSubscriptions(filtered);
        }

        async function toggleUserAccountStatus(id, currentStatus) {
            const newStatus = (currentStatus || '').toLowerCase() === 'suspended' ? 'active' : 'suspended';
            const action = newStatus === 'suspended' ? 'suspend' : 'activate';

            if (!confirm(`Are you sure you want to ${action} this user account?`)) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', newStatus);

            try {
                const res = await fetch('../api/toggle_user_status.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    showToast(`User account ${newStatus} successfully`, 'success');
                    loadSubscriptions(); // Refresh table
                } else {
                    showToast(json.message, 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        async function loadSpecs() {
            try {
                const res = await fetch('../api/specs.php');
                const json = await res.json();
                if (json.success) {
                    state.specs = json.data;
                    renderSpecs();
                }
            } catch (e) { showToast('Failed to load specifications', 'danger'); }
        }

        function renderSpecs() {
            // Materials table
            const paperBody = document.querySelector('#table-paper tbody');
            const paperSpecs = state.specs.filter(s => s.spec_type === 'paper');
            if (paperSpecs.length === 0) {
                paperBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No records found.</td></tr>`;
            } else {
                paperBody.innerHTML = paperSpecs.map(s => `
                    <tr>
                        <td class="text-muted fw-bold">#${s.id}</td>
                        <td class="fw-bold">${escapeHtml(s.name)}</td>
                        <td>${parseFloat(s.price_modifier).toFixed(2)}×</td>
                        <td><span class="badge-status ${s.is_active == 1 ? 'badge-success' : 'badge-danger'}">${s.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <label class="toggle-wrap">
                                <input type="checkbox" ${s.is_active == 1 ? 'checked' : ''} onchange="toggleSpecActive('${s.spec_type}', ${s.id}, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light action-btn" onclick="openSpecEditModal('${s.spec_type}', ${s.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-light action-btn text-danger" onclick="deleteSpec(${s.id}, '${s.spec_type}')" title="Delete"><i class="bi bi-trash3"></i></button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            // Finishes table
            const finishBody = document.querySelector('#table-finish tbody');
            const finishSpecs = state.specs.filter(s => s.spec_type === 'finish');
            if (finishSpecs.length === 0) {
                finishBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No records found.</td></tr>`;
            } else {
                finishBody.innerHTML = finishSpecs.map(s => `
                    <tr>
                        <td class="text-muted fw-bold">#${s.id}</td>
                        <td class="fw-bold">${escapeHtml(s.name)}</td>
                        <td class="fw-bold">₱${parseFloat(s.price_modifier || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td class="fw-bold">₱${parseFloat(s.per_unit_fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td><span class="badge-status ${s.is_active == 1 ? 'badge-success' : 'badge-danger'}">${s.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <label class="toggle-wrap">
                                <input type="checkbox" ${s.is_active == 1 ? 'checked' : ''} onchange="toggleSpecActive('${s.spec_type}', ${s.id}, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light action-btn" onclick="openSpecEditModal('${s.spec_type}', ${s.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-light action-btn text-danger" onclick="deleteSpec(${s.id}, '${s.spec_type}')" title="Delete"><i class="bi bi-trash3"></i></button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            // Sizes table
            const sizeBody = document.querySelector('#table-size tbody');
            const sizeSpecs = state.specs.filter(s => s.spec_type === 'size');
            if (sizeSpecs.length === 0) {
                sizeBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No records found.</td></tr>`;
            } else {
                sizeBody.innerHTML = sizeSpecs.map(s => `
                    <tr>
                        <td class="text-muted fw-bold">#${s.id}</td>
                        <td class="fw-bold">${escapeHtml(s.name)}</td>
                        <td>${parseFloat(s.price_modifier).toFixed(2)}×</td>
                        <td><span class="badge-status ${s.is_active == 1 ? 'badge-success' : 'badge-danger'}">${s.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                        <td>
                            <label class="toggle-wrap">
                                <input type="checkbox" ${s.is_active == 1 ? 'checked' : ''} onchange="toggleSpecActive('${s.spec_type}', ${s.id}, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light action-btn" onclick="openSpecEditModal('${s.spec_type}', ${s.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-light action-btn text-danger" onclick="deleteSpec(${s.id}, '${s.spec_type}')" title="Delete"><i class="bi bi-trash3"></i></button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }

        async function toggleSpecActive(type, id, active) {
            const row = state.specs.find(s => s.id == id && s.spec_type === type);
            if (!row) return;
            const body = new URLSearchParams();
            body.append('id', id);
            body.append('spec_type', type);
            body.append('name', row.name);
            body.append('is_active', active ? 1 : 0);
            body.append('price_modifier', row.price_modifier || 0);
            body.append('per_unit_fee', row.per_unit_fee || 0);

            try {
                const res = await fetch('../api/specs.php', { method: 'PUT', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
                const json = await res.json();
                if (json.success) {
                    showToast(active ? 'Activated specification' : 'Deactivated specification', 'success');
                    loadSpecs();
                } else {
                    showToast(json.message || 'Error updating status', 'danger');
                    loadSpecs();
                }
            } catch (e) { 
                showToast('Network error', 'danger'); 
                loadSpecs();
            }
        }

        function openSpecEditModal(type, id = null) {
            const modal = new bootstrap.Modal(document.getElementById('specEditModal'));
            const form = document.getElementById('specEditForm');
            form.reset();

            document.getElementById('specEditId').value = id || '';
            document.getElementById('specEditType').value = type;

            const typeNames = { paper: 'Material', finish: 'Finish', size: 'Size' };
            document.getElementById('specEditModalTitle').textContent = (id ? 'Edit ' : 'Add ') + typeNames[type];

            const isFinish = type === 'finish';
            document.getElementById('specEditMultiplierGroup').style.display = isFinish ? 'none' : 'block';
            document.getElementById('specEditFinishGroup').style.display = isFinish ? 'block' : 'none';

            document.getElementById('specEditMultiplier').required = !isFinish;
            document.getElementById('specEditSetupFee').required = isFinish;
            document.getElementById('specEditPerUnitFee').required = isFinish;

            if (id) {
                const s = state.specs.find(x => x.id == id && x.spec_type === type);
                if (s) {
                    document.getElementById('specEditName').value = s.name;
                    if (isFinish) {
                        document.getElementById('specEditSetupFee').value = s.price_modifier || '0.00';
                        document.getElementById('specEditPerUnitFee').value = s.per_unit_fee || '0.00';
                    } else {
                        document.getElementById('specEditMultiplier').value = s.price_modifier || '1.00';
                    }
                    document.getElementById('specEditActive').value = s.is_active;
                }
            } else {
                document.getElementById('specEditActive').value = '1';
            }

            modal.show();
        }

        async function handleSpecEditSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('specEditId').value;
            const type = document.getElementById('specEditType').value;
            const name = document.getElementById('specEditName').value.trim();
            const is_active = document.getElementById('specEditActive').value;

            const isFinish = type === 'finish';
            const price_modifier = isFinish 
                ? document.getElementById('specEditSetupFee').value 
                : document.getElementById('specEditMultiplier').value;
            const per_unit_fee = isFinish 
                ? document.getElementById('specEditPerUnitFee').value 
                : '0.00';

            const method = id ? 'PUT' : 'POST';
            const body = new URLSearchParams();
            if (id) body.append('id', id);
            body.append('spec_type', type);
            body.append('name', name);
            body.append('is_active', is_active);
            body.append('price_modifier', price_modifier);
            body.append('per_unit_fee', per_unit_fee);

            try {
                const res = await fetch('../api/specs.php', { method, headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
                const json = await res.json();
                if (json.success) {
                    showToast(id ? 'Specification updated' : 'Specification added', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('specEditModal')).hide();
                    loadSpecs();
                } else {
                    showToast(json.message || 'Failed to save specification', 'danger');
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        async function deleteSpec(id, type) {
            if (!confirm('Are you sure you want to delete this?')) return;
            try {
                const body = new URLSearchParams();
                body.append('id', id);
                body.append('spec_type', type);
                const res = await fetch('../api/specs.php', { method: 'DELETE', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
                const json = await res.json();
                if (json.success) {
                    showToast('Deleted successfully', 'success');
                    loadSpecs();
                }
            } catch (e) { showToast('Network error', 'danger'); }
        }

        function escapeHtml(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Helpers
        function getProgress(status) {
            return {
                'Proof Pending': 5,
                'Proof Pending Review': 10,
                'Prepress': 20,
                'Printing': 40,
                'Finishing': 60,
                'Shipping': 80,
                'Delivered': 100
            }[status] || 0;
        }

        function getStatusColor(status) {
            return {
                'Proof Pending': '#fb6340',
                'Proof Pending Review': '#1d8cf8',
                'Prepress': '#1171ef',
                'Printing': '#7c4dff',
                'Finishing': '#8c09ff',
                'Shipping': '#2dce89',
                'Delivered': '#2dce89',
                'Reprint': '#f5365c'
            }[status] || '#8898aa';
        }

        function timeAgo(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            if (seconds < 60) return 'just now';
            const intervals = { 'yr': 31536000, 'mo': 2592000, 'd': 86400, 'h': 3600, 'm': 60 };
            for (let [suffix, val] of Object.entries(intervals)) {
                const count = Math.floor(seconds / val);
                if (count > 0) return `${count}${suffix} ago`;
            }
            return 'just now';
        }

        // ── ADMIN PROOF UPLOAD HANDLERS ──────────────────
        function openAdminProofUploadModal(orderId) {
            const o = state.orders.find(x => x.id == orderId);
            if (!o) return;

            document.getElementById('adminProofOrderId').value = o.id;
            document.getElementById('adminProofOrderNum').textContent = o.order_number || ('PPR-' + String(o.id).padStart(3, '0'));
            document.getElementById('adminProofClientName').textContent = o.business_name || o.client_name || 'Client';
            document.getElementById('adminProofProduct').textContent = o.product_type || 'Custom';
            document.getElementById('adminProofQty').textContent = parseInt(o.quantity).toLocaleString();
            
            document.getElementById('adminProofFileInput').value = '';

            const modal = new bootstrap.Modal(document.getElementById('adminProofModal'));
            modal.show();
        }

        async function handleAdminProofUpload(e) {
            e.preventDefault();
            const orderId = document.getElementById('adminProofOrderId').value;
            const fileInput = document.getElementById('adminProofFileInput');
            
            if (!orderId || !fileInput.files.length) {
                showToast('Please select a proof file.', 'warning');
                return;
            }

            const submitBtn = document.getElementById('adminProofSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';

            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('proof_file', fileInput.files[0]);

            try {
                const res = await fetch('../api/upload_proof.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    showToast('Print proof uploaded successfully!', 'success');
                    
                    // Hide modal cleanly
                    const modalEl = document.getElementById('adminProofModal');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if (modalInst) modalInst.hide();
                    
                    loadOrders();
                    updateDashboardData();
                } else {
                    showToast(json.message || 'Failed to upload proof', 'danger');
                }
            } catch (e) {
                showToast('Network error during proof upload.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload Proof';
            }
        }

    </script>
</body>
</html>