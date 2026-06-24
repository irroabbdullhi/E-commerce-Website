<?php
$page_title = 'Empower Your Local Community';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch a few featured products for the homepage
$stmt = $pdo->query("
    SELECT p.*, b.business_name 
    FROM products p 
    JOIN businesses b ON p.business_id = b.id 
    WHERE p.status = 'active' 
    ORDER BY p.id DESC 
    LIMIT 4
");
$featured_products = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold text-dark mb-3">Empower Your <span class="text-primary">Local Community</span></h1>
                <p class="lead text-muted mb-4">The modern marketplace connecting skilled local artisans with neighbors who value quality, sustainability, and community impact.</p>
                <div class="d-flex gap-3 mb-4">
                    <a href="shop.php" class="btn btn-primary btn-lg px-4"><i class="bi bi-bag-fill me-2"></i>Shop Local</a>
                    <a href="about.php" class="btn btn-outline-secondary btn-lg px-4">Learn More</a>
                </div>
                <div class="d-flex gap-4 text-muted" style="font-size: 0.9rem;">
                    <span><i class="bi bi-patch-check-fill text-primary me-2"></i>Verified Artisans</span>
                    <span><i class="bi bi-truck text-primary me-2"></i>Fast Local Delivery</span>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- Grid of local/artisan style graphics (CSS illustration) -->
                <div class="row g-3">
                    <div class="col-7">
                        <div class="bg-secondary text-white rounded-3 p-4 d-flex align-items-end" style="height: 250px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=600&auto=format&fit=crop&q=80') center/cover;">
                            <div>
                                <span class="badge bg-primary mb-2">Artisan Pottery</span>
                                <h5 class="fw-bold m-0">Handmade Stoneware</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="bg-primary text-white rounded-3 p-4 d-flex align-items-end mb-3" style="height: 118px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&auto=format&fit=crop&q=80') center/cover;">
                            <h6 class="fw-bold m-0">Organic Crops</h6>
                        </div>
                        <div class="bg-dark text-white rounded-3 p-4 d-flex align-items-end" style="height: 118px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400&auto=format&fit=crop&q=80') center/cover;">
                            <h6 class="fw-bold m-0">Scented Oils</h6>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="bg-info text-white rounded-3 p-4 d-flex align-items-end" style="height: 150px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1520408222757-6f9f95d87d5d?w=800&auto=format&fit=crop&q=80') center/cover;">
                            <div>
                                <h5 class="fw-bold m-0">Leather goods & Wood carvings</h5>
                                <p class="small text-white-50 m-0">Made by local experts in Portland.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search block section -->
<section class="py-5 bg-white border-bottom">
    <div class="container text-center">
        <h3 class="fw-bold mb-4">Find what you need, nearby</h3>
        <form action="shop.php" method="GET" class="row justify-content-center g-2 max-width-800 mx-auto">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                    <input type="text" name="location" class="form-control border-start-0 bg-light" placeholder="Your City or Zip" value="Portland, OR">
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Search products, categories, or shops...">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 py-2">Search</button>
            </div>
        </form>
    </div>
</section>

<!-- Discover Section -->
<section class="py-5 bg-light border-bottom">
    <div class="container">
        <h3 class="fw-bold mb-4 text-center">Discover Your Community</h3>
        <div class="row g-4">
            <!-- Left Side: Artisan Plaza Featured Card -->
            <div class="col-lg-6">
                <div class="card border-0 rounded-3 text-white h-100 overflow-hidden shadow-sm" style="min-height: 400px; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.85)), url('https://images.unsplash.com/photo-1533900298318-6b8da08a523e?w=800&auto=format&fit=crop&q=80') center/cover;">
                    <div class="card-body d-flex flex-column justify-content-end p-4">
                        <span class="badge bg-danger align-self-start mb-2">FEATURED MARKET</span>
                        <h4 class="fw-bold">The Artisan Plaza</h4>
                        <p class="text-white-50">Meet 50+ local creators this weekend at the central plaza event.</p>
                        <a href="shop.php" class="text-white fw-semibold text-decoration-none">View Details <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <!-- Right Side: Grid of 3 Cards -->
            <div class="col-lg-6 d-flex flex-column justify-content-between gap-4">
                <!-- Card 1 -->
                <div class="card border-0 rounded-3 shadow-sm bg-white p-3 flex-grow-1" style="background: linear-gradient(to right, rgba(255,255,255,0.9), rgba(255,255,255,0.95)), url('https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=400&auto=format&fit=crop&q=80') right/cover;">
                    <div class="card-body">
                        <h5 class="fw-bold">Home Decor</h5>
                        <p class="text-muted small">Unique pieces that tell a story.</p>
                        <a href="shop.php?category=1" class="text-primary fw-semibold text-decoration-none">Browse Collection</a>
                    </div>
                </div>
                <!-- Row with Card 2 and 3 -->
                <div class="row g-4 flex-grow-1">
                    <div class="col-md-6">
                        <div class="card border-0 rounded-3 shadow-sm bg-white h-100 p-3">
                            <div class="card-body">
                                <div class="text-success fs-3 mb-2"><i class="bi bi-recycle"></i></div>
                                <h6 class="fw-bold">Sustainable Living</h6>
                                <p class="text-muted small">Zero-waste products from local shops.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 rounded-3 shadow-sm bg-primary text-white h-100 p-3">
                            <div class="card-body">
                                <div class="fs-3 mb-2"><i class="bi bi-stars"></i></div>
                                <h6 class="fw-bold">Join the Club</h6>
                                <p class="text-white-50 small">Get exclusive rewards for shopping local.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Grow Your Business Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0 text-center">
                <!-- Custom Dashboard Illustration -->
                <div class="bg-light p-4 rounded-3 border shadow-sm">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-app-indicator text-primary fs-4"></i>
                            <span class="fw-bold text-dark">LocalTrade Analytics</span>
                        </div>
                        <span class="badge bg-success">Live</span>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-white p-3 rounded border text-start">
                                <small class="text-muted">Monthly Sales</small>
                                <h4 class="fw-bold text-dark m-0">$12,482.00</h4>
                                <small class="text-success"><i class="bi bi-graph-up"></i> +12.5%</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-3 rounded border text-start">
                                <small class="text-muted">Total Orders</small>
                                <h4 class="fw-bold text-dark m-0">432</h4>
                                <small class="text-success"><i class="bi bi-graph-up"></i> +5.2%</small>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded border text-start" style="height: 120px;">
                        <small class="text-muted mb-2 d-block">Revenue Trend</small>
                        <div class="d-flex justify-content-between align-items-end h-75 pb-2">
                            <div class="bg-primary opacity-25 rounded-top" style="width: 12%; height: 30%;"></div>
                            <div class="bg-primary opacity-50 rounded-top" style="width: 12%; height: 50%;"></div>
                            <div class="bg-primary opacity-25 rounded-top" style="width: 12%; height: 40%;"></div>
                            <div class="bg-primary rounded-top" style="width: 12%; height: 85%;"></div>
                            <div class="bg-primary opacity-75 rounded-top" style="width: 12%; height: 70%;"></div>
                            <div class="bg-primary rounded-top" style="width: 12%; height: 95%;"></div>
                            <div class="bg-primary opacity-75 rounded-top" style="width: 12%; height: 65%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 ps-lg-5">
                <h2 class="fw-bold text-dark mb-4">Grow Your Business With LocalTrade</h2>
                <p class="text-muted mb-4">We provide the digital tools and community reach you need to scale your local passion into a thriving enterprise.</p>
                
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="bg-primary-light text-primary rounded p-2"><i class="bi bi-bar-chart-line fs-5"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Powerful Insights</h6>
                        <p class="text-muted small m-0">Track your growth with professional-grade analytics and customer behavior data.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="bg-primary-light text-primary rounded p-2"><i class="bi bi-people fs-5"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Community First</h6>
                        <p class="text-muted small m-0">Connect directly with customers who care about supporting local businesses.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-primary-light text-primary rounded p-2"><i class="bi bi-wallet2 fs-5"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Lower Fees</h6>
                        <p class="text-muted small m-0">Our fair commission structure keeps more profit in the hands of the makers.</p>
                    </div>
                </div>

                <a href="auth/register.php?role=owner" class="btn btn-primary py-2 px-4">Register as Business</a>
            </div>
        </div>
    </div>
</section>

<!-- Call To Action Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center py-4">
        <h2 class="fw-bold mb-3">Ready to support your neighborhood?</h2>
        <p class="text-white-50 mb-4 max-width-600 mx-auto">Join 50,000+ neighbors already trading locally and making a difference in their communities.</p>
        <form class="row justify-content-center g-2 max-width-500 mx-auto">
            <div class="col-8">
                <input type="email" class="form-control py-2 border-0" placeholder="Enter your email">
            </div>
            <div class="col-4">
                <button type="button" class="btn btn-light w-100 py-2 fw-semibold">Get Started</button>
            </div>
        </form>
        <small class="text-white-50 d-block mt-3" style="font-size: 0.75rem;">By signing up, you agree to our Terms and Privacy Policy.</small>
    </div>
</section>

<!-- Simple Footer Links -->
<footer class="py-5 bg-white border-top">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <h5 class="fw-bold text-primary mb-3">LocalTrade</h5>
                <p class="text-muted small">Empowering local artisans and business owners with digital storefronts to serve their neighborhood communities.</p>
            </div>
            <div class="col-md-3">
                <h6 class="fw-bold text-dark mb-3">Explore</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="shop.php" class="text-muted text-decoration-none">Shop All</a></li>
                    <li class="mb-2"><a href="shop.php?category=1" class="text-muted text-decoration-none">Home Decor</a></li>
                    <li class="mb-2"><a href="shop.php?category=2" class="text-muted text-decoration-none">Kitchenware</a></li>
                    <li class="mb-2"><a href="shop.php?category=3" class="text-muted text-decoration-none">Beverages</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="fw-bold text-dark mb-3">For Business</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="auth/register.php?role=owner" class="text-muted text-decoration-none">Sell on LocalTrade</a></li>
                    <li class="mb-2"><a href="auth/login.php" class="text-muted text-decoration-none">Merchant Dashboard</a></li>
                    <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none">Success Stories</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="fw-bold text-dark mb-3">Support</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="contact.php" class="text-muted text-decoration-none">Contact Us</a></li>
                    <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="border-top pt-3 text-center text-muted small">
            &copy; 2024 LocalTrade E-Commerce. All rights reserved.
        </div>
    </div>
</footer>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
