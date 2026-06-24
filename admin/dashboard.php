<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$admin_name = $_SESSION['user_name'];

// Fetch Real-time metrics
// 1. Total Revenue
$rev_stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'");
$total_revenue = $rev_stmt->fetchColumn() ?? 0;

// 2. Active Sellers
$sellers_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner' AND status = 'active'");
$active_sellers = $sellers_stmt->fetchColumn() ?? 0;

// 3. Total Customers
$cust_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'");
$total_customers = $cust_stmt->fetchColumn() ?? 0;

// 4. Daily Orders (all time orders in this system for demo simplicity)
$orders_count_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $orders_count_stmt->fetchColumn() ?? 0;

// Top Sellers
$top_sellers_stmt = $pdo->query("
    SELECT b.business_name, SUM(oi.quantity) as total_sales, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN businesses b ON p.business_id = b.id
    GROUP BY b.id
    ORDER BY revenue DESC
    LIMIT 3
");
$top_sellers = $top_sellers_stmt->fetchAll();

// Recent Orders
$recent_orders_stmt = $pdo->query("
    SELECT o.*, u.full_name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    ORDER BY o.id DESC 
    LIMIT 5
");
$recent_orders = $recent_orders_stmt->fetchAll();

$page_title = 'Admin Dashboard';
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
                <h4 class="fw-bold m-0">Platform Overview</h4>
                <small class="text-muted">Good morning, monitoring LocalTrade performance today.</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3 me-1"></i>Last 30 Days</button>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 600;">
                    AD
                </div>
            </div>
        </div>

        <!-- Admin Stats widgets -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Revenue</small>
                            <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo format_price($total_revenue); ?></h4>
                        </div>
                        <div class="stat-icon bg-primary-light text-primary"><i class="bi bi-wallet2"></i></div>
                    </div>
                    <span class="stat-badge bg-success-light text-success"><i class="bi bi-arrow-up-right me-1"></i>+12.5%</span>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Active Sellers</small>
                            <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo number_format($active_sellers); ?></h4>
                        </div>
                        <div class="stat-icon bg-success-light text-success"><i class="bi bi-shop"></i></div>
                    </div>
                    <span class="stat-badge bg-success-light text-success"><i class="bi bi-arrow-up-right me-1"></i>+4.2%</span>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Customers</small>
                            <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo number_format($total_customers); ?></h4>
                        </div>
                        <div class="stat-icon bg-info-light text-info"><i class="bi bi-people"></i></div>
                    </div>
                    <span class="stat-badge bg-secondary-light text-secondary">Stable</span>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Orders</small>
                            <h4 class="fw-bold text-dark mt-1 mb-0"><?php echo $total_orders; ?></h4>
                        </div>
                        <div class="stat-icon bg-danger-light text-danger"><i class="bi bi-cart"></i></div>
                    </div>
                    <span class="stat-badge bg-danger-light text-danger"><i class="bi bi-arrow-down-right me-1"></i>-2.1%</span>
                </div>
            </div>
        </div>

        <!-- Sales Analytics and Top Sellers -->
        <div class="row g-4 mb-5">
            <!-- Simulated analytics chart -->
            <div class="col-lg-8">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold m-0">Sales Analytics</h5>
                            <small class="text-muted">Real-time platform performance monitoring</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-primary active">Revenue</button>
                            <button class="btn btn-outline-secondary">Volume</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end px-3" style="height: 250px;">
                        <div class="d-flex flex-column align-items-center" style="width: 12%;">
                            <div class="bg-primary opacity-25 w-100 rounded-top" style="height: 90px;"></div>
                            <small class="text-muted mt-2" style="font-size: 0.75rem;">Week 1</small>
                        </div>
                        <div class="d-flex flex-column align-items-center" style="width: 12%;">
                            <div class="bg-primary opacity-50 w-100 rounded-top" style="height: 150px;"></div>
                            <small class="text-muted mt-2" style="font-size: 0.75rem;">Week 2</small>
                        </div>
                        <div class="d-flex flex-column align-items-center" style="width: 12%;">
                            <div class="bg-primary opacity-25 w-100 rounded-top" style="height: 120px;"></div>
                            <small class="text-muted mt-2" style="font-size: 0.75rem;">Week 3</small>
                        </div>
                        <div class="d-flex flex-column align-items-center" style="width: 12%;">
                            <div class="bg-primary w-100 rounded-top" style="height: 210px;"></div>
                            <small class="text-muted mt-2" style="font-size: 0.75rem;">Week 4</small>
                        </div>
                        <div class="d-flex flex-column align-items-center" style="width: 12%;">
                            <div class="bg-primary opacity-75 w-100 rounded-top" style="height: 170px;"></div>
                            <small class="text-muted mt-2" style="font-size: 0.75rem;">Week 5</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Sellers list -->
            <div class="col-lg-4">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">Top Sellers</h5>
                    <div class="top-sellers-list">
                        <?php if (count($top_sellers) > 0): ?>
                            <?php foreach ($top_sellers as $seller): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-semibold mb-0 small"><?php echo sanitize($seller['business_name']); ?></h6>
                                        <small class="text-muted"><?php echo $seller['total_sales']; ?> orders completed</small>
                                    </div>
                                    <span class="fw-bold text-primary small"><?php echo format_price($seller['revenue']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small text-center my-5">No seller activity recorded.</p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-auto border-top pt-3 text-center">
                        <a href="/ecommerce-system/admin/businesses/index.php" class="btn btn-sm btn-outline-secondary w-100">View All Sellers</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders table -->
        <h5 class="fw-bold mb-3">Recent Orders</h5>
        <div class="table-responsive">
            <table class="table table-hover m-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_orders) > 0): ?>
                        <?php foreach ($recent_orders as $ord): ?>
                            <tr>
                                <td class="fw-semibold">#LT-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo sanitize($ord['customer_name']); ?></td>
                                <td>
                                    <span class="badge badge-status status-<?php echo $ord['order_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ord['order_status'])); ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></td>
                                <td class="fw-bold"><?php echo format_price($ord['total_amount']); ?></td>
                                <td class="text-end">
                                    <a href="/ecommerce-system/admin/orders/index.php" class="btn btn-sm btn-light" title="Manage Order"><i class="bi bi-pencil-fill"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No orders received on the platform yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
