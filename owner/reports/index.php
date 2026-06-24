<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('owner');

$business_id = $_SESSION['business_id'];
if (!$business_id) {
    header("Location: ../dashboard.php");
    exit;
}

// Fetch general report statistics
// 1. Total revenue
$rev_stmt = $pdo->prepare("
    SELECT SUM(oi.quantity * oi.price) 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE p.business_id = ? AND o.payment_status = 'paid'
");
$rev_stmt->execute([$business_id]);
$total_revenue = $rev_stmt->fetchColumn() ?? 0;

// 2. Average rating of products
$rat_stmt = $pdo->prepare("
    SELECT AVG(r.rating) as avg_rating, COUNT(r.id) as total_revs
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    WHERE p.business_id = ?
");
$rat_stmt->execute([$business_id]);
$rating_res = $rat_stmt->fetch();
$avg_rating = $rating_res['avg_rating'] ? round($rating_res['avg_rating'], 1) : 0;
$total_reviews = $rating_res['total_revs'] ?? 0;

// Fetch sales breakdown by product
$sales_stmt = $pdo->prepare("
    SELECT p.name, p.price, p.stock, SUM(oi.quantity) as units_sold, SUM(oi.quantity * oi.price) as gross_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
    WHERE p.business_id = ?
    GROUP BY p.id
    ORDER BY units_sold DESC
");
$sales_stmt->execute([$business_id]);
$product_reports = $sales_stmt->fetchAll();

// Fetch reviews list for feedback
$rev_stmt = $pdo->prepare("
    SELECT r.*, p.name as product_name, u.full_name as customer_name
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.customer_id = u.id
    WHERE p.business_id = ?
    ORDER BY r.id DESC
");
$rev_stmt->execute([$business_id]);
$reviews = $rev_stmt->fetchAll();

$page_title = 'Sales Reports';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Reports & Analytics</h4>
                <small class="text-muted">Analyze your store performance and customer feedback</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <div class="row g-4 mb-5">
            <!-- Revenue card -->
            <div class="col-md-6">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h6 class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Gross Revenue Earned</h6>
                    <h2 class="fw-bold text-primary mt-2 mb-0"><?php echo format_price($total_revenue); ?></h2>
                    <p class="text-muted small mt-2 m-0">This represents all successful orders paid by customers in your store.</p>
                </div>
            </div>

            <!-- Review Card -->
            <div class="col-md-6">
                <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                    <h6 class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Customer Satisfaction</h6>
                    <h2 class="fw-bold text-warning mt-2 mb-0">
                        <?php echo $avg_rating; ?> <span class="fs-6 text-muted">/ 5.0</span>
                    </h2>
                    <p class="text-muted small mt-2 m-0">Based on <?php echo $total_reviews; ?> customer review comments left on your items.</p>
                </div>
            </div>
        </div>

        <!-- Product Performance Table -->
        <div class="card border rounded-3 p-4 bg-white shadow-sm mb-5">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Sales Breakdown by Product</h5>
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Unit Price</th>
                            <th>Current Stock</th>
                            <th>Units Sold</th>
                            <th>Gross Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product_reports as $report): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo sanitize($report['name']); ?></td>
                                <td><?php echo format_price($report['price']); ?></td>
                                <td><?php echo $report['stock']; ?> units</td>
                                <td><?php echo $report['units_sold'] ?? 0; ?></td>
                                <td class="fw-bold text-success"><?php echo format_price($report['gross_revenue'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Feedback list -->
        <div class="card border rounded-3 p-4 bg-white shadow-sm">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Latest Customer Feedback</h5>
            <div class="reviews-list">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <h6 class="fw-bold mb-0 small"><?php echo sanitize($rev['customer_name']); ?></h6>
                                <span class="text-muted small"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                            </div>
                            <div class="text-warning mb-1" style="font-size: 0.8rem;">
                                <?php echo render_stars($rev['rating']); ?>
                            </div>
                            <p class="small text-muted mb-1"><strong>Item:</strong> <?php echo sanitize($rev['product_name']); ?></p>
                            <p class="small m-0">"<?php echo sanitize($rev['comment']); ?>"</p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small text-center my-4">No reviews have been left for your products yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
