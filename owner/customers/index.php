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

// Fetch all customers who have ordered from this business
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email, u.created_at as registered_date,
           COUNT(o.id) as total_orders, 
           SUM(oi.quantity * oi.price) as total_spent
    FROM users u
    JOIN orders o ON u.id = o.customer_id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.business_id = ?
    GROUP BY u.id
    ORDER BY total_spent DESC
");
$stmt->execute([$business_id]);
$customers = $stmt->fetchAll();

$page_title = 'My Customers';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">My Customers</h4>
                <small class="text-muted">Customers who have ordered from your storefront</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <div class="card border rounded-3 p-4 bg-white shadow-sm">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Customer List</h5>
            
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Email Address</th>
                            <th>Total Orders Placed</th>
                            <th>Total Spent in Store</th>
                            <th>First Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($customers) > 0): ?>
                            <?php foreach ($customers as $cust): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 600; font-size: 0.8rem;">
                                                <?php echo strtoupper(substr($cust['full_name'], 0, 2)); ?>
                                            </div>
                                            <span class="fw-bold small"><?php echo sanitize($cust['full_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo sanitize($cust['email']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary rounded-pill"><?php echo $cust['total_orders']; ?> orders</span>
                                    </td>
                                    <td class="fw-bold text-success"><?php echo format_price($cust['total_spent']); ?></td>
                                    <td style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($cust['registered_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No customers have purchased from your storefront yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
