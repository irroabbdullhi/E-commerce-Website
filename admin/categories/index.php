<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$message = '';
$message_type = 'success';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $name = sanitize($_POST['category_name']);
        if (empty($name)) {
            $message = 'Category name cannot be empty.';
            $message_type = 'danger';
        } else {
            // Check uniqueness
            $chk = $pdo->prepare("SELECT id FROM categories WHERE category_name = ?");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                $message = 'Category name already exists.';
                $message_type = 'danger';
            } else {
                $ins = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                if ($ins->execute([$name])) {
                    $message = 'Category added successfully!';
                }
            }
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $cat_id = (int)$_POST['category_id'];
        $name = sanitize($_POST['category_name']);
        if (empty($name)) {
            $message = 'Category name cannot be empty.';
            $message_type = 'danger';
        } else {
            // Check uniqueness
            $chk = $pdo->prepare("SELECT id FROM categories WHERE category_name = ? AND id != ?");
            $chk->execute([$name, $cat_id]);
            if ($chk->fetch()) {
                $message = 'Category name already exists.';
                $message_type = 'danger';
            } else {
                $upd = $pdo->prepare("UPDATE categories SET category_name = ? WHERE id = ?");
                if ($upd->execute([$name, $cat_id])) {
                    $message = 'Category updated successfully!';
                }
            }
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Check if category is used in products (Restricted Foreign Key)
    $chk_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $chk_stmt->execute([$del_id]);
    $count = $chk_stmt->fetchColumn();

    if ($count > 0) {
        $message = 'Cannot delete category. There are ' . $count . ' products associated with it. Move or delete the products first.';
        $message_type = 'danger';
    } else {
        $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($del->execute([$del_id])) {
            $message = 'Category deleted successfully.';
        }
    }
}

// Fetch categories
$categories = get_categories($pdo);

// Edit Setup
$edit_cat = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_cat = $edit_stmt->fetch();
}

$page_title = 'Manage Categories';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4 class="fw-bold m-0">Manage Categories</h4>
                <small class="text-muted">Organize catalog product sections</small>
            </div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Add/Edit Category Form Column -->
            <div class="col-lg-4">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">
                        <?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?>
                    </h5>
                    
                    <form action="index.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                        
                        <?php if ($edit_cat): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_cat['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" id="category_name" required value="<?php echo $edit_cat ? sanitize($edit_cat['category_name']) : ''; ?>" placeholder="e.g. Handmade Toys">
                        </div>

                        <?php if ($edit_cat): ?>
                            <button type="submit" name="edit_category" class="btn btn-primary w-100">Save Changes</button>
                            <a href="index.php" class="btn btn-light w-100 mt-2">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Categories List Column -->
            <div class="col-lg-8">
                <div class="card border rounded-3 p-4 bg-white shadow-sm">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Categories</h5>

                    <div class="table-responsive">
                        <table class="table table-hover m-0">
                            <thead>
                                <tr>
                                    <th>Category ID</th>
                                    <th>Category Name</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="fw-semibold">#CAT-<?php echo str_pad($cat['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo sanitize($cat['category_name']); ?></td>
                                        <td class="text-end">
                                            <a href="index.php?edit_id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-light text-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                            <a href="index.php?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-light text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');"><i class="bi bi-trash-fill"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
