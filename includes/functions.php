<?php
// Shared Utility Functions
require_once __DIR__ . '/../config/database.php';

// Format currency
function format_price($price) {
    return '$' . number_format((double)$price, 2);
}

// Generate stars for rating
function render_stars($rating) {
    $stars = '';
    $rating = (int)round($rating);
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            $stars .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    return $stars;
}

// Secure file upload handler
function handle_file_upload($file, $target_subfolder = 'products') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'No file uploaded or upload error occurred.'];
    }

    $file_size = $file['size'];
    if ($file_size > MAX_FILE_SIZE) {
        return ['status' => false, 'message' => 'File size exceeds maximum limit of ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.'];
    }

    $file_name = basename($file['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        return ['status' => false, 'message' => 'Invalid file extension. Allowed extensions: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    // Generate unique file name to prevent collision
    $new_file_name = uniqid($target_subfolder . '_', true) . '.' . $file_ext;
    
    // Ensure target upload directory exists
    $upload_path = UPLOAD_DIR . $target_subfolder . '/';
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }

    $dest_path = $upload_path . $new_file_name;
    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        return ['status' => true, 'file_name' => $target_subfolder . '/' . $new_file_name];
    } else {
        return ['status' => false, 'message' => 'Failed to move uploaded file. Check folder permissions.'];
    }
}

// Helper to fetch average rating of a product
function get_product_rating($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();
    return [
        'rating' => $result['avg_rating'] ? round($result['avg_rating'], 1) : 0,
        'count' => $result['count'] ?? 0
    ];
}

// Fetch all categories
function get_categories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
    return $stmt->fetchAll();
}
?>
