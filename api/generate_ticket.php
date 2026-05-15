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
    $stmt = $pdo->prepare("SELECT o.*, u.name as client_name, c.business_name
                          FROM orders o
                          JOIN clients c ON o.client_id = c.id
                          JOIN users u ON c.user_id = u.id
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order)
        die('Order not found');

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
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
                <div><strong>Product:</strong> <?php echo $order['product_type']; ?></div>
                <div><strong>Quantity:</strong> <?php echo number_format($order['quantity']); ?> units</div>
                <div><strong>Status:</strong> <?php echo $order['status']; ?></div>
                <div><strong>Due Date:</strong> <?php echo $order['due_date']; ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Technical Specifications</div>
            <div class="grid">
                <div><strong>Size:</strong> <?php echo $order['size_width'] . '" x ' . $order['size_height'] . '"'; ?></div>
                <div><strong>Paper:</strong> <?php echo $order['paper_weight']; ?></div>
                <div><strong>Finishing:</strong> <?php echo $order['finish'] ?: 'Standard'; ?></div>
            </div>
        </div>

        <div class="barcode">
            <div style="font-family: 'Courier New', Courier, monospace; font-size: 2.5rem; font-weight: 800; letter-spacing: 4px;">*ORD-<?php echo $order['id']; ?>*</div>
            <div style="font-size: 0.8rem; font-weight: 600; letter-spacing: 1px; margin-top: 5px;">SCAN TO UPDATE PRODUCTION STATUS</div>
        </div>

        <div class="footer">
            PrintPro Internal Document - Confidential
        </div>
    </div>
</body>

</html>