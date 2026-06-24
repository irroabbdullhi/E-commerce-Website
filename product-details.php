<?php
$page_title = 'Product Details';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$stmt = $pdo->prepare("
    SELECT p.*, c.category_name, b.business_name, b.address as business_address, b.description as business_desc
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    JOIN businesses b ON p.business_id = b.id 
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container py-5 text-center'><div class='alert alert-warning'>Product not found.</div><a href='shop.php' class='btn btn-primary'>Back to Shop</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$rating_info = get_product_rating($pdo, $product['id']);

// Add to Cart / Wishlist Handler
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        if (isset($_POST['add_to_cart'])) {
            if ($_SESSION['user_role'] !== 'customer') {
                $message = 'Only customer accounts can purchase products.';
                $message_type = 'warning';
            } elseif ($product['stock'] < $qty) {
                $message = 'Requested quantity exceeds available stock.';
                $message_type = 'danger';
            } else {
                // Upsert to cart
                $cart_stmt = $pdo->prepare("
                    INSERT INTO cart (customer_id, product_id, quantity) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
                ");
                if ($cart_stmt->execute([$_SESSION['user_id'], $product['id'], $qty])) {
                    $message = 'Product added to your cart successfully!';
                    $message_type = 'success';
                }
            }
        } elseif (isset($_POST['add_to_wishlist'])) {
            if ($_SESSION['user_role'] !== 'customer') {
                $message = 'Only customer accounts have wishlists.';
                $message_type = 'warning';
            } else {
                // Insert into wishlist
                $wish_stmt = $pdo->prepare("
                    INSERT IGNORE INTO wishlist (customer_id, product_id) 
                    VALUES (?, ?)
                ");
                if ($wish_stmt->execute([$_SESSION['user_id'], $product['id']])) {
                    $message = 'Product added to your wishlist!';
                    $message_type = 'success';
                }
            }
        } elseif (isset($_POST['submit_review'])) {
            $rating = (int)$_POST['rating'];
            $comment = sanitize($_POST['comment']);
            
            if ($rating < 1 || $rating > 5 || empty($comment)) {
                $message = 'Please provide a valid rating and comment.';
                $message_type = 'danger';
            } else {
                $rev_stmt = $pdo->prepare("INSERT INTO reviews (customer_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
                if ($rev_stmt->execute([$_SESSION['user_id'], $product['id'], $rating, $comment])) {
                    $message = 'Thank you for your review!';
                    $message_type = 'success';
                    // Refresh rating details
                    $rating_info = get_product_rating($pdo, $product['id']);
                }
            }
        }
    }
}

// Fetch all reviews for this product
$rev_stmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reviews r 
    JOIN users u ON r.customer_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.id DESC
");
$rev_stmt->execute([$product['id']]);
$reviews = $rev_stmt->fetchAll();

$img_src = !empty($product['image']) ? '/ecommerce-system/assets/uploads/' . $product['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&auto=format&fit=crop&q=80';
?>

<!-- Breadcrumbs -->
<div class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php" class="text-decoration-none">Shop</a></li>
                <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_id']; ?>" class="text-decoration-none"><?php echo sanitize($product['category_name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <!-- Left Column: Product Image -->
        <div class="col-md-6">
            <div class="card border rounded-3 p-2 bg-white mb-3">
                <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($product['name']); ?>" class="img-fluid rounded-3" id="mainProductImg">
            </div>
            <!-- Extra Thumbnail Gallery Simulated -->
            <div class="row g-2">
                <div class="col-3">
                    <img src="<?php echo $img_src; ?>" class="img-fluid rounded border border-primary p-1 cursor-pointer opacity-75" style="height: 70px; object-fit: cover;">
                </div>
                <div class="col-3">
                    <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=200&auto=format&fit=crop&q=80" class="img-fluid rounded border p-1 cursor-pointer" style="height: 70px; object-fit: cover;" onclick="document.getElementById('mainProductImg').src=this.src">
                </div>
                <div class="col-3">
                    <img src="https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=200&auto=format&fit=crop&q=80" class="img-fluid rounded border p-1 cursor-pointer" style="height: 70px; object-fit: cover;" onclick="document.getElementById('mainProductImg').src=this.src">
                </div>
            </div>
        </div>

        <!-- Right Column: Product Core Details -->
        <div class="col-md-6">
            <span class="badge bg-primary mb-2"><?php echo sanitize($product['category_name']); ?></span>
            <h1 class="fw-bold mb-2"><?php echo sanitize($product['name']); ?></h1>
            
            <!-- Rating -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="text-warning">
                    <?php echo render_stars($rating_info['rating']); ?>
                </div>
                <span class="text-muted small"><?php echo $rating_info['rating']; ?> / 5.0 (<?php echo $rating_info['count']; ?> reviews)</span>
            </div>

            <!-- Price -->
            <div class="mb-4">
                <h3 class="fw-bold text-primary"><?php echo format_price($product['price']); ?></h3>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark">Description</h6>
                <p class="text-muted"><?php echo nl2br(sanitize($product['description'])); ?></p>
            </div>

            <!-- Stock status -->
            <div class="mb-4">
                <?php if ($product['stock'] > 0): ?>
                    <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i> In Stock (<?php echo $product['stock']; ?> available)</span>
                <?php else: ?>
                    <span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i> Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Add to Cart Form -->
            <form action="product-details.php?id=<?php echo $product['id']; ?>" method="POST" class="mb-4 border-top pt-4">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-auto">
                        <label for="quantity" class="form-label mb-0 fw-semibold">Quantity:</label>
                    </div>
                    <div class="col-auto" style="width: 100px;">
                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo max(1, $product['stock']); ?>" <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <?php if (is_logged_in()): ?>
                        <button type="submit" name="add_to_cart" class="btn btn-primary px-4 py-2 flex-grow-1" <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart-plus me-2"></i>Add to Cart
                        </button>
                        <button type="submit" name="add_to_wishlist" class="btn btn-outline-secondary px-3 py-2" title="Add to Wishlist">
                            <i class="bi bi-heart"></i>
                        </button>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-primary px-4 py-2 flex-grow-1">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Purchase
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Seller Box -->
            <div class="card border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shop me-1"></i><?php echo sanitize($product['business_name']); ?></h6>
                    <span class="badge bg-secondary">Local Seller</span>
                </div>
                <p class="text-muted small mb-2"><?php echo sanitize($product['business_desc'] ?? 'A trusted local producer on LocalTrade.'); ?></p>
                <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?php echo sanitize($product['business_address']); ?></div>
            </div>
        </div>
    </div>

    <!-- Product Specifications & Attributes -->
    <div class="row mt-5">
        <div class="col-lg-6">
            <div class="card border-0 bg-white p-4 shadow-sm h-100">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Product Attributes</h5>
                <table class="table table-borderless m-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold px-0 text-muted" style="width: 150px;">Category</td>
                            <td class="px-0"><?php echo sanitize($product['category_name']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-0 text-muted">Stock Status</td>
                            <td class="px-0"><?php echo $product['stock'] > 0 ? 'Available' : 'Unavailable'; ?></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold px-0 text-muted">SKU</td>
                            <td class="px-0 text-uppercase">LT-PROD-<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Community Reviews -->
        <div class="col-lg-6">
            <div class="card border-0 bg-white p-4 shadow-sm h-100">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Community Reviews (<?php echo count($reviews); ?>)</h5>
                
                <!-- Add Review Form (Only for Customer role) -->
                <?php if (is_logged_in() && $_SESSION['user_role'] === 'customer'): ?>
                    <form action="product-details.php?id=<?php echo $product['id']; ?>" method="POST" class="mb-4 pb-4 border-bottom">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        <h6 class="fw-bold mb-2">Write a Review</h6>
                        <div class="mb-2">
                            <label for="rating" class="form-label small mb-1">Your Rating:</label>
                            <select name="rating" id="rating" class="form-select form-select-sm" style="width: 120px;" required>
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label small mb-1">Comment:</label>
                            <textarea name="comment" id="comment" rows="2" class="form-control" required placeholder="Tell others what you think of this product..."></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary btn-sm">Submit Review</button>
                    </form>
                <?php endif; ?>

                <!-- Reviews list -->
                <div class="reviews-list" style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div class="review-item mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between mb-1">
                                    <h6 class="fw-bold m-0 small"><?php echo sanitize($rev['full_name']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                                </div>
                                <div class="text-warning mb-1" style="font-size: 0.8rem;">
                                    <?php echo render_stars($rev['rating']); ?>
                                </div>
                                <p class="text-muted small m-0"><?php echo sanitize($rev['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small text-center my-4">No reviews yet for this product. Be the first to buy and review!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
