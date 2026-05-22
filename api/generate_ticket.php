<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Require valid session (any logged-in user)
if (!isset($_SESSION['user_id'])) {
    showErrorPage('Unauthorized', 'You must be logged in to view job tickets.');
    exit;
}

$order_id = $_GET['order_id'] ?? 0;
if (!$order_id) {
    showErrorPage('Missing Order ID', 'Order ID parameter is required.');
    exit;
}

// Create job_tickets_log table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS job_tickets_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            generated_by_user_id INT NOT NULL,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
} catch (PDOException $e) {
    // Continue even if table creation fails
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as client_name, u.email as client_email, c.business_name
        FROM orders o
        JOIN clients c ON o.client_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        showErrorPage('Order Not Found', "Order #{$order_id} could not be found in the database.");
        exit;
    }

    // Log ticket generation
    try {
        $logStmt = $pdo->prepare("INSERT INTO job_tickets_log (order_id, generated_by_user_id) VALUES (?, ?)");
        $logStmt->execute([$order_id, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Continue even if logging fails
    }

} catch (PDOException $e) {
    showErrorPage('Database Error', $e->getMessage());
    exit;
}

function showErrorPage($title, $message) {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error - Job Ticket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .error-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .error-message {
            color: #666;
            margin-bottom: 20px;
        }
        .back-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .back-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">⚠️</div>
        <div class="error-title">' . htmlspecialchars($title) . '</div>
        <div class="error-message">' . htmlspecialchars($message) . '</div>
        <a href="javascript:history.back()" class="back-btn">Go Back</a>
    </div>
</body>
</html>';
}

// Helper function for status color
function getStatusColor($status) {
    $colors = [
        'Proof Pending' => '#ffc107',
        'Proof Pending Review' => '#ffc107',
        'Prepress' => '#17a2b8',
        'Printing' => '#28a745',
        'Finishing' => '#28a745',
        'Shipping' => '#007bff',
        'Delivered' => '#6c757d',
        'Done' => '#6c757d',
        'Reprint' => '#dc3545'
    ];
    return $colors[$status] ?? '#6c757d';
}

$order_number = 'PPR-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$generated_date = date('F j, Y, g:i A');
$bleed_text = $order['bleed'] ? 'With Bleed (0.125")' : 'No Bleed';
$status_color = getStatusColor($order['status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Ticket #<?php echo $order_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            font-size: 14px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .print-btn:hover {
            background: #0056b3;
        }

        .ticket {
            background: white;
            max-width: 900px;
            margin: 0 auto;
            border: 3px solid #000;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header-left h1 {
            font-size: 28px;
            font-weight: 800;
            color: #000;
            margin-bottom: 5px;
        }

        .header-left .subtitle {
            font-size: 16px;
            color: #666;
            font-weight: 500;
        }

        .header-right {
            text-align: right;
        }

        .order-number {
            font-size: 48px;
            font-weight: 900;
            color: #000;
            line-height: 1;
            margin-bottom: 10px;
        }

        .confidential {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
        }

        .generated-date {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .field {
            margin-bottom: 10px;
        }

        .field-label {
            font-size: 12px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .field-value {
            font-size: 15px;
            font-weight: 600;
            color: #000;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 13px;
            color: white;
        }

        .artwork-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 2px dashed #ccc;
        }

        .artwork-warning {
            color: #dc3545;
            font-weight: 600;
            font-size: 15px;
        }

        .artwork-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .artwork-link:hover {
            text-decoration: underline;
        }

        .notes-section {
            background: #fff3cd;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ffc107;
        }

        .notes-text {
            font-style: italic;
            color: #856404;
        }

        .barcode-section {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .barcode {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 6px;
            color: #000;
            margin-bottom: 10px;
        }

        .barcode-label {
            font-size: 12px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .footer-line {
            margin-bottom: 5px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .ticket {
                border: 3px solid #000;
                box-shadow: none;
                max-width: 100%;
                padding: 20px;
            }

            .print-btn {
                display: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .confidential {
                background: #000 !important;
                color: white !important;
            }

            .status-badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">
            🖨️ Print This Ticket
        </button>
    </div>

    <div class="ticket">
        <!-- Header Section -->
        <div class="header">
            <div class="header-left">
                <h1>JOB TICKET</h1>
                <div class="subtitle">PrintPro Production</div>
                <div class="generated-date">Generated: <?php echo $generated_date; ?></div>
            </div>
            <div class="header-right">
                <div class="order-number">#<?php echo $order_number; ?></div>
                <div class="confidential">CONFIDENTIAL - PRODUCTION USE ONLY</div>
            </div>
        </div>

        <!-- Client Section -->
        <div class="section">
            <div class="section-title">CLIENT INFORMATION</div>
            <div class="grid">
                <div class="field">
                    <div class="field-label">Client Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['client_name']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Business Name</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['business_name']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Email</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['client_email']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Order Date</div>
                    <div class="field-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Due Date</div>
                    <div class="field-value"><?php echo date('F j, Y', strtotime($order['due_date'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Specifications Section -->
        <div class="section">
            <div class="section-title">SPECIFICATIONS</div>
            <div class="grid">
                <div class="field">
                    <div class="field-label">Product Type</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['product_type']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Quantity</div>
                    <div class="field-value"><?php echo number_format($order['quantity']); ?> units</div>
                </div>
                <div class="field">
                    <div class="field-label">Size</div>
                    <div class="field-value"><?php echo $order['size_width']; ?>" × <?php echo $order['size_height']; ?>"</div>
                </div>
                <div class="field">
                    <div class="field-label">Paper Stock</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['paper_weight']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Finishing</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['finish'] ?: 'Standard'); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Print Sides</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['print_sides'] ?: 'Single Side'); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Bleed</div>
                    <div class="field-value"><?php echo $bleed_text; ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Turnaround</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['turnaround']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Shipping</div>
                    <div class="field-value"><?php echo htmlspecialchars($order['shipping_method']); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Status</div>
                    <div class="field-value">
                        <span class="status-badge" style="background: <?php echo $status_color; ?>;">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Artwork Section -->
        <div class="section">
            <div class="section-title">ARTWORK</div>
            <div class="artwork-section">
                <?php if ($order['artwork_file']): ?>
                    <div class="field-value">
                        📎 <?php echo htmlspecialchars(basename($order['artwork_file'])); ?>
                        <br>
                        <a href="../<?php echo htmlspecialchars($order['artwork_file']); ?>" class="artwork-link" target="_blank">
                            Download File
                        </a>
                    </div>
                <?php else: ?>
                    <div class="artwork-warning">⚠️ No artwork uploaded yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Financials Section -->
        <div class="section">
            <div class="section-title">FINANCIALS</div>
            <div class="grid">
                <div class="field">
                    <div class="field-label">Unit Price</div>
                    <div class="field-value">₱<?php echo number_format($order['unit_price'] ?? 0, 2); ?></div>
                </div>
                <div class="field">
                    <div class="field-label">Tax Rate</div>
                    <div class="field-value"><?php echo ($order['tax_rate'] ?? 0); ?>%</div>
                </div>
                <div class="field">
                    <div class="field-label">Total Amount</div>
                    <div class="field-value" style="font-size: 18px; color: #007bff;">
                        ₱<?php echo number_format($order['total_amount'] ?? $order['total_price'] ?? 0, 2); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="section">
            <div class="section-title">NOTES</div>
            <div class="notes-section">
                <?php if ($order['notes']): ?>
                    <div class="notes-text"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                <?php else: ?>
                    <div class="notes-text">No special instructions</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Barcode Section -->
        <div class="barcode-section">
            <div class="barcode">*<?php echo $order_number; ?>*</div>
            <div class="barcode-label">SCAN TO UPDATE PRODUCTION STATUS</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-line">Generated by: <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?> on <?php echo $generated_date; ?></div>
            <div class="footer-line">PrintPro Commercial Printing System</div>
        </div>
    </div>
</body>
</html>