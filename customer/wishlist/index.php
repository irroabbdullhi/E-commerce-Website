<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('customer');

$customer_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Handle Actions (Add to Cart, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $product_id = (int)$_POST['product_id'];
        
        if (isset($_POST['add_to_cart'])) {
            // Check stock
            $stk_stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stk_stmt->execute([$product_id]);
            $stock = $stk_stmt->fetchColumn();

            if ($stock < 1) {
                $message = 'This product is currently out of stock.';
                $message_type = 'danger';
            } else {
                // Insert into cart
                $cart_stmt = $pdo->prepare("
                    INSERT INTO cart (customer_id, product_id, quantity) 
                    VALUES (?, ?, 1) 
                    ON DUPLICATE KEY UPDATE quantity = quantity + 1
                ");
                if ($cart_stmt->execute([$customer_id, $product_id])) {
                    // Remove from wishlist
                    $del_stmt = $pdo->prepare("DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?");
                    $del_stmt->execute([$customer_id, $product_id]);
                    $message = 'Product moved to your shopping cart!';
                }
            }
        } elseif (isset($_POST['remove_wish'])) {
            $del_stmt = $pdo->prepare("DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?");
            if ($del_stmt->execute([$customer_id, $product_id])) {
                $message = 'Product removed from your wishlist.';
            }
        }
    }
}

// Fetch wishlist items
$stmt = $pdo->prepare("
    SELECT w.id as wish_id, p.*, b.business_name, c.category_name 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    JOIN businesses b ON p.business_id = b.id 
    WHERE w.customer_id = ?
");
$stmt->execute([$customer_id]);
$wishlist = $stmt->fetchAll();

$page_title = 'My Wishlist';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">My Wishlist</h4>
                <small class="text-muted">Review items you saved for later</small>
            </div>
            <a href="/ecommerce-system/shop.php" class="btn btn-outline-primary btn-sm">Browse Products</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (count($wishlist) > 0): ?>
            <div class="row g-4">
                <?php foreach ($wishlist as $item): 
                    $img_src = !empty($item['image']) ? '/ecommerce-system/assets/uploads/' . $item['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&auto=format&fit=crop&q=80';
                ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="product-card shadow-sm">
                            <div class="product-img-wrapper" style="padding-top: 65%;">
                                <img src="<?php echo $img_src; ?>" alt="">
                                <span class="product-badge bg-primary"><?php echo sanitize($item['category_name']); ?></span>
                            </div>
                            <div class="product-details p-3">
                                <h6 class="fw-bold text-dark mb-1 text-truncate">
                                    <a href="/ecommerce-system/product-details.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark"><?php echo sanitize($item['name']); ?></a>
                                </h6>
                                <small class="text-muted d-block mb-2 text-truncate"><?php echo sanitize($item['business_name']); ?></small>
                                
                                <div class="fw-bold text-primary mb-3"><?php echo format_price($item['price']); ?></div>
                                
                                <div class="d-flex gap-1">
                                    <form action="index.php" method="POST" class="flex-grow-1">
                                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm w-100"><i class="bi bi-cart-plus me-1"></i>Cart</button>
                                    </form>
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_wish" class="btn btn-outline-danger btn-sm" title="Remove"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm p-5 text-center bg-white my-5">
                <div class="text-muted fs-1 mb-3"><i class="bi bi-heart"></i></div>
                <h5 class="fw-bold">Your Wishlist is Empty</h5>
                <p class="text-muted">Save products you like to view them here later.</p>
                <a href="/ecommerce-system/shop.php" class="btn btn-primary mt-3 align-self-center">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
