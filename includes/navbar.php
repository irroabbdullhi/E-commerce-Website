<?php
$cart_count = 0;
if (is_logged_in() && $_SESSION['user_role'] === 'customer') {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_res = $stmt->fetch();
    $cart_count = $cart_res['count'] ?? 0;
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold text-primary" href="/ecommerce-system/index.php">
            <i class="bi bi-shop me-2"></i>LocalTrade
        </a>
        
        <!-- Toggle button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar items -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/ecommerce-system/shop.php">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/ecommerce-system/about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/ecommerce-system/contact.php">Contact</a>
                </li>
            </ul>

            <!-- Search Form -->
            <form action="/ecommerce-system/shop.php" method="GET" class="d-flex me-3 mb-2 mb-lg-0" style="max-width: 400px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Find what you need..." value="<?php echo isset($_GET['search']) ? sanitize($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Right Align Actions -->
            <div class="d-flex align-items-center gap-3">
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['user_role'] === 'customer'): ?>
                        <!-- Customer specific links -->
                        <a href="/ecommerce-system/customer/wishlist/index.php" class="text-dark position-relative" title="Wishlist">
                            <i class="bi bi-heart fs-5"></i>
                        </a>
                        <a href="/ecommerce-system/customer/cart/index.php" class="text-dark position-relative" title="Shopping Cart">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- User Account Dropdown -->
                    <div class="dropdown">
                        <a class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 600; font-size: 0.85rem;">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo sanitize($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userDropdown">
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="/ecommerce-system/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                            <?php elseif ($_SESSION['user_role'] === 'owner'): ?>
                                <li><a class="dropdown-item" href="/ecommerce-system/owner/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Seller Dashboard</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="/ecommerce-system/customer/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</a></li>
                                <li><a class="dropdown-item" href="/ecommerce-system/customer/profile/index.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="/ecommerce-system/customer/orders/index.php"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/ecommerce-system/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Auth Links -->
                    <a href="/ecommerce-system/auth/login.php" class="btn btn-outline-primary">Sign In</a>
                    <a href="/ecommerce-system/auth/register.php?role=owner" class="btn btn-primary">Register as Business</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
