<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$owner_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];
$business = null;
$error = '';
$success = '';

// Check if owner has a business record in database
if ($business_id) {
    $stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();
} else {
    // Check if there is a pending business
    $stmt = $pdo->prepare("SELECT * FROM businesses WHERE owner_id = ? LIMIT 1");
    $stmt->execute([$owner_id]);
    $business = $stmt->fetch();
    if ($business) {
        $_SESSION['business_id'] = $business['id'];
        $business_id = $business['id'];
    }
}

// Handle Business Profile Registration if they don't have one
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$business) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $b_name = sanitize($_POST['business_name']);
        $b_type = sanitize($_POST['business_type']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $desc = sanitize($_POST['description']);

        if (empty($b_name) || empty($b_type) || empty($phone) || empty($address)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Upload Logo
            $logo_name = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_res = handle_file_upload($_FILES['logo'], 'logos');
                if ($upload_res['status']) {
                    $logo_name = $upload_res['file_name'];
                } else {
                    $error = $upload_res['message'];
                }
            }

            if (empty($error)) {
                $ins = $pdo->prepare("INSERT INTO businesses (owner_id, business_name, business_type, phone, address, logo, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                if ($ins->execute([$owner_id, $b_name, $b_type, $phone, $address, $logo_name, $desc])) {
                    $_SESSION['business_id'] = $pdo->lastInsertId();
                    header("Location: dashboard.php");
                    exit;
                }
            }
        }
    }
}

