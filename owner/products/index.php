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

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $name = sanitize($_POST['name']);
        $cat_id = (int)$_POST['category_id'];
        $desc = sanitize($_POST['description']);
        $price = (double)$_POST['price'];
        $stock = (int)$_POST['stock'];

        // Upload Image
        $img_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_res = handle_file_upload($_FILES['image'], 'products');
            if ($upload_res['status']) {
                $img_name = $upload_res['file_name'];
            } else {
                $message = $upload_res['message'];
                $message_type = 'danger';
            }
        }

        if ($price <= 0 || $stock < 0 || empty($name) || empty($desc)) {
            $message = 'Please provide valid values for all fields.';
            $message_type = 'danger';
        } elseif (empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO products (business_id, category_id, name, description, price, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            if ($stmt->execute([$business_id, $cat_id, $name, $desc, $price, $stock, $img_name])) {
                $message = 'Product added successfully!';
            } else {
                $message = 'Failed to add product.';
                $message_type = 'danger';
            }
        }
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $product_id = (int)$_POST['product_id'];
        $name = sanitize($_POST['name']);
        $cat_id = (int)$_POST['category_id'];
        $desc = sanitize($_POST['description']);
        $price = (double)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $status = sanitize($_POST['status']);

        // Check ownership
        $owner_chk = $pdo->prepare("SELECT id, image FROM products WHERE id = ? AND business_id = ?");
        $owner_chk->execute([$product_id, $business_id]);
        $existing_product = $owner_chk->fetch();

        if (!$existing_product) {
            $message = 'Permission denied.';
            $message_type = 'danger';
        } else {
            $img_name = $existing_product['image'];
            // If new image uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_res = handle_file_upload($_FILES['image'], 'products');
                if ($upload_res['status']) {
                    $img_name = $upload_res['file_name'];
                } else {
                    $message = $upload_res['message'];
                    $message_type = 'danger';
                }
            }

            if (empty($message)) {
                $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ?, status = ? WHERE id = ?");
                if ($stmt->execute([$cat_id, $name, $desc, $price, $stock, $img_name, $status, $product_id])) {
                    $message = 'Product updated successfully!';
                } else {
                    $message = 'Failed to update product.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Check ownership
    $owner_chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND business_id = ?");
    $owner_chk->execute([$del_id, $business_id]);
    
    if ($owner_chk->fetch()) {
        $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($del->execute([$del_id])) {
            $message = 'Product deleted successfully.';
        }
    } else {
        $message = 'Permission denied.';
        $message_type = 'danger';
    }
}

// Fetch all products of this business
$prod_stmt = $pdo->prepare("
    SELECT p.*, c.category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.business_id = ? 
    ORDER BY p.id DESC
");
$prod_stmt->execute([$business_id]);
$products = $prod_stmt->fetchAll();

// Fetch categories for forms
$categories = get_categories($pdo);

// Edit mode setup
$edit_item = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND business_id = ?");
    $edit_stmt->execute([$edit_id, $business_id]);
    $edit_item = $edit_stmt->fetch();
}

$page_title = 'Manage Products';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Manage Products</h4>
                <small class="text-muted">Create, edit, or delete items from your inventory</small>
            </div>
            <div>
                <a href="index.php?add_mode=1" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Product</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Add / Edit Form Column -->
            <?php if (isset($_GET['add_mode']) || $edit_item): ?>
                <div class="col-lg-4">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h5 class="fw-bold m-0"><?php echo $edit_item ? 'Edit Product' : 'Add New Product'; ?></h5>
                            <a href="index.php" class="btn-close" aria-label="Close"></a>
                        </div>

                        <form action="index.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            
                            <?php if ($edit_item): ?>
                                <input type="hidden" name="product_id" value="<?php echo $edit_item['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" id="name" required value="<?php echo $edit_item ? sanitize($edit_item['name']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_item && $edit_item['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price ($)</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="price" required value="<?php echo $edit_item ? $edit_item['price'] : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock" id="stock" required value="<?php echo $edit_item ? $edit_item['stock'] : '0'; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="image" id="image">
                                <?php if ($edit_item && !empty($edit_item['image'])): ?>
                                    <div class="mt-2 small text-muted">Current: <?php echo basename($edit_item['image']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="3" required><?php echo $edit_item ? sanitize($edit_item['description']) : ''; ?></textarea>
                            </div>

                            <?php if ($edit_item): ?>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="active" <?php echo $edit_item['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $edit_item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <button type="submit" name="edit_product" class="btn btn-primary w-100">Save Changes</button>
                            <?php else: ?>
                                <button type="submit" name="add_product" class="btn btn-primary w-100">Add Product</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Products List Column -->
            <div class="<?php echo (isset($_GET['add_mode']) || $edit_item) ? 'col-lg-8' : 'col-12'; ?>">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Product Catalog</h5>

                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($products) > 0): ?>
                                    <?php foreach ($products as $prod): 
                                        $img_src = !empty($prod['image']) ? '/ecommerce-system/assets/uploads/' . $prod['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=60&auto=format&fit=crop&q=80';
                                    ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $img_src; ?>" alt="" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <h6 class="fw-bold mb-0 small"><?php echo sanitize($prod['name']); ?></h6>
                                                <small class="text-muted text-uppercase" style="font-size: 0.7rem;">SKU: <?php echo str_pad($prod['id'], 5, '0', STR_PAD_LEFT); ?></small>
                                            </td>
                                            <td><?php echo sanitize($prod['category_name']); ?></td>
                                            <td class="fw-bold"><?php echo format_price($prod['price']); ?></td>
                                            <td>
                                                <?php if ($prod['stock'] == 0): ?>
                                                    <span class="badge bg-danger rounded-pill">Out of Stock</span>
                                                <?php elseif ($prod['stock'] < 5): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill"><?php echo $prod['stock']; ?> Low</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success rounded-pill"><?php echo $prod['stock']; ?> units</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-status status-<?php echo $prod['status']; ?>"><?php echo ucfirst($prod['status']); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <a href="index.php?edit_id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-light text-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                                <a href="index.php?delete=<?php echo $prod['id']; ?>" class="btn btn-sm btn-light text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?');"><i class="bi bi-trash-fill"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No products created yet. click "New Product" to begin.</td>
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
