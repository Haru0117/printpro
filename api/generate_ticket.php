<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$order_id = $_GET['order_id'] ?? 0;
if (!$order_id)
    die('Order ID required');

try {
    $stmt = $pdo->prepare("SELECT o.*, u.name as client_name, u.business_name,
                                 p.name as paper_name, s.name as size_name, f.name as finish_name
                          FROM orders o
                          JOIN users u ON o.user_id = u.id
                          LEFT JOIN tbl_materials p ON o.paper_id = p.id
                          LEFT JOIN tbl_sizes s ON o.size_id = s.id
                          LEFT JOIN tbl_finishes f ON o.finish_id = f.id
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order)
        die('Order not found');

} catch (PDOException $e) {
    die('Database error');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Job Ticket #<?php echo $order['id']; ?></title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding: 40px;
            color: #333;
        }

        .ticket {
            border: 2px solid #000;
            padding: 20px;
            max-width: 800px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .job-id {
            font-size: 2rem;
            font-weight: bold;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            margin-bottom: 10px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .barcode {
            text-align: center;
            margin-top: 40px;
        }

        .footer {
            margin-top: 40px;
            font-size: 0.8rem;
            text-align: center;
            color: #666;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()">Print Ticket</button>
    </div>
    <div class="ticket">
        <div class="header">
            <div>
                <div style="font-size: 1.2rem; font-weight: bold;">PrintPro Production Ticket</div>
                <div>Generated on: <?php echo date('Y-m-d H:i'); ?></div>
            </div>
            <div class="job-id">#ORD-<?php echo $order['id']; ?></div>
        </div>

        <div class="section">
            <div class="section-title">Client Information</div>
            <div class="grid">
                <div><strong>Client Name:</strong> <?php echo $order['client_name']; ?></div>
                <div><strong>Business:</strong> <?php echo $order['business_name']; ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Job Details</div>
            <div class="grid">
                <div><strong>Job Name:</strong> <?php echo $order['job_name']; ?></div>
                <div><strong>Quantity:</strong> <?php echo number_format($order['quantity']); ?> units</div>
                <div><strong>Status:</strong> <?php echo $order['status']; ?></div>
                <div><strong>Order Date:</strong> <?php echo $order['created_at']; ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Technical Specifications</div>
            <div class="grid">
                <div><strong>Size:</strong> <?php echo $order['size_name'] ?: 'Custom'; ?></div>
                <div><strong>Paper:</strong> <?php echo $order['paper_name']; ?></div>
                <div><strong>Finishing:</strong> <?php echo $order['finish_name'] ?: 'Standard'; ?></div>
            </div>
        </div>

        <div class="barcode">
            <div style="font-family: 'Libre Barcode 39', cursive; font-size: 4rem;">*ORD-<?php echo $order['id']; ?>*
            </div>
            <div style="font-size: 0.8rem;">SCAN TO UPDATE PRODUCTION STATUS</div>
        </div>

        <div class="footer">
            PrintPro Internal Document - Confidential
        </div>
    </div>
</body>

</html>