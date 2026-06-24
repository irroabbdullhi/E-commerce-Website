<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('customer');

$customer_id = $_SESSION['user_id'];
$error = '';
$success = '';

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

if (count($cart_items) == 0) {
    header("Location: /ecommerce-system/customer/cart/index.php");
    exit;
}

// Calculations
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = ($subtotal > 100) ? 0.00 : 5.00;
$tax = $subtotal * 0.08;
$total_amount = $subtotal + $shipping + $tax;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $address = sanitize($_POST['address']);
        $phone = sanitize($_POST['phone']);
        $payment_method = sanitize($_POST['payment_method']);

        if (empty($address) || empty($phone) || empty($payment_method)) {
            $error = 'Please fill in all shipping and payment fields.';
        } else {
            // Process order in a single transaction
            try {
                $pdo->beginTransaction();

                // 1. Insert Order
                $order_stmt = $pdo->prepare("
                    INSERT INTO orders (customer_id, total_amount, order_status, payment_status) 
                    VALUES (?, ?, 'pending', 'pending')
                ");
                $order_stmt->execute([$customer_id, $total_amount]);
                $order_id = $pdo->lastInsertId();

                // 2. Insert Order Items and Deduct Stock
                $item_stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stock_stmt = $pdo->prepare("
                    UPDATE products SET stock = stock - ? WHERE id = ?
                ");

                foreach ($cart_items as $item) {
                    // Check if stock is still available
                    if ($item['stock'] < $item['quantity']) {
                        throw new Exception("Product '" . $item['product_name'] . "' is out of stock or does not have enough quantities.");
                    }
                    
                    // Insert item
                    $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                    
                    // Deduct stock
                    $stock_stmt->execute([$item['quantity'], $item['product_id']]);
                }

                // 3. Create Payment entry
                $tx_ref = 'TX-' . strtoupper(bin2hex(random_bytes(8)));
                $pay_stmt = $pdo->prepare("
                    INSERT INTO payments (order_id, amount, payment_method, transaction_reference, payment_status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $pay_stmt->execute([$order_id, $total_amount, $payment_method, $tx_ref, 'pending']);

                // 4. Clear Customer Cart
                $clear_stmt = $pdo->prepare("DELETE FROM cart WHERE customer_id = ?");
                $clear_stmt->execute([$customer_id]);

                $pdo->commit();
                
                $_SESSION['last_order_id'] = $order_id;
                header("Location: /ecommerce-system/customer/orders/index.php?success=1");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error processing checkout: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Checkout';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Checkout</h4>
                <small class="text-muted">Review order and complete payment details</small>
            </div>
            <a href="/ecommerce-system/customer/cart/index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cart me-1"></i>Back to Cart</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

            <div class="row g-4">
                <!-- Shipping and Payment Details -->
                <div class="col-lg-7">
                    <!-- Shipping Info Card -->
                    <div class="card border rounded-3 p-4 bg-white shadow-sm mb-4">
                        <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-geo-alt me-2 text-primary"></i>Shipping Details</h5>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <textarea class="form-control" name="address" id="address" rows="3" required placeholder="Street address, Apartment/Suite, City, State, ZIP code"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Contact Phone Number</label>
                            <input type="text" class="form-control" name="phone" id="phone" required placeholder="e.g. 123-456-7890">
                        </div>
                    </div>

                    <!-- Payment Info Card -->
                    <div class="card border rounded-3 p-4 bg-white shadow-sm">
                        <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-credit-card me-2 text-primary"></i>Payment Method</h5>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="Cash on Delivery" checked>
                            <label class="form-check-input-label fw-bold" for="pay_cod">
                                Cash on Delivery (COD)
                            </label>
                            <p class="text-muted small m-0 ms-4">Pay local courier with cash upon receiving your items.</p>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="pay_card" value="Credit Card">
                            <label class="form-check-input-label fw-bold" for="pay_card">
                                Credit / Debit Card (Simulated)
                            </label>
                            <p class="text-muted small m-0 ms-4">Pay securely using Visa, MasterCard, or American Express.</p>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pay_bank" value="Bank Transfer">
                            <label class="form-check-input-label fw-bold" for="pay_bank">
                                Local Bank Transfer (Simulated)
                            </label>
                            <p class="text-muted small m-0 ms-4">Transfer directly to LocalTrade escrow bank account.</p>
                        </div>
                    </div>
                </div>

                <!-- Order items and subtotal summary -->
                <div class="col-lg-5">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm mb-4">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Your Order</h5>
                        
                        <!-- Mini cart items -->
                        <div class="mb-4" style="max-height: 250px; overflow-y: auto;">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div style="max-width: 70%;">
                                        <h6 class="fw-bold mb-0 small text-truncate"><?php echo sanitize($item['product_name']); ?></h6>
                                        <small class="text-muted"><?php echo $item['quantity']; ?> x <?php echo format_price($item['price']); ?></small>
                                    </div>
                                    <span class="fw-semibold text-dark small"><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Calculations -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal</span>
                                <span class="fw-semibold small"><?php echo format_price($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Estimated Tax (8%)</span>
                                <span class="fw-semibold small"><?php echo format_price($tax); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                <span class="text-muted small">Shipping Fee</span>
                                <span class="fw-semibold small">
                                    <?php echo $shipping == 0 ? '<span class="text-success">Free</span>' : format_price($shipping); ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="fw-bold text-dark">Total</span>
                                <span class="fw-bold text-primary fs-5"><?php echo format_price($total_amount); ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            Place Order (<?php echo format_price($total_amount); ?>)
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
