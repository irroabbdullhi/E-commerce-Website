<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $success = 'System configurations updated successfully!';
    }
}

$page_title = 'Platform Settings';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Platform Settings</h4>
                <small class="text-muted">Configure site name, tax codes, and default shipping criteria</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card border rounded-3 p-4 bg-white shadow-sm max-width-800">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Global Settings</h5>
            
            <form action="index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="site_name" class="form-label">Platform Name</label>
                        <input type="text" class="form-control" name="site_name" id="site_name" value="LocalTrade" required>
                    </div>

                    <div class="col-md-6">
                        <label for="contact_email" class="form-label">Support Contact Email</label>
                        <input type="email" class="form-control" name="contact_email" id="contact_email" value="support@localtrade.com" required>
                    </div>

                    <div class="col-md-6">
                        <label for="tax_rate" class="form-label">VAT / Tax Rate (%)</label>
                        <input type="number" step="0.1" class="form-control" name="tax_rate" id="tax_rate" value="8.0" required>
                    </div>

                    <div class="col-md-6">
                        <label for="shipping_fee" class="form-label">Standard Shipping Fee ($)</label>
                        <input type="number" step="0.01" class="form-control" name="shipping_fee" id="shipping_fee" value="5.00" required>
                    </div>

                    <div class="col-md-6">
                        <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold ($)</label>
                        <input type="number" step="0.01" class="form-control" name="free_shipping_threshold" id="free_shipping_threshold" value="100.00" required>
                    </div>

                    <div class="col-md-6">
                        <label for="currency" class="form-label">Base Currency</label>
                        <select name="currency" id="currency" class="form-select">
                            <option value="USD" selected>USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">Save Platform Settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
