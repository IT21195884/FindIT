<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=Please login first.");
    exit();
}

// Generate and validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../report-create.php?error=Invalid request. Please try again.");
        exit();
    }
}

// Sanitize POST inputs
$type        = sanitize($_POST['type'] ?? '');
$category    = sanitize($_POST['category'] ?? '');
$title       = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$suburb      = sanitize($_POST['suburb'] ?? '');
$dateOccurred = $_POST['date_occurred'] ?? '';

// Validate required fields
if (empty($type) || empty($category) || empty($title) || empty($description) || empty($suburb) || empty($dateOccurred)) {
    header("Location: ../../report-create.php?error=Please fill in all required fields.");
    exit();
}

// Validate report type
if (!in_array($type, ['lost', 'found'], true)) {
    header("Location: ../../report-create.php?error=Invalid report type.");
    exit();
}

// Validate category
$allowedCategories = ['Pets', 'Electronics', 'Documents', 'Missing Persons'];
if (!in_array($category, $allowedCategories, true)) {
    header("Location: ../../report-create.php?error=Invalid category.");
    exit();
}

// Handle optional image upload
$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
    header("Location: ../../report-create.php?error=Invalid image type. Only JPG, PNG, and GIF are allowed.");
    exit();
        }
        if ($_FILES['image']['size'] > $maxSize) {
    header("Location: ../../report-create.php?error=Image too large. Maximum size is 2MB.");
    exit();
        }

    // Upload to Cloudinary
        require_once __DIR__ . '/../../vendor/autoload.php';
        \Cloudinary\Configuration\Configuration::instance([
        'cloud' => [
        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
        'api_key'    => getenv('CLOUDINARY_API_KEY'),
        'api_secret' => getenv('CLOUDINARY_API_SECRET'),
    ],
    'url'   => ['secure' => true]
    ]);

        $cloudinary = new \Cloudinary\Cloudinary();
        $uploadApi  = $cloudinary->uploadApi();

    try {
    $result    = $uploadApi->upload($_FILES['image']['tmp_name'], ['folder' => 'findit']);
    $imagePath = $result['secure_url'];
    } catch (\Exception $e) {
    error_log('Cloudinary upload failed: ' . $e->getMessage());
    header("Location: ../../report-create.php?error=Image upload failed. Please try again.");
    exit();
    }
}

// Insert report — status defaults to 'pending' (awaiting admin approval)
$stmt = $pdo->prepare("
    INSERT INTO reports (user_id, report_type, category, title, description, suburb, report_date, image_path, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([
    $_SESSION['user_id'],
    $type,
    $category,
    $title,
    $description,
    $suburb,
    $dateOccurred,
    $imagePath
]);

$newReportId = $pdo->lastInsertId();

// Trigger match detection for the new report
require_once __DIR__ . '/../match/detect.php';
runMatchDetection($pdo, (int)$newReportId);

// Redirect back to dashboard
header("Location: ../../dashboard.php?success=Report submitted successfully. It will appear publicly once reviewed by an administrator.");
exit();
?>