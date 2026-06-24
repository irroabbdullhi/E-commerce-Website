<?php
if (!is_logged_in()) {
    return;
}

$role = $_SESSION['user_role'];
$current_page = $_SERVER['REQUEST_URI'];
?>
<div class="sidebar">
    <div class="brand">
        <?php if ($role === 'admin'): ?>
            <span class="fs-5 fw-bold text-primary">Admin Central</span><br>
            <small class="text-muted fw-normal" style="font-size: 0.75rem;">Platform Manager</small>
        <?php elseif ($role === 'owner'): ?>
            <span class="fs-5 fw-bold text-primary">LocalTrade</span><br>
            <small class="text-muted fw-normal" style="font-size: 0.75rem;">Seller Dashboard</small>
        <?php else: ?>
            <span class="fs-5 fw-bold text-primary">LocalTrade</span><br>
            <small class="text-muted fw-normal" style="font-size: 0.75rem;">Customer Portal</small>
        <?php endif; ?>
    </div>

    <div class="flex-grow-1">
        <?php if ($role === 'admin'): ?>
            <!-- Admin Sidebar Links -->
            <a href="/ecommerce-system/admin/dashboard.php" class="nav-link <?php echo (strpos($current_page, 'admin/dashboard.php') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="/ecommerce-system/admin/users/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/users/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="/ecommerce-system/admin/businesses/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/businesses/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-building"></i> Businesses
            </a>
            <a href="/ecommerce-system/admin/categories/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/categories/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            <a href="/ecommerce-system/admin/products/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/products/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a href="/ecommerce-system/admin/orders/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/orders/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-bag"></i> Orders
            </a>
            <a href="/ecommerce-system/admin/payments/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/payments/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-credit-card"></i> Payments
            </a>
            <a href="/ecommerce-system/admin/reports/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/reports/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
            <a href="/ecommerce-system/admin/settings/index.php" class="nav-link <?php echo (strpos($current_page, 'admin/settings/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Settings
            </a>

        <?php elseif ($role === 'owner'): ?>
            <!-- Business Owner Sidebar Links -->
            <a href="/ecommerce-system/owner/dashboard.php" class="nav-link <?php echo (strpos($current_page, 'owner/dashboard.php') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="/ecommerce-system/owner/products/index.php" class="nav-link <?php echo (strpos($current_page, 'owner/products/') !== false && strpos($current_page, 'inventory') === false) ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a href="/ecommerce-system/owner/inventory/index.php" class="nav-link <?php echo (strpos($current_page, 'owner/inventory/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-boxes"></i> Inventory
            </a>
            <a href="/ecommerce-system/owner/orders/index.php" class="nav-link <?php echo (strpos($current_page, 'owner/orders/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-bag"></i> Orders
            </a>
            <a href="/ecommerce-system/owner/customers/index.php" class="nav-link <?php echo (strpos($current_page, 'owner/customers/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Customers
            </a>
            <a href="/ecommerce-system/owner/reports/index.php" class="nav-link <?php echo (strpos($current_page, 'owner/reports/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
            <a href="/ecommerce-system/owner/profile/index.php" class="nav-link <?php echo (strpos($current_page, 'owner/profile/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-person"></i> Profile
            </a>

        <?php else: ?>
            <!-- Customer Portal Sidebar Links -->
            <a href="/ecommerce-system/customer/dashboard.php" class="nav-link <?php echo (strpos($current_page, 'customer/dashboard.php') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="/ecommerce-system/customer/orders/index.php" class="nav-link <?php echo (strpos($current_page, 'customer/orders/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-bag"></i> My Orders
            </a>
            <a href="/ecommerce-system/customer/wishlist/index.php" class="nav-link <?php echo (strpos($current_page, 'customer/wishlist/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-heart"></i> Wishlist
            </a>
            <a href="/ecommerce-system/customer/profile/index.php" class="nav-link <?php echo (strpos($current_page, 'customer/profile/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-person"></i> Profile
            </a>
        <?php endif; ?>
    </div>

    <div class="border-top pt-3 mt-3">
        <a href="/ecommerce-system/index.php" class="nav-link">
            <i class="bi bi-globe"></i> Visit Store
        </a>
        <a href="/ecommerce-system/auth/logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
