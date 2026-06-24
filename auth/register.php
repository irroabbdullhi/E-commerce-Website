<?php
$page_title = 'Create Account';
require_once __DIR__ . '/../includes/header.php';

$error = '';
$success = '';
$role = isset($_GET['role']) && $_GET['role'] === 'owner' ? 'owner' : 'customer';

// Form fields
$full_name = '';
$email = '';
$business_name = '';
$business_type = '';
$phone = '';
$address = '';
$description = '';

if (is_logged_in()) {
    redirect_dashboard();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $role = sanitize($_POST['role']);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($role === 'owner') {
            $business_name = sanitize($_POST['business_name']);
            $business_type = sanitize($_POST['business_type']);
            $phone = sanitize($_POST['phone']);
            $address = sanitize($_POST['address']);
            $description = sanitize($_POST['description']);
        }

        // Validation
        if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all required personal information fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address format.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif ($role === 'owner' && (empty($business_name) || empty($business_type) || empty($phone) || empty($address))) {
            $error = 'Please fill in all required business information fields.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Insert User
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $user_status = ($role === 'owner') ? 'active' : 'active'; // Owner user account is active, but business status is pending
                    
                    $u_stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                    $u_stmt->execute([$full_name, $email, $hashed_password, $role, $user_status]);
                    $user_id = $pdo->lastInsertId();

                    // If Owner, insert Business
                    if ($role === 'owner') {
                        $b_stmt = $pdo->prepare("INSERT INTO businesses (owner_id, business_name, business_type, phone, address, description, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                        $b_stmt->execute([$user_id, $business_name, $business_type, $phone, $address, $description]);
                    }

                    $pdo->commit();
                    $success = 'Registration successful! You can now log in.';
                    // Clear form
                    $full_name = $email = $business_name = $business_type = $phone = $address = $description = '';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Error during registration: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-<?php echo ($role === 'owner') ? '8' : '5'; ?>">
            <div class="card auth-card p-4 shadow">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary"><i class="bi bi-shop me-2"></i>LocalTrade</h3>
                    <p class="text-muted">Register as a <?php echo ($role === 'owner') ? 'Business Owner' : 'Customer'; ?></p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?> <a href="login.php" class="alert-link">Click here to Sign In</a>.
                    </div>
                <?php endif; ?>

                <form action="register.php?role=<?php echo $role; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="role" value="<?php echo $role; ?>">

                    <div class="row">
                        <!-- Account Details Column -->
                        <div class="<?php echo ($role === 'owner') ? 'col-md-6 border-end' : 'col-12'; ?>">
                            <h5 class="fw-bold mb-3 text-secondary">Account Information</h5>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo sanitize($full_name); ?>" required placeholder="John Doe">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize($email); ?>" required placeholder="name@example.com">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="At least 6 characters">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
                            </div>
                        </div>

                        <!-- Business Details Column (Only for Owner) -->
                        <?php if ($role === 'owner'): ?>
                            <div class="col-md-6">
                                <h5 class="fw-bold mb-3 text-secondary">Business Information</h5>
                                
                                <div class="mb-3">
                                    <label for="business_name" class="form-label">Business Name</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo sanitize($business_name); ?>" required placeholder="e.g. Portland Crafts Co.">
                                </div>

                                <div class="mb-3">
                                    <label for="business_type" class="form-label">Business Type</label>
                                    <input type="text" class="form-control" id="business_type" name="business_type" value="<?php echo sanitize($business_type); ?>" required placeholder="e.g. Home Decor, Crafts, Food">
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Business Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo sanitize($phone); ?>" required placeholder="123-456-7890">
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Business Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required placeholder="Street address, City, State, ZIP"><?php echo sanitize($address); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Tell customers about your business..."><?php echo sanitize($description); ?></textarea>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Register Account</button>
                    </div>

                    <div class="text-center">
                        <span class="text-muted">Already have an account?</span>
                        <a href="login.php" class="text-decoration-none fw-semibold">Sign In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
