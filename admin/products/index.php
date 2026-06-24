<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$message = '';
$message_type = 'success';

// Handle product status override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $product_id = (int)$_POST['product_id'];
        $new_status = sanitize($_POST['status']);

        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $product_id])) {
            $message = 'Product status updated successfully!';
        }
    }
}

// Fetch all products with category and business details
$stmt = $pdo->query("
    SELECT p.*, c.category_name, b.business_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    JOIN businesses b ON p.business_id = b.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

$page_title = 'Platform Products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Platform Products</h4>
                <small class="text-muted">Review and audit all products listed on LocalTrade</small>
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
            <h5 class="fw-bold mb-3 border-bottom pb-2">Audit Product Catalog</h5>

            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Shop</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 250px;">Set Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $prod): 
                                $img_src = !empty($prod['image']) ? '/ecommerce-system/assets/uploads/' . $prod['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=60&auto=format&fit=crop&q=80';
                            ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $img_src; ?>" alt="" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold small"><?php echo sanitize($prod['name']); ?></div>
                                        <small class="text-muted text-uppercase" style="font-size: 0.7rem;">SKU: <?php echo str_pad($prod['id'], 5, '0', STR_PAD_LEFT); ?></small>
                                    </td>
                                    <td><span class="small fw-semibold"><?php echo sanitize($prod['business_name']); ?></span></td>
                                    <td><?php echo sanitize($prod['category_name']); ?></td>
                                    <td class="fw-bold"><?php echo format_price($prod['price']); ?></td>
                                    <td><?php echo $prod['stock']; ?> units</td>
                                    <td>
                                        <span class="badge badge-status status-<?php echo $prod['status']; ?>"><?php echo ucfirst($prod['status']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <form action="index.php" method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                                <option value="active" <?php echo $prod['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $prod['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Set</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No products listed on the platform.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
