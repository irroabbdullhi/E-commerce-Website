<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('customer');

$customer_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$customer_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        if (isset($_POST['update_profile'])) {
            $name = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);

            if (empty($name) || empty($email)) {
                $error = 'Please fill in all fields.';
            } else {
                // Check if email already used by another user
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chk->execute([$email, $customer_id]);
                if ($chk->fetch()) {
                    $error = 'This email is already in use by another account.';
                } else {
                    $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    if ($upd->execute([$name, $email, $customer_id])) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $success = 'Profile updated successfully!';
                        // Refresh user data
                        $user['full_name'] = $name;
                        $user['email'] = $email;
                    }
                }
            }
        } elseif (isset($_POST['change_password'])) {
            $old_pass = $_POST['old_password'];
            $new_pass = $_POST['new_password'];
            $confirm_pass = $_POST['confirm_password'];

            if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
                $error = 'Please fill in all password fields.';
            } elseif ($new_pass !== $confirm_pass) {
                $error = 'New passwords do not match.';
            } elseif (strlen($new_pass) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                // Verify old password
                if (password_verify($old_pass, $user['password'])) {
                    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($upd->execute([$hashed, $customer_id])) {
                        $success = 'Password changed successfully!';
                        // Refresh user data
                        $stmt->execute([$customer_id]);
                        $user = $stmt->fetch();
                    }
                } else {
                    $error = 'Incorrect old password.';
                }
            }
        }
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">My Profile</h4>
                <small class="text-muted">Manage your account information and security settings</small>
            </div>
            <a href="/ecommerce-system/customer/dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Edit Profile Details -->
            <div class="col-md-6">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Profile Information</h5>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="full_name" value="<?php echo sanitize($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" id="email" value="<?php echo sanitize($user['email']); ?>" required>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col-md-6">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Change Password</h5>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="old_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="old_password" id="old_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="new_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-outline-danger">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
