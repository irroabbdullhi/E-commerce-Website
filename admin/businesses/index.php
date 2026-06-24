<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$message = '';
$message_type = 'success';

// Handle Business status approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_business_status'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $business_id = (int)$_POST['business_id'];
        $new_status = sanitize($_POST['status']);

        $stmt = $pdo->prepare("UPDATE businesses SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $business_id])) {
            $message = 'Business status updated to ' . ucfirst($new_status) . ' successfully!';
        } else {
            $message = 'Failed to update business status.';
            $message_type = 'danger';
        }
    }
}

// Fetch all businesses with owners details
$stmt = $pdo->query("
    SELECT b.*, u.full_name as owner_name, u.email as owner_email 
    FROM businesses b 
    JOIN users u ON b.owner_id = u.id 
    ORDER BY b.id DESC
");
$businesses = $stmt->fetchAll();

$page_title = 'Manage Businesses';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Manage Businesses</h4>
                <small class="text-muted">Approve or reject local vendor storefronts</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border rounded-3 p-4 bg-white shadow-sm">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Business Storefronts</h5>
            
            <div class="table-responsive">
                <table class="table table-hover m-0">
                    <thead>
                        <tr>
                            <th>Store ID</th>
                            <th>Business Name</th>
                            <th>Owner</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 250px;">Verify Store</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($businesses) > 0): ?>
                            <?php foreach ($businesses as $bus): ?>
                                <tr>
                                    <td class="fw-semibold">#BUS-<?php echo str_pad($bus['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <h6 class="fw-bold mb-0 small"><?php echo sanitize($bus['business_name']); ?></h6>
                                        <small class="text-muted"><?php echo sanitize($bus['business_type']); ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo sanitize($bus['owner_name']); ?></div>
                                        <small class="text-muted"><?php echo sanitize($bus['owner_email']); ?></small>
                                    </td>
                                    <td><?php echo sanitize($bus['phone']); ?></td>
                                    <td><span class="small text-muted"><?php echo sanitize($bus['address']); ?></span></td>
                                    <td>
                                        <span class="badge badge-status status-<?php echo $bus['status']; ?>">
                                            <?php echo ucfirst($bus['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form action="index.php" method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="business_id" value="<?php echo $bus['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                                <option value="approved" <?php echo $bus['status'] === 'approved' ? 'selected' : ''; ?>>Approve</option>
                                                <option value="pending" <?php echo $bus['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="rejected" <?php echo $bus['status'] === 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                            </select>
                                            <button type="submit" name="update_business_status" class="btn btn-primary btn-sm">Set</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No businesses registered on the platform yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