$page_title = 'Seller Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout-wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- 1. State: No Business Registered -->
        <?php if (!$business): ?>
            <div class="top-navbar">
                <div>
                    <h4 class="fw-bold m-0">Register Your Local Business</h4>
                    <small class="text-muted">Create a business profile to start selling on LocalTrade</small>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card border rounded-3 p-4 bg-white shadow-sm max-width-800">
                <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input type="text" class="form-control" name="business_name" id="business_name" required placeholder="e.g. Artisan Brews">
                        </div>
                        <div class="col-md-6">
                            <label for="business_type" class="form-label">Business Type</label>
                            <input type="text" class="form-control" name="business_type" id="business_type" required placeholder="e.g. Specialty Coffee, Woodworking">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" name="phone" id="phone" required placeholder="e.g. 123-456-7890">
                        </div>
                        <div class="col-md-6">
                            <label for="logo" class="form-label">Upload Logo</label>
                            <input type="file" class="form-control" name="logo" id="logo">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Storefront Address</label>
                            <textarea class="form-control" name="address" id="address" rows="2" required placeholder="Full physical address where customers can find you"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Business Description</label>
                            <textarea class="form-control" name="description" id="description" rows="3" placeholder="Tell the neighborhood about what you create, your history, and your products..."></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Register Business Profile</button>
                        </div>
                    </div>
                </form>
            </div>

        <!-- 2. State: Business Registered but Pending Approval -->
        <?php elseif ($business && $business['status'] === 'pending'): ?>
            <div class="top-navbar">
                <div>
                    <h4 class="fw-bold m-0">Store Under Review</h4>
                    <small class="text-muted">Status for: <?php echo sanitize($business['business_name']); ?></small>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-5 text-center bg-white my-5">
                <div class="text-warning fs-1 mb-3"><i class="bi bi-clock-history"></i></div>
                <h4 class="fw-bold">Business Registration Pending Approval</h4>
                <p class="text-muted max-width-600 mx-auto my-3">
                    Thank you for joining LocalTrade! Our system administrator is reviewing your business details (<strong><?php echo sanitize($business['business_name']); ?></strong>).
                    We will activate your store dashboard once approved. This process typically takes less than 24 hours.
                </p>
                <div class="mt-4">
                    <a href="/ecommerce-system/auth/logout.php" class="btn btn-outline-danger btn-sm">Sign Out</a>
                    <a href="/ecommerce-system/contact.php" class="btn btn-light btn-sm ms-2">Contact Support</a>
                </div>
            </div>

        <!-- 3. State: Business Approved - Show Live Dashboard -->
        <?php elseif ($business && $business['status'] === 'approved'): 
            // Query Metrics
            // Monthly sales
            $sales_stmt = $pdo->prepare("
                SELECT SUM(oi.quantity * oi.price) as monthly_sales 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE p.business_id = ? AND o.payment_status = 'paid' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $sales_stmt->execute([$business_id]);
            $monthly_sales = $sales_stmt->fetchColumn() ?? 0;

            // Total orders
            $orders_stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT o.id) as total_orders
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE p.business_id = ?
            ");
            $orders_stmt->execute([$business_id]);
            $total_orders = $orders_stmt->fetchColumn() ?? 0;

            // Low stock count (less than 5)
            $stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE business_id = ? AND stock < 5 AND status = 'active'");
            $stock_stmt->execute([$business_id]);
            $low_stock = $stock_stmt->fetchColumn() ?? 0;

            // Best sellers
            $best_stmt = $pdo->prepare("
                SELECT p.name, p.price, SUM(oi.quantity) as sales_count
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE p.business_id = ?
                GROUP BY p.id
                ORDER BY sales_count DESC
                LIMIT 4
            ");
            $best_stmt->execute([$business_id]);
            $best_sellers = $best_stmt->fetchAll();

            // Recent orders
            $recent_stmt = $pdo->prepare("
                SELECT DISTINCT o.id, u.full_name as customer_name, o.created_at, o.order_status, 
                       (SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.id AND product_id IN (SELECT id FROM products WHERE business_id = ?)) as seller_amount
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                JOIN users u ON o.customer_id = u.id
                WHERE p.business_id = ?
                ORDER BY o.id DESC
                LIMIT 4
            ");
            $recent_stmt->execute([$business_id, $business_id]);
            $recent_orders = $recent_stmt->fetchAll();
        ?>
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div>
                    <h4 class="fw-bold m-0"><?php echo sanitize($business['business_name']); ?> Dashboard</h4>
                    <small class="text-muted">Welcome back, Shop Owner</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="/ecommerce-system/owner/products/index.php?add=1" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 600;">
                        <?php echo strtoupper(substr($business['business_name'], 0, 2)); ?>
                    </div>
                </div>
            </div>

            <!-- Stats Widgets -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Monthly Sales</small>
                                <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo format_price($monthly_sales); ?></h3>
                            </div>
                            <div class="stat-icon bg-primary-light text-primary"><i class="bi bi-cash-coin"></i></div>
                        </div>
                        <span class="stat-badge bg-success-light text-success"><i class="bi bi-arrow-up-right me-1"></i>+12.5%</span>
                        <span class="text-muted small ms-1">from last month</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Orders</small>
                                <h3 class="fw-bold text-dark mt-1 mb-0"><?php echo $total_orders; ?></h3>
                            </div>
                            <div class="stat-icon bg-info-light text-info"><i class="bi bi-cart3"></i></div>
                        </div>
                        <span class="stat-badge bg-success-light text-success"><i class="bi bi-arrow-up-right me-1"></i>+5.2%</span>
                        <span class="text-muted small ms-1">since last week</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Low Stock Items</small>
                                <h3 class="fw-bold <?php echo $low_stock > 0 ? 'text-danger' : 'text-dark'; ?> mt-1 mb-0"><?php echo $low_stock; ?></h3>
                            </div>
                            <div class="stat-icon bg-warning-light text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                        <?php if ($low_stock > 0): ?>
                            <span class="stat-badge bg-danger-light text-danger">Needs attention</span>
                            <span class="text-muted small ms-1">immediate action</span>
                        <?php else: ?>
                            <span class="stat-badge bg-success-light text-success">Healthy stock</span>
                            <span class="text-muted small ms-1">no issues</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Revenue Trend Chart and Best Sellers -->
            <div class="row g-4 mb-5">
                <!-- Trend Chart Simulated with CSS -->
                <div class="col-lg-8">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold m-0">Revenue Overview</h5>
                                <small class="text-muted">Daily breakdown of total store revenue</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary active">Weekly</button>
                                <button class="btn btn-outline-secondary">Monthly</button>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-end px-3" style="height: 250px;">
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top opacity-50" style="height: 120px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Mon</small>
                            </div>
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top opacity-75" style="height: 180px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Tue</small>
                            </div>
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top opacity-50" style="height: 140px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Wed</small>
                            </div>
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top" style="height: 220px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Thu</small>
                            </div>
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top opacity-75" style="height: 160px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Fri</small>
                            </div>
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top" style="height: 240px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Sat</small>
                            </div>
                            <div class="d-flex flex-column align-items-center" style="width: 12%;">
                                <div class="bg-primary w-100 rounded-top opacity-75" style="height: 190px;"></div>
                                <small class="text-muted mt-2" style="font-size: 0.75rem;">Sun</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Sellers -->
                <div class="col-lg-4">
                    <div class="card border rounded-3 p-4 bg-white shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                            <h5 class="fw-bold m-0">Best Sellers</h5>
                            <a href="/ecommerce-system/owner/reports/index.php" class="text-decoration-none small">View All</a>
                        </div>

                        <div class="best-sellers-list">
                            <?php if (count($best_sellers) > 0): ?>
                                <?php foreach ($best_sellers as $item): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div style="max-width: 70%;">
                                            <h6 class="fw-semibold mb-0 small text-truncate"><?php echo sanitize($item['name']); ?></h6>
                                            <small class="text-muted"><?php echo $item['sales_count']; ?> Sales</small>
                                        </div>
                                        <span class="fw-bold text-primary small"><?php echo format_price($item['price'] * $item['sales_count']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small text-center my-5">No sales recorded yet.</p>
                            <?php endif; ?>
                            
                            <div class="border-top pt-3 mt-3 d-flex justify-content-between align-items-center small text-muted">
                                <span>Total Category Growth</span>
                                <span class="fw-bold text-success">+18.2%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0">Recent Orders</h5>
                <a href="/ecommerce-system/owner/orders/index.php" class="btn btn-sm btn-outline-secondary">View Detailed Log</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr>
                                    <td class="fw-semibold">#ORD-<?php echo str_pad($ord['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo sanitize($ord['customer_name']); ?></td>
                                    <td style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></td>
                                    <td class="fw-bold"><?php echo format_price($ord['seller_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-status status-<?php echo $ord['order_status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ord['order_status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/ecommerce-system/owner/orders/index.php" class="btn btn-sm btn-light" title="Manage Order"><i class="bi bi-pencil-square"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No orders received yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dashboard Footer -->
            <footer class="mt-5 pt-4 border-top border-muted">
                <div class="row g-4 small text-muted">
                    <div class="col-md-4">
                        <h6 class="fw-bold text-dark">LocalTrade</h6>
                        <p class="m-0">Empowering local artisans and business owners with digital storefronts to serve their neighborhood communities.</p>
                    </div>
                    <div class="col-md-2">
                        <h6 class="fw-bold text-dark">Product</h6>
                        <ul class="list-unstyled">
                            <li>Features</li>
                            <li>Sell with Us</li>
                            <li>Success Stories</li>
                        </ul>
                    </div>
                    <div class="col-md-2">
                        <h6 class="fw-bold text-dark">Company</h6>
                        <ul class="list-unstyled">
                            <li>Privacy Policy</li>
                            <li>Terms of Service</li>
                            <li>Contact Us</li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h6 class="fw-bold text-dark">Follow Us</h6>
                        <div class="d-flex justify-content-md-end gap-3 fs-5 mt-2">
                            <i class="bi bi-globe"></i>
                            <i class="bi bi-share"></i>
                        </div>
                        <p class="mt-2 mb-0">&copy; 2024 LocalTrade E-Commerce. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
