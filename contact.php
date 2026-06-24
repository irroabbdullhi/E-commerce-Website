<?php
$page_title = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Thank you for reaching out! We have received your message and will get back to you shortly.';
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-3 text-center">Get in Touch</h1>
            <p class="text-muted text-center mb-5">Have questions about setting up a store or need assistance with your order? Our local support team is here to help.</p>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="row g-5">
                <div class="col-md-5">
                    <h5 class="fw-bold mb-4">Contact Information</h5>
                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-geo-alt text-primary fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Address</h6>
                            <p class="text-muted small">123 Market Street, Suite 400<br>Portland, OR 97201</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-telephone text-primary fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Phone</h6>
                            <p class="text-muted small">1 (800) 555-LT-LOCAL</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <i class="bi bi-envelope text-primary fs-5"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Email</h6>
                            <p class="text-muted small">support@localtrade.com</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <h5 class="fw-bold mb-4">Send Us a Message</h5>
                    <form action="contact.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" required placeholder="John Doe">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" required placeholder="john@example.com">
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" required placeholder="How can we help?">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="4" required placeholder="Type your message here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary py-2 px-4 w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
