<?php
$page_title = 'Sign In';
require_once __DIR__ . '/../includes/header.php';

$error = '';
$email = '';

if (is_logged_in()) {
    redirect_dashboard();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection check
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            // Find user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'inactive') {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    // Start secure user session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Check if business owner has a business profile
                    if ($user['role'] === 'owner') {
                        $b_stmt = $pdo->prepare("SELECT id FROM businesses WHERE owner_id = ? LIMIT 1");
                        $b_stmt->execute([$user['id']]);
                        $business = $b_stmt->fetch();
                        if ($business) {
                            $_SESSION['business_id'] = $business['id'];
                        } else {
                            $_SESSION['business_id'] = null; // Needs to register business
                        }
                    }

                    // Regenerate session id to prevent fixation
                    session_regenerate_id(true);

                    // Redirect
                    if (isset($_SESSION['redirect_url'])) {
                        $url = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']);
                        header("Location: " . $url);
                    } else {
                        redirect_dashboard();
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
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
                    <h3 class="fw-bold text-primary"><i class="bi bi-shop me-2"></i>LocalTrade</h3>
                    <p class="text-muted">Sign in to your account to continue</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize($email); ?>" required placeholder="name@example.com">
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label">Password</label>
                            <a href="forgot_password.php" class="text-decoration-none" style="font-size: 0.85rem;">Forgot password?</a>
                        </div>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Sign In</button>
                    
                    <div class="text-center">
                        <span class="text-muted">New to LocalTrade?</span><br>
                        <a href="register.php?role=customer" class="text-decoration-none fw-semibold">Create Customer Account</a> or 
                        <a href="register.php?role=owner" class="text-decoration-none fw-semibold">Register your Business</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
