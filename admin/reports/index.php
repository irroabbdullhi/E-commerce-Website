<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

// 1. Gross Merchandise Volume (GMV)
$gmv_stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'");
$gmv = $gmv_stmt->fetchColumn() ?? 0;

// 2. Total Paid Orders
$cnt_stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'");
$paid_orders = $cnt_stmt->fetchColumn() ?? 0;

// 3. Average Order Value (AOV)
$aov = $paid_orders > 0 ? ($gmv / $paid_orders) : 0;

// 4. Sales distribution by Category
$cat_stmt = $pdo->query("
    SELECT c.category_name, SUM(oi.quantity) as units_sold, SUM(oi.quantity * oi.price) as gross_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    GROUP BY c.id
    ORDER BY gross_revenue DESC
");
$category_stats = $cat_stmt->fetchAll();

// 5. Vendor Sales leaderboard
$vend_stmt = $pdo->query("
    SELECT b.business_name, u.full_name as owner_name, COUNT(DISTINCT o.id) as orders_count, SUM(oi.quantity * oi.price) as vendor_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN businesses b ON p.business_id = b.id
    JOIN users u ON b.owner_id = u.id
    JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    GROUP BY b.id
    ORDER BY vendor_revenue DESC
");
$vendor_leaderboard = $vend_stmt->fetchAll();

$page_title = 'Platform Analytics';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Platform Analytics</h4>
                <small class="text-muted">High-level sales reports and leaderboard data</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <!-- Metric widgets -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Gross Merchandise Volume (GMV)</h6>
                    <h2 class="fw-bold text-primary mb-0"><?php echo format_price($gmv); ?></h2>
                    <small class="text-muted">Cumulative paid orders total volume.</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Average Order Value (AOV)</h6>
                    <h2 class="fw-bold text-success mb-0"><?php echo format_price($aov); ?></h2>
                    <small class="text-muted">Mean spending per completed order transaction.</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Total Volume</h6>
                    <h2 class="fw-bold text-dark mb-0"><?php echo number_format($paid_orders); ?></h2>
                    <small class="text-muted">Number of paid transactions processed.</small>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Category Share -->
            <div class="col-lg-6">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Sales by Category</h5>
                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Units Sold</th>
                                    <th>Gross Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($category_stats) > 0): ?>
                                    <?php foreach ($category_stats as $c_stat): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo sanitize($c_stat['category_name']); ?></td>
                                            <td><?php echo $c_stat['units_sold']; ?> items</td>
                                            <td class="fw-bold text-primary"><?php echo format_price($c_stat['gross_revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted">No sales activity to categorize yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Seller Leaderboard -->
            <div class="col-lg-6">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Local Sellers Leaderboard</h5>
                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead>
                                <tr>
                                    <th>Business Name</th>
                                    <th>Owner</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($vendor_leaderboard) > 0): ?>
                                    <?php foreach ($vendor_leaderboard as $vend): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo sanitize($vend['business_name']); ?></td>
                                            <td><?php echo sanitize($vend['owner_name']); ?></td>
                                            <td><?php echo $vend['orders_count']; ?></td>
                                            <td class="fw-bold text-success"><?php echo format_price($vend['vendor_revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">No vendors have processed sales yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
