<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Access Control
require_role('customer');

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['user_name'];

// Fetch latest order for tracking stepper
$stmt = $pdo->prepare("
    SELECT o.*, oi.quantity, oi.price as item_price, p.name as product_name, p.image as product_image, b.business_name 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN businesses b ON p.business_id = b.id
    WHERE o.customer_id = ?
    ORDER BY o.id DESC
    LIMIT 1
");
$stmt->execute([$customer_id]);
$latest_order = $stmt->fetch();

// Fetch summary metrics
// 1. Active orders (pending, confirmed, processed, in_transit)
$active_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND order_status NOT IN ('delivered', 'cancelled')");
$active_stmt->execute([$customer_id]);
$active_orders_count = $active_stmt->fetch()['count'] ?? 0;

// 2. Total spent / saved (simulate total saved as 10% of total spent)
$spent_stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE customer_id = ? AND payment_status = 'paid'");
$spent_stmt->execute([$customer_id]);
$total_spent = $spent_stmt->fetch()['total'] ?? 0;
$total_saved = $total_spent * 0.10; // 10% savings

// 3. Wishlist count
$wish_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?");
$wish_stmt->execute([$customer_id]);
$wishlist_count = $wish_stmt->fetch()['count'] ?? 0;

// Fetch recommended products
$rec_stmt = $pdo->query("
    SELECT p.*, b.business_name 
    FROM products p
    JOIN businesses b ON p.business_id = b.id
    WHERE p.status = 'active'
    ORDER BY RAND()
    LIMIT 4
");
$recommended_products = $rec_stmt->fetchAll();

// Fetch order history
$history_stmt = $pdo->prepare("
    SELECT o.*, GROUP_CONCAT(b.business_name SEPARATOR ', ') as vendors
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN businesses b ON p.business_id = b.id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.id DESC
");
$history_stmt->execute([$customer_id]);
$order_history = $history_stmt->fetchAll();

$page_title = 'Customer Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout-wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Customer Portal</h4>
                <small class="text-muted">Welcome back, <?php echo sanitize($customer_name); ?></small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="/ecommerce-system/shop.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Order</a>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 600;">
                    <?php echo strtoupper(substr($customer_name, 0, 2)); ?>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Widgets -->
        <div class="row g-4 mb-5">
            <!-- Stepper tracking / Recent order -->
            <div class="col-lg-8">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-4">Track Recent Order</h5>
                    
                    <?php if ($latest_order): 
                        $status = $latest_order['order_status'];
                        $steps = ['pending' => 1, 'confirmed' => 2, 'processed' => 3, 'in_transit' => 4, 'delivered' => 5];
                        $current_step = $steps[$status] ?? 1;
                        $progress_pct = (($current_step - 1) / 4) * 100;
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-semibold text-secondary">Order #LT-<?php echo str_pad($latest_order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <span class="badge badge-status status-<?php echo $status; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                        </div>

                        <!-- Stepper Component -->
                        <div class="stepper-container mb-4">
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

                        <!-- Item details in tracking -->
                        <div class="border rounded p-3 d-flex align-items-center gap-3">
                            <div style="width: 70px; height: 70px; background-color: #f8fafc; overflow: hidden; border-radius: 8px;">
                                <img src="<?php echo !empty($latest_order['product_image']) ? '/ecommerce-system/assets/uploads/' . $latest_order['product_image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100&auto=format&fit=crop&q=80'; ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo sanitize($latest_order['product_name']); ?></h6>
                                <p class="text-muted small mb-0">Workshop: <?php echo sanitize($latest_order['business_name']); ?></p>
                                <span class="fw-bold text-primary"><?php echo format_price($latest_order['total_amount']); ?></span>
                            </div>
                            <div class="ms-auto text-end">
                                <small class="text-muted d-block">Est. Delivery</small>
                                <span class="fw-semibold small"><?php echo date('M d, Y', strtotime($latest_order['created_at'] . ' + 3 days')); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bag-x fs-1 text-muted"></i>
                            <h6 class="fw-bold mt-3">No active orders</h6>
                            <p class="text-muted small">You haven't placed any orders yet.</p>
                            <a href="/ecommerce-system/shop.php" class="btn btn-primary btn-sm">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Sidebar: Local Rewards & Summary -->
            <div class="col-lg-4 d-flex flex-column justify-content-between gap-4">
                <!-- Local rewards -->
                <div class="card border-0 bg-primary text-white p-4 rounded-3 shadow-sm">
                    <h5 class="fw-bold mb-2">LOCAL REWARDS</h5>
                    <h3 class="fw-bold mb-3"><?php echo number_format($total_spent * 10); ?> <span class="fs-6 fw-normal">pts</span></h3>
                    <p class="small text-white-50 mb-0">You're only a few points away from a $10 local discount voucher!</p>
                </div>
                
                <!-- Quick Summary metrics -->
                <div class="card border rounded-3 p-4 bg-white shadow-sm flex-grow-1">
                    <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2">Quick Summary</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Active Orders</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $active_orders_count; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Total Saved</span>
                        <span class="fw-semibold text-success"><?php echo format_price($total_saved); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Wishlist Items</span>
                        <span class="badge bg-secondary rounded-pill"><?php echo $wishlist_count; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommended section -->
        <h5 class="fw-bold mb-4">Recommended for You</h5>
        <div class="row g-4 mb-5">
            <?php foreach ($recommended_products as $rec_prod): 
                $rec_img = !empty($rec_prod['image']) ? '/ecommerce-system/assets/uploads/' . $rec_prod['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&auto=format&fit=crop&q=80';
            ?>
                <div class="col-md-3 col-sm-6">
                    <div class="product-card shadow-sm">
                        <div class="product-img-wrapper" style="padding-top: 65%;">
                            <img src="<?php echo $rec_img; ?>" alt="">
                        </div>
                        <div class="product-details p-3">
                            <h6 class="fw-bold text-dark mb-1 text-truncate">
                                <a href="/ecommerce-system/product-details.php?id=<?php echo $rec_prod['id']; ?>" class="text-decoration-none text-dark"><?php echo sanitize($rec_prod['name']); ?></a>
                            </h6>
                            <small class="text-muted d-block mb-2 text-truncate"><?php echo sanitize($rec_prod['business_name']); ?></small>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary"><?php echo format_price($rec_prod['price']); ?></span>
                                <a href="/ecommerce-system/product-details.php?id=<?php echo $rec_prod['id']; ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-cart"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Order history table -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Order History</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover m-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Vendor(s)</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($order_history) > 0): ?>
                        <?php foreach ($order_history as $hist): ?>
                            <tr>
                                <td class="fw-semibold">#LT-<?php echo str_pad($hist['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo sanitize($hist['vendors']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($hist['created_at'])); ?></td>
                                <td><span class="badge badge-status status-<?php echo $hist['order_status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $hist['order_status'])); ?></span></td>
                                <td class="fw-bold"><?php echo format_price($hist['total_amount']); ?></td>
                                <td class="text-end">
                                    <a href="/ecommerce-system/customer/orders/index.php" class="btn btn-sm btn-outline-secondary">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No orders found in your history.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
