<?php
$page_title = 'Reset Password';
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';

// Check if we have reset permission in session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token_expires']) || time() > $_SESSION['reset_token_expires']) {
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_token_expires']);
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Update password
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($stmt->execute([$hashed, $_SESSION['reset_email']])) {
                $success = 'Password has been reset successfully!';
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token_expires']);
            } else {
                $error = 'Failed to reset password. Please try again.';
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
                    <h3 class="fw-bold text-primary"><i class="bi bi-shield-lock me-2"></i>Reset Password</h3>
                    <p class="text-muted">Enter a new secure password for your account</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?><br><br>
                        <a href="login.php" class="btn btn-primary w-100">Sign In Now</a>
                    </div>
                <?php else: ?>
                    <form action="reset_password.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="At least 6 characters">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Reset Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
