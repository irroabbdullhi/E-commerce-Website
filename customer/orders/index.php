<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('customer');

$customer_id = $_SESSION['user_id'];
$success_msg = isset($_GET['success']) ? 'Your order was successfully placed! Thank you for buying local.' : '';

// Fetch all orders
$orders_stmt = $pdo->prepare("
    SELECT o.*, p.payment_method, p.transaction_reference, p.payment_status as pay_status 
    FROM orders o 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE o.customer_id = ? 
    ORDER BY o.id DESC
");
$orders_stmt->execute([$customer_id]);
$orders = $orders_stmt->fetchAll();

// Detail view
$detail_order = null;
$detail_items = [];
if (isset($_GET['detail_id'])) {
    $detail_id = (int)$_GET['detail_id'];
    
    // Fetch order details
    $det_stmt = $pdo->prepare("
        SELECT o.*, p.payment_method, p.transaction_reference, p.payment_status as pay_status 
        FROM orders o 
        LEFT JOIN payments p ON o.id = p.order_id 
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $det_stmt->execute([$detail_id, $customer_id]);
    $detail_order = $det_stmt->fetch();

    if ($detail_order) {
        // Fetch order items
        $items_stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image, b.business_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            JOIN businesses b ON p.business_id = b.id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->execute([$detail_id]);
        $detail_items = $items_stmt->fetchAll();
    }
}

$page_title = 'My Orders';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">My Orders</h4>
                <small class="text-muted">Track and review your purchases</small>
            </div>
            <a href="/ecommerce-system/customer/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Order List Column -->
            <div class="<?php echo $detail_order ? 'col-lg-6' : 'col-12'; ?>">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Order History</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
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
                                            <td style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></td>
                                            <td>
                                                <span class="badge badge-status status-<?php echo $ord['order_status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ord['order_status'])); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold"><?php echo format_price($ord['total_amount']); ?></td>
                                            <td class="text-end">
                                                <a href="index.php?detail_id=<?php echo $ord['id']; ?>" class="btn btn-sm btn-primary">Track</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">You have not placed any orders yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tracker / Detail Column -->
            <?php if ($detail_order): 
                $status = $detail_order['order_status'];
                $steps = ['pending' => 1, 'confirmed' => 2, 'processed' => 3, 'in_transit' => 4, 'delivered' => 5];
                $current_step = $steps[$status] ?? 1;
                $progress_pct = (($current_step - 1) / 4) * 100;
            ?>
                <div class="col-lg-6">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                            <h5 class="fw-bold m-0">Tracking Order #LT-<?php echo str_pad($detail_order['id'], 5, '0', STR_PAD_LEFT); ?></h5>
                            <a href="index.php" class="btn-close" aria-label="Close" title="Hide details"></a>
                        </div>

                        <!-- Stepper Tracker -->
                        <div class="stepper-container mb-5">
                            <div class="stepper-progress" style="width: <?php echo $progress_pct; ?>%;"></div>
                            <div class="step-item <?php echo $current_step >= 1 ? 'completed' : ''; ?> <?php echo $current_step == 1 ? 'active' : ''; ?>">
                                <div class="step-circle"><i class="bi bi-clock"></i></div>
                                <div class="step-label">Placed</div>
                            </div>
                            <div class="step-item <?php echo $current_step >= 2 ? 'completed' : ''; ?> <?php echo $current_step == 2 ? 'active' : ''; ?>">
                                <div class="step-circle"><i class="bi bi-check-lg"></i></div>
                                <div class="step-label">Confirmed</div>
                            </div>
                            <div class="step-item <?php echo $current_step >= 3 ? 'completed' : ''; ?> <?php echo $current_step == 3 ? 'active' : ''; ?>">
                                <div class="step-circle"><i class="bi bi-gear-fill"></i></div>
                                <div class="step-label">Processed</div>
                            </div>
                            <div class="step-item <?php echo $current_step >= 4 ? 'completed' : ''; ?> <?php echo $current_step == 4 ? 'active' : ''; ?>">
                                <div class="step-circle"><i class="bi bi-truck"></i></div>
                                <div class="step-label">In Transit</div>
                            </div>
                            <div class="step-item <?php echo $current_step >= 5 ? 'completed' : ''; ?> <?php echo $current_step == 5 ? 'active' : ''; ?>">
                                <div class="step-circle"><i class="bi bi-house-door-fill"></i></div>
                                <div class="step-label">Delivered</div>
                            </div>
                        </div>

                        <!-- Items Details -->
                        <h6 class="fw-bold mb-3 text-secondary">Items Ordered</h6>
                        <div class="mb-4">
                            <?php foreach ($detail_items as $item): 
                                $img_src = !empty($item['image']) ? '/ecommerce-system/assets/uploads/' . $item['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100&auto=format&fit=crop&q=80';
                            ?>
                                <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
                                    <div style="width: 50px; height: 50px; background-color: #f8fafc; border-radius: 6px; overflow: hidden;" class="flex-shrink-0">
                                        <img src="<?php echo $img_src; ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-semibold mb-0 small"><?php echo sanitize($item['product_name']); ?></h6>
                                        <small class="text-muted">Seller: <?php echo sanitize($item['business_name']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="d-block fw-semibold small"><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                                        <small class="text-muted"><?php echo $item['quantity']; ?> unit(s)</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Invoice Total -->
                        <div class="card bg-light border-0 p-3 mb-3">
                            <div class="row g-2 small mb-1">
                                <div class="col-6 text-muted">Subtotal:</div>
                                <div class="col-6 text-end fw-semibold"><?php echo format_price($detail_order['total_amount'] / 1.08 - 5); // Rough estimation ?></div>
                            </div>
                            <div class="row g-2 small mb-1">
                                <div class="col-6 text-muted">Payment Method:</div>
                                <div class="col-6 text-end fw-semibold"><?php echo sanitize($detail_order['payment_method'] ?? 'COD'); ?></div>
                            </div>
                            <div class="row g-2 small mb-1">
                                <div class="col-6 text-muted">Transaction Reference:</div>
                                <div class="col-6 text-end fw-semibold text-uppercase"><?php echo sanitize($detail_order['transaction_reference'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="row g-2 fs-6 fw-bold border-top pt-2 mt-2">
                                <div class="col-6">Total Amount:</div>
                                <div class="col-6 text-end text-primary"><?php echo format_price($detail_order['total_amount']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
