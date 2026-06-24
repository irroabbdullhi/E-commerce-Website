<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('customer');

$customer_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Handle Actions (Update Qty, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        if (isset($_POST['update_qty'])) {
            $cart_id = (int)$_POST['cart_id'];
            $qty = (int)$_POST['quantity'];
            
            // Check stock first
            $chk_stmt = $pdo->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?");
            $chk_stmt->execute([$cart_id]);
            $stock = $chk_stmt->fetchColumn();

            if ($qty < 1) {
                $message = 'Quantity must be at least 1.';
                $message_type = 'danger';
            } elseif ($qty > $stock) {
                $message = 'Requested quantity exceeds available stock of ' . $stock . ' items.';
                $message_type = 'danger';
            } else {
                $upd_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
                if ($upd_stmt->execute([$qty, $cart_id, $customer_id])) {
                    $message = 'Cart updated successfully!';
                }
            }
        } elseif (isset($_POST['delete_item'])) {
            $cart_id = (int)$_POST['cart_id'];
            $del_stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
            if ($del_stmt->execute([$cart_id, $customer_id])) {
                $message = 'Item removed from cart.';
            }
        }
    }
}

// Fetch Cart Items
$stmt = $pdo->prepare("
    SELECT c.*, p.name as product_name, p.price, p.stock, p.image, b.business_name 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    JOIN businesses b ON p.business_id = b.id 
    WHERE c.customer_id = ?
");
$stmt->execute([$customer_id]);
$cart_items = $stmt->fetchAll();

// Calculations
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = ($subtotal > 100 || $subtotal == 0) ? 0.00 : 5.00;
$tax = $subtotal * 0.08; // 8% tax
$total_amount = $subtotal + $shipping + $tax;

$page_title = 'Shopping Cart';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Shopping Cart</h4>
                <small class="text-muted">Manage items in your shopping cart</small>
            </div>
            <a href="/ecommerce-system/shop.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="row g-4">
                <!-- Cart Items List -->
                <div class="col-lg-8">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm">
                        <?php foreach ($cart_items as $item): 
                            $img_src = !empty($item['image']) ? '/ecommerce-system/assets/uploads/' . $item['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100&auto=format&fit=crop&q=80';
                        ?>
                            <div class="row align-items-center mb-4 pb-4 border-bottom last-border-none">
                                <div class="col-md-2 col-3">
                                    <img src="<?php echo $img_src; ?>" alt="" class="img-fluid rounded border">
                                </div>
                                <div class="col-md-4 col-9 mb-3 mb-md-0">
                                    <h6 class="fw-bold mb-1"><?php echo sanitize($item['product_name']); ?></h6>
                                    <small class="text-muted d-block mb-1"><i class="bi bi-shop me-1"></i><?php echo sanitize($item['business_name']); ?></small>
                                    <span class="fw-semibold text-primary"><?php echo format_price($item['price']); ?></span>
                                </div>
                                <div class="col-md-3 col-6">
                                    <form action="index.php" method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" class="form-control form-control-sm text-center" style="width: 70px;" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                                        <button type="submit" name="update_qty" class="btn btn-outline-secondary btn-sm" title="Update Quantity"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                </div>
                                <div class="col-md-2 col-4 text-start text-md-end fw-bold">
                                    <?php echo format_price($item['price'] * $item['quantity']); ?>
                                </div>
                                <div class="col-md-1 col-2 text-end">
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_item" class="btn btn-sm btn-outline-danger" title="Remove item"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold"><?php echo format_price($subtotal); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Estimated Tax (8%)</span>
                            <span class="fw-semibold"><?php echo format_price($tax); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Shipping Fee</span>
                            <span class="fw-semibold">
                                <?php echo $shipping == 0 ? '<span class="text-success">Free</span>' : format_price($shipping); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold text-dark fs-5">Total</span>
                            <span class="fw-bold text-primary fs-5"><?php echo format_price($total_amount); ?></span>
                        </div>

                        <a href="/ecommerce-system/customer/checkout/index.php" class="btn btn-primary w-100 py-2 fw-semibold">
                            Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm p-5 text-center bg-white my-5">
                <div class="text-muted fs-1 mb-3"><i class="bi bi-cart-x"></i></div>
                <h5 class="fw-bold">Your Cart is Empty</h5>
                <p class="text-muted">Looks like you haven't added any products to your cart yet.</p>
                <a href="/ecommerce-system/shop.php" class="btn btn-primary mt-3 align-self-center">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
