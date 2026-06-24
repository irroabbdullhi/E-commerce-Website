<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$message = '';
$message_type = 'success';

// Handle payment status override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $payment_id = (int)$_POST['payment_id'];
        $new_status = sanitize($_POST['status']);

        // Fetch order associated with payment to sync order payment status
        $stmt = $pdo->prepare("SELECT order_id FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $order_id = $stmt->fetchColumn();

        if ($order_id) {
            $pdo->beginTransaction();
            try {
                // Update payment status
                $upd1 = $pdo->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
                $upd1->execute([$new_status, $payment_id]);

                // Update order payment status to match
                $upd2 = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
                $upd2->execute([$new_status, $order_id]);

                $pdo->commit();
                $message = 'Payment status updated successfully!';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Error updating payment: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Fetch payments log
$stmt = $pdo->query("
    SELECT p.*, o.customer_id, u.full_name as customer_name 
    FROM payments p 
    JOIN orders o ON p.order_id = o.id 
    JOIN users u ON o.customer_id = u.id 
    ORDER BY p.id DESC
");
$payments = $stmt->fetchAll();

$page_title = 'Platform Payments';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Platform Payments</h4>
                <small class="text-muted">Review and audit financial transactions</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border rounded-3 p-4 bg-white shadow-sm">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Transaction History</h5>
            
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Gateway / Method</th>
                            <th>Reference Code</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 250px;">Verify Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payments) > 0): ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td class="fw-semibold">#TXN-<?php echo str_pad($pay['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td>#LT-<?php echo str_pad($pay['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="fw-semibold small"><?php echo sanitize($pay['customer_name']); ?></div>
                                    </td>
                                    <td class="fw-bold"><?php echo format_price($pay['amount']); ?></td>
                                    <td><?php echo sanitize($pay['payment_method']); ?></td>
                                    <td class="text-uppercase" style="font-size: 0.85rem; font-family: monospace;"><?php echo sanitize($pay['transaction_reference']); ?></td>
                                    <td>
                                        <span class="badge badge-status status-<?php echo $pay['payment_status']; ?>">
                                            <?php echo ucfirst($pay['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form action="index.php" method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                                <option value="pending" <?php echo $pay['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $pay['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $pay['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="refunded" <?php echo $pay['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                            <button type="submit" name="update_payment" class="btn btn-primary btn-sm">Set</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No transactions recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
