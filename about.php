<?php
$page_title = 'About Us';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="fw-bold mb-3">About <span class="text-primary">LocalTrade</span></h1>
            <p class="lead text-muted">We are dedicated to building sustainable neighborhoods by connecting passionate local artisans and independent businesses with conscious consumers.</p>
            <p class="text-muted">Founded in 2024, LocalTrade was born out of a simple idea: that community-driven commerce creates stronger, happier, and more resilient neighborhoods. Our platform provides the digital infrastructure for local creators, bakers, woodworkers, and farmers to showcase their goods, manage sales, and manage deliveries, while making it incredibly easy for customers to buy local.</p>
        </div>
        <div class="col-lg-6">
            <img src="https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&auto=format&fit=crop&q=80" alt="About LocalTrade" class="img-fluid rounded-3 shadow">
        </div>
    </div>

    <div class="row text-center g-4 py-5 border-top border-bottom mb-5">
        <div class="col-md-4">
            <h2 class="fw-bold text-primary">50k+</h2>
            <p class="text-muted">Active Customers</p>
        </div>
        <div class="col-md-4">
            <h2 class="fw-bold text-primary">1,200+</h2>
            <p class="text-muted">Verified Sellers</p>
        </div>
        <div class="col-md-4">
            <h2 class="fw-bold text-primary">$2.4M+</h2>
            <p class="text-muted">Returned to Local Economies</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h3 class="fw-bold">Our Core Values</h3>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <div class="fs-2 text-primary mb-2"><i class="bi bi-heart"></i></div>
                <h5>Community Support</h5>
                <p class="text-muted small">We prioritize small-scale producers and support local economies to keep wealth where it matters most.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <div class="fs-2 text-primary mb-2"><i class="bi bi-shield-check"></i></div>
                <h5>Aesthetic Integrity</h5>
                <p class="text-muted small">We vet all vendors to verify that every purchase is crafted with high-quality and sustainable materials.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded h-100 text-center">
                <div class="fs-2 text-primary mb-2"><i class="bi bi-arrow-repeat"></i></div>
                <h5>Environmental Care</h5>
                <p class="text-muted small">Reducing carbon footprint through local shipping, minimal packaging, and sustainable sourcing.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
