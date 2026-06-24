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

$message = '';
$message_type = 'success';

// Handle Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $product_id = (int)$_POST['product_id'];
        $new_stock = (int)$_POST['stock'];

        // Verify ownership
        $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
        $chk->execute([$product_id, $business_id]);

        if ($chk->fetch()) {
            if ($new_stock < 0) {
                $message = 'Stock quantity cannot be negative.';
                $message_type = 'danger';
            } else {
                $upd = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                if ($upd->execute([$new_stock, $product_id])) {
                    $message = 'Stock updated successfully!';
                }
            }
        } else {
            $message = 'Permission denied.';
            $message_type = 'danger';
        }
    }
}

// Fetch inventory list
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.stock, p.price, p.status, c.category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.business_id = ? 
    ORDER BY p.stock ASC
");
$stmt->execute([$business_id]);
$inventory = $stmt->fetchAll();

$page_title = 'Inventory Management';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Inventory Management</h4>
                <small class="text-muted">Monitor and quickly adjust stock counts</small>
            </div>
            <a href="../products/index.php" class="btn btn-outline-primary btn-sm">Manage Products</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border rounded-3 p-4 bg-white shadow-sm">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Inventory Stock Status</h5>
            
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock Count</th>
                            <th>Status Alert</th>
                            <th class="text-end" style="width: 250px;">Quick Stock Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($inventory) > 0): ?>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td class="fw-semibold">#LT-PROD-<?php echo str_pad($item['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <h6 class="fw-semibold mb-0 small"><?php echo sanitize($item['name']); ?></h6>
                                        <small class="text-muted"><?php echo $item['status'] === 'active' ? 'Active Storefront' : 'Hidden Storefront'; ?></small>
                                    </td>
                                    <td><?php echo sanitize($item['category_name']); ?></td>
                                    <td class="fw-bold"><?php echo format_price($item['price']); ?></td>
                                    <td>
                                        <span class="fw-bold fs-6"><?php echo $item['stock']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($item['stock'] == 0): ?>
                                            <span class="badge bg-danger">OUT OF STOCK</span>
                                        <?php elseif ($item['stock'] < 5): ?>
                                            <span class="badge bg-warning text-dark">CRITICAL LOW STOCK</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">OK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <form action="index.php" method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="stock" class="form-control form-control-sm text-center" style="width: 90px;" value="<?php echo $item['stock']; ?>" min="0">
                                            <button type="submit" name="update_stock" class="btn btn-primary btn-sm fw-semibold">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No inventory listings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
