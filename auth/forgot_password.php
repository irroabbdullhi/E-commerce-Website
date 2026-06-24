<?php
$page_title = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $email = sanitize($_POST['email']);
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // In production, send a secure email with token.
                // For this demo/production XAMPP instance, we generate a session token to reset directly.
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token_expires'] = time() + 1800; // 30 minutes
                $success = 'Password reset request received. For testing/demo convenience, you can now reset your password directly.';
            } else {
                // To prevent user enumeration, we still show success in some apps, but let's give an explicit status or mock success.
                $error = 'No user account found with that email address.';
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center my-5">
        <div class="col-md-5">
            <div class="card auth-card p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary"><i class="bi bi-key me-2"></i>Forgot Password</h3>
                    <p class="text-muted">Enter your email and we'll help you reset your password</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?><br><br>
                        <a href="reset_password.php" class="btn btn-sm btn-primary">Reset Password Now</a>
                    </div>
                <?php else: ?>
                    <form action="forgot_password.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Send Reset Link</button>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">Back to Sign In</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
