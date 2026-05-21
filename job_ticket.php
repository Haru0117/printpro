<?php
// ─────────────────────────────────────────────────────────────
//  PrintPro — Job Ticket Viewer & Generator
//  URL: job_ticket.php?order_id=XX
//  Displays a clean, print-optimized job ticket for production floor
// ─────────────────────────────────────────────────────────────

session_start();
require_once 'includes/db.php';

// Get order ID from query parameter
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

if (!$order_id) {
    die('Error: Order ID not provided.');
}

// Fetch order details with client info
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.business_name,
            u.name as client_name,
            u.email as client_email
        FROM orders o
        LEFT JOIN clients c ON o.client_id = c.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die('Error: Order not found.');
    }

    // Log ticket generation if user is authenticated
    if (isset($_SESSION['user_id'])) {
        $logStmt = $pdo->prepare("
            INSERT INTO job_tickets_log (order_id, generated_by_user_id, generated_at)
            VALUES (?, ?, NOW())
        ");
        $logStmt->execute([$order_id, $_SESSION['user_id']]);
    }

} catch (Exception $e) {
    die('Error: ' . htmlspecialchars($e->getMessage()));
}

// Format data
$orderNum = $order['order_number'] ?? ('PPR-' . str_pad($order_id, 3, '0', STR_PAD_LEFT));
$businessName = htmlspecialchars($order['business_name'] ?? $order['client_name'] ?? 'Walk-in Client');
$clientName = htmlspecialchars($order['client_name'] ?? 'Client');
$clientEmail = htmlspecialchars($order['client_email'] ?? '');
$productType = htmlspecialchars($order['product_type'] ?? 'Custom Product');
$quantity = (int)$order['quantity'];
$paperWeight = htmlspecialchars($order['paper_weight'] ?? 'Not Specified');
$finish = htmlspecialchars($order['finish'] ?? 'None');
$printSides = htmlspecialchars($order['print_sides'] ?? 'Single-sided');
$bleed = htmlspecialchars($order['bleed'] ?? 'No');
$sizeWidth = htmlspecialchars($order['size_width'] ?? '0');
$sizeHeight = htmlspecialchars($order['size_height'] ?? '0');
$turnaround = htmlspecialchars($order['turnaround'] ?? 'Standard');
$shippingMethod = htmlspecialchars($order['shipping_method'] ?? 'Standard Delivery');
$totalAmount = number_format($order['total_amount'] ?? 0, 2);
$dueDate = $order['due_date'] ? date('M d, Y', strtotime($order['due_date'])) : 'Not Set';
$notes = nl2br(htmlspecialchars($order['notes'] ?? ''));
$artworkFile = htmlspecialchars($order['artwork_file'] ?? '');
$generatedTime = date('F d, Y \a\t g:i A');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Ticket #<?php echo $orderNum; ?> - PrintPro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #000;
            line-height: 1.6;
        }

        .container {
            max-width: 850px;
            margin: 20px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 2px solid #000;
        }

        .no-print {
            margin-bottom: 20px;
            text-align: right;
        }

        .no-print button {
            padding: 10px 20px;
            background-color: #1d8cf8;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .no-print button:hover {
            background-color: #155db5;
        }

        /* ──────── HEADER SECTION ──────── */
        .ticket-header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .ticket-header h1 {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .order-number {
            font-size: 24px;
            font-weight: bold;
            color: #d32f2f;
            letter-spacing: 1px;
        }

        /* ──────── CLIENT SECTION ──────── */
        .section {
            margin-bottom: 25px;
            border: 2px solid #000;
            padding: 15px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
            display: block;
        }

        .client-info {
            font-size: 14px;
            line-height: 1.8;
        }

        .client-info strong {
            font-weight: bold;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: bold;
            min-width: 150px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .info-value {
            flex: 1;
            font-size: 14px;
        }

        /* ──────── SPECS GRID ──────── */
        .specs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .spec-box {
            border: 2px solid #000;
            padding: 12px;
            background-color: #f9f9f9;
        }

        .spec-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }

        .spec-value {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        /* ──────── NOTES SECTION ──────── */
        .notes-section {
            border: 2px solid #000;
            padding: 15px;
            background-color: #fffacd;
            min-height: 100px;
        }

        .notes-section h5 {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }

        .notes-content {
            font-size: 13px;
            line-height: 1.6;
            font-family: monospace;
        }

        .no-notes {
            color: #999;
            font-style: italic;
        }

        /* ──────── ARTWORK FILE ──────── */
        .artwork-section {
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 25px;
            background-color: #f0f0f0;
        }

        .artwork-label {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: block;
        }

        .artwork-file {
            font-size: 14px;
            font-weight: bold;
            color: #d32f2f;
            word-break: break-all;
        }

        .no-file {
            color: #999;
            font-style: italic;
        }

        /* ──────── FOOTER ──────── */
        .ticket-footer {
            border-top: 3px solid #000;
            padding-top: 15px;
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 30px;
        }

        /* ──────── PRINT STYLES ──────── */
        @media print {
            body {
                background: white;
            }

            .container {
                max-width: 100%;
                margin: 0;
                padding: 40px;
                box-shadow: none;
            }

            .no-print {
                display: none;
            }

            button,
            input[type="button"],
            input[type="submit"] {
                display: none !important;
            }
        }

        /* ──────── RESPONSIVE ──────── */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .ticket-header h1 {
                font-size: 24px;
            }

            .order-number {
                font-size: 20px;
            }

            .info-label {
                min-width: 120px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Print Button (Hidden when Printing) -->
        <div class="no-print">
            <button onclick="window.print()">🖨️ Print This Ticket</button>
        </div>

        <!-- Header -->
        <div class="ticket-header">
            <h1>JOB TICKET</h1>
            <div class="order-number">#<?php echo $orderNum; ?></div>
        </div>

        <!-- Client Information -->
        <div class="section">
            <span class="section-title">Client Information</span>
            <div class="client-info">
                <div class="info-row">
                    <span class="info-label">Client Name:</span>
                    <span class="info-value"><?php echo $clientName; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Business:</span>
                    <span class="info-value"><?php echo $businessName; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo $clientEmail; ?></span>
                </div>
            </div>
        </div>

        <!-- Important Dates -->
        <div class="section">
            <span class="section-title">Timeline</span>
            <div class="specs-grid">
                <div class="spec-box">
                    <span class="spec-label">Due Date</span>
                    <div class="spec-value"><?php echo $dueDate; ?></div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Turnaround</span>
                    <div class="spec-value"><?php echo $turnaround; ?></div>
                </div>
            </div>
        </div>

        <!-- Product Specifications -->
        <div class="section">
            <span class="section-title">Product & Specifications</span>
            <div class="specs-grid">
                <div class="spec-box">
                    <span class="spec-label">Product Type</span>
                    <div class="spec-value"><?php echo $productType; ?></div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Quantity</span>
                    <div class="spec-value"><?php echo number_format($quantity); ?> units</div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Size</span>
                    <div class="spec-value"><?php echo $sizeWidth; ?>" × <?php echo $sizeHeight; ?>"</div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Paper Weight</span>
                    <div class="spec-value"><?php echo $paperWeight; ?></div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Finish</span>
                    <div class="spec-value"><?php echo $finish; ?></div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Print Sides</span>
                    <div class="spec-value"><?php echo $printSides; ?></div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Bleed</span>
                    <div class="spec-value"><?php echo $bleed; ?></div>
                </div>
                <div class="spec-box">
                    <span class="spec-label">Shipping Method</span>
                    <div class="spec-value"><?php echo $shippingMethod; ?></div>
                </div>
            </div>
        </div>

        <!-- Artwork File -->
        <div class="artwork-section">
            <span class="artwork-label">Artwork File</span>
            <?php if ($artworkFile): ?>
                <div class="artwork-file">
                    📎 <?php echo basename($artworkFile); ?>
                </div>
            <?php else: ?>
                <div class="no-file">⚠️ No artwork file uploaded yet</div>
            <?php endif; ?>
        </div>

        <!-- Notes / Special Instructions -->
        <div class="notes-section">
            <h5>Notes & Special Instructions</h5>
            <div class="notes-content">
                <?php if ($notes): ?>
                    <?php echo $notes; ?>
                <?php else: ?>
                    <span class="no-notes">No special instructions</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="section">
            <div class="info-row" style="font-size: 18px;">
                <span class="info-label">Total Amount:</span>
                <span class="info-value" style="font-weight: bold; color: #d32f2f; font-size: 20px;">₱<?php echo $totalAmount; ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <p><strong>PrintPro Job Ticket System</strong></p>
            <p>Generated by Admin on <?php echo $generatedTime; ?></p>
            <p style="margin-top: 10px; font-size: 10px;">For production floor use only. Please verify all specifications before proceeding with production.</p>
        </div>
    </div>
</body>

</html>
