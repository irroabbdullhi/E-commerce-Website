<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('owner');

$business_id = $_SESSION['business_id'];
if (!$business_id) {
    header("Location: ../dashboard.php");
    exit;
}

$error = '';
$success = '';

// Fetch current details
$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $logo_name = $business['logo'];
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $upload_res = handle_file_upload($_FILES['logo'], 'logos');
                if ($upload_res['status']) {
                    $logo_name = $upload_res['file_name'];
                } else {
                    $error = $upload_res['message'];
                }
            }

            if (empty($error)) {
                $upd = $pdo->prepare("UPDATE businesses SET business_name = ?, business_type = ?, phone = ?, address = ?, logo = ?, description = ? WHERE id = ?");
                if ($upd->execute([$b_name, $b_type, $phone, $address, $logo_name, $desc, $business_id])) {
                    $success = 'Business profile updated successfully!';
                    // Refresh data
                    $stmt->execute([$business_id]);
                    $business = $stmt->fetch();
                } else {
                    $error = 'Failed to update profile details.';
                }
            }
        }
    }
}

$page_title = 'Store Profile';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Store Profile Settings</h4>
                <small class="text-muted">Manage your public storefront information</small>
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
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="business_name" class="form-label">Business Name</label>
                        <input type="text" class="form-control" name="business_name" id="business_name" required value="<?php echo sanitize($business['business_name']); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="business_type" class="form-label">Business Type</label>
                        <input type="text" class="form-control" name="business_type" id="business_type" required value="<?php echo sanitize($business['business_type']); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Business Phone</label>
                        <input type="text" class="form-control" name="phone" id="phone" required value="<?php echo sanitize($business['phone']); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="logo" class="form-label">Upload New Logo</label>
                        <input type="file" class="form-control" name="logo" id="logo">
                        <?php if (!empty($business['logo'])): ?>
                            <div class="mt-2 small text-muted">Current Logo: <?php echo basename($business['logo']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label">Physical Store Address</label>
                        <textarea class="form-control" name="address" id="address" rows="2" required><?php echo sanitize($business['address']); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Store Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"><?php echo sanitize($business['description']); ?></textarea>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">Save Profile Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
