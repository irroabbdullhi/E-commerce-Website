<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$message = '';
$message_type = 'success';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $order_id = (int)$_POST['order_id'];
        $new_status = sanitize($_POST['order_status']);
        $new_pay_status = sanitize($_POST['payment_status']);

        $upd = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
        if ($upd->execute([$new_status, $new_pay_status, $order_id])) {
            if ($new_pay_status === 'paid') {
                $upd_pay = $pdo->prepare("UPDATE payments SET payment_status = 'paid' WHERE order_id = ?");
                $upd_pay->execute([$order_id]);
            }
            $message = 'Order #' . $order_id . ' updated successfully!';
        }
    }
}

// Fetch all orders
$stmt = $pdo->query("
    SELECT o.*, u.full_name as customer_name, p_pay.payment_method 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    LEFT JOIN payments p_pay ON o.id = p_pay.order_id 
    ORDER BY o.id DESC
");
$orders = $stmt->fetchAll();

// Detail View Setup
$detail_order = null;
$detail_items = [];
if (isset($_GET['detail_id'])) {
    $detail_id = (int)$_GET['detail_id'];
    
    $det_stmt = $pdo->prepare("
        SELECT o.*, u.full_name as customer_name, u.email as customer_email, p_pay.payment_method, p_pay.transaction_reference 
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN payments p_pay ON o.id = p_pay.order_id
        WHERE o.id = ?
    ");
    $det_stmt->execute([$detail_id]);
    $detail_order = $det_stmt->fetch();

    if ($detail_order) {
        $items_stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, b.business_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            JOIN businesses b ON p.business_id = b.id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$detail_id]);
        $detail_items = $items_stmt->fetchAll();
    }
}

$page_title = 'Platform Orders';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Platform Orders</h4>
                <small class="text-muted">Monitor and adjust all platform orders</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Orders List Table -->
            <div class="<?php echo $detail_order ? 'col-lg-6' : 'col-12'; ?>">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Orders Log</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr class="<?php echo ($detail_order && $detail_order['id'] == $ord['id']) ? 'table-primary' : ''; ?>">
                                            <td class="fw-semibold">#LT-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo sanitize($ord['customer_name']); ?></td>
                                            <td style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></td>
                                            <td>
                                                <span class="badge badge-status status-<?php echo $ord['order_status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ord['order_status'])); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold"><?php echo format_price($ord['total_amount']); ?></td>
                                            <td class="text-end">
                                                <a href="index.php?detail_id=<?php echo $ord['id']; ?>" class="btn btn-sm btn-primary">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No orders found on the platform.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Details Panel & Edit Form -->
            <?php if ($detail_order): ?>
                <div class="col-lg-6">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h5 class="fw-bold m-0">Order #LT-<?php echo str_pad($detail_order['id'], 5, '0', STR_PAD_LEFT); ?> Details</h5>
                            <a href="index.php" class="btn-close" aria-label="Close"></a>
                        </div>

                        <!-- Customer info -->
                        <div class="mb-3 small">
                            <h6 class="fw-bold text-secondary mb-1">Customer Info</h6>
                            <div><strong>Name:</strong> <?php echo sanitize($detail_order['customer_name']); ?></div>
                            <div><strong>Email:</strong> <?php echo sanitize($detail_order['customer_email']); ?></div>
                        </div>

                        <!-- Items details -->
                        <h6 class="fw-bold text-secondary small mb-2">Order Items</h6>
                        <div class="mb-4">
                            <?php foreach ($detail_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <div>
                                        <h6 class="fw-semibold mb-0 small"><?php echo sanitize($item['product_name']); ?></h6>
                                        <small class="text-muted">Shop: <?php echo sanitize($item['business_name']); ?> | Qty: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span class="fw-semibold small"><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="d-flex justify-content-between align-items-center mt-2 fw-bold text-primary">
                                <span>Grand Total:</span>
                                <span><?php echo format_price($detail_order['total_amount']); ?></span>
                            </div>
                        </div>

                        <!-- Update Form -->
                        <h6 class="fw-bold text-secondary border-top pt-3 small">Override Status</h6>
                        <form action="index.php?detail_id=<?php echo $detail_order['id']; ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            <input type="hidden" name="order_id" value="<?php echo $detail_order['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="order_status" class="form-label small">Shipping Status</label>
                                <select class="form-select form-select-sm" name="order_status" id="order_status">
                                    <option value="pending" <?php echo $detail_order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $detail_order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="processed" <?php echo $detail_order['order_status'] === 'processed' ? 'selected' : ''; ?>>Processed</option>
                                    <option value="in_transit" <?php echo $detail_order['order_status'] === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="delivered" <?php echo $detail_order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $detail_order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="payment_status" class="form-label small">Payment Status</label>
                                <select class="form-select form-select-sm" name="payment_status" id="payment_status">
                                    <option value="pending" <?php echo $detail_order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $detail_order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="failed" <?php echo $detail_order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $detail_order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>

                            <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">Update Order</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
