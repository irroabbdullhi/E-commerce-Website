<?php
$page_title = 'Shop Products';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Filter Variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build Query
$query = "
    SELECT p.*, c.category_name, b.business_name, b.address as business_address 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    JOIN businesses b ON p.business_id = b.id 
    WHERE p.status = 'active'
";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR b.business_name LIKE ?)";
    $search_val = "%$search%";
    $params[] = $search_val;
    $params[] = $search_val;
    $params[] = $search_val;
}

if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($location)) {
    $query .= " AND b.address LIKE ?";
    $params[] = "%$location%";
}

// Sorting logic
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'latest':
    default:
        $query .= " ORDER BY p.id DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch categories for sidebar filter
$categories = get_categories($pdo);
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Filter Column -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                
                <!-- Category Filter -->
                <div class="mb-4">
                    <h6 class="fw-bold text-secondary mb-2">Categories</h6>
                    <div class="list-group list-group-flush">
                        <a href="shop.php?category=0&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="list-group-item list-group-item-action border-0 px-0 <?php echo $category_id === 0 ? 'fw-bold text-primary' : ''; ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="shop.php?category=<?php echo $cat['id']; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="list-group-item list-group-item-action border-0 px-0 <?php echo $category_id === $cat['id'] ? 'fw-bold text-primary' : ''; ?>">
                                <?php echo sanitize($cat['category_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sort Filter -->
                <div class="mb-4">
                    <h6 class="fw-bold text-secondary mb-2">Sort By</h6>
                    <select class="form-select" onchange="location = this.value;">
                        <option value="shop.php?category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>&sort=latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest Arrivals</option>
                        <option value="shop.php?category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>&sort=price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="shop.php?category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>&sort=price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="shop.php?category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>&sort=name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Product Name</option>
                    </select>
                </div>

                <!-- Reset Filters Button -->
                <a href="shop.php" class="btn btn-outline-secondary w-100">Reset Filters</a>
            </div>
        </div>

        <!-- Products Grid Column -->
        <div class="col-lg-9">
            <!-- Header Info -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold m-0">
                    <?php if (!empty($search)): ?>
                        Search results for "<?php echo sanitize($search); ?>"
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                    <span class="text-muted fs-6 fw-normal">(<?php echo count($products); ?> items found)</span>
                </h4>
            </div>

            <!-- Products Grid -->
            <?php if (count($products) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): 
                        $rating_info = get_product_rating($pdo, $product['id']);
                        
                        // Pick a default image if none uploaded
                        $img_src = !empty($product['image']) ? '/ecommerce-system/assets/uploads/' . $product['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&auto=format&fit=crop&q=80';
                    ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="product-card shadow-sm">
                                <div class="product-img-wrapper">
                                    <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($product['name']); ?>">
                                    <span class="product-badge bg-primary"><?php echo sanitize($product['category_name']); ?></span>
                                </div>
                                <div class="product-details d-flex flex-column flex-grow-1 p-3">
                                    <small class="text-muted mb-1"><i class="bi bi-shop me-1"></i><?php echo sanitize($product['business_name']); ?></small>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark"><?php echo sanitize($product['name']); ?></a>
                                    </h6>
                                    
                                    <!-- Reviews -->
                                    <div class="mb-2" style="font-size: 0.85rem;">
                                        <?php echo render_stars($rating_info['rating']); ?>
                                        <span class="text-muted ms-1">(<?php echo $rating_info['count']; ?>)</span>
                                    </div>
                                    
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary fs-5"><?php echo format_price($product['price']); ?></span>
                                        
                                        <!-- Actions -->
                                        <div class="btn-group">
                                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm" title="View details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm p-5 text-center bg-white my-5">
                    <div class="text-muted fs-1 mb-3"><i class="bi bi-search"></i></div>
                    <h5 class="fw-bold">No Products Found</h5>
                    <p class="text-muted">We couldn't find any products matching your search criteria. Try removing some filters.</p>
                    <a href="shop.php" class="btn btn-primary align-self-center mt-3">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
