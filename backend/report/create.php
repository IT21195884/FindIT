<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=Please login first.");
    exit();
}

$type = sanitize($_POST['type'] ?? '');
$category = sanitize($_POST['category'] ?? '');
$title = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$suburb = sanitize($_POST['suburb'] ?? '');
$dateOccurred = $_POST['date_occurred'] ?? '';

if (
    empty($type) ||
    empty($category) ||
    empty($title) ||
    empty($description) ||
    empty($suburb) ||
    empty($dateOccurred)
) {
    header("Location: ../../report-create.php?error=Please fill in all fields.");
    exit();
}

if (!in_array($type, ['lost', 'found'], true)) {
    header("Location: ../../report-create.php?error=Invalid report type.");
    exit();
}

$imagePaths = [null, null, null];

if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $totalImages = count(array_filter($_FILES['images']['name']));

    if ($totalImages > 3) {
        header("Location: ../../report-create.php?error=You can upload a maximum of 3 images.");
        exit();
    }

    $uploadDir = __DIR__ . '/../../uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!is_writable($uploadDir)) {
        header("Location: ../../report-create.php?error=Upload folder is not writable.");
        exit();
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024;

    $savedIndex = 0;

    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if (empty($_FILES['images']['name'][$i])) {
            continue;
        }

        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
            header("Location: ../../report-create.php?error=Image upload failed. Error code: " . $_FILES['images']['error'][$i]);
            exit();
        }

        if ($_FILES['images']['size'][$i] > $maxFileSize) {
            header("Location: ../../report-create.php?error=Each image must be less than 5MB.");
            exit();
        }

        $originalName = $_FILES['images']['name'][$i];
        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            header("Location: ../../report-create.php?error=Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.");
            exit();
        }

        $mimeType = mime_content_type($_FILES['images']['tmp_name'][$i]);

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            header("Location: ../../report-create.php?error=Invalid image file type.");
            exit();
        }

        $newFileName = time() . '_' . uniqid('report_', true) . '.' . $fileExtension;
        $targetFile = $uploadDir . $newFileName;

        if (!move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFile)) {
            header("Location: ../../report-create.php?error=Failed to save uploaded image.");
            exit();
        }

        $imagePaths[$savedIndex] = 'uploads/' . $newFileName;
        $savedIndex++;
    }
}

$stmt = $pdo->prepare("
    INSERT INTO reports (
        user_id,
        report_type,
        category,
        title,
        description,
        suburb,
        report_date,
        image_path,
        image_1,
        image_2,
        image_3,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
");

$stmt->execute([
    $_SESSION['user_id'],
    $type,
    $category,
    $title,
    $description,
    $suburb,
    $dateOccurred,
    $imagePaths[0],
    $imagePaths[0],
    $imagePaths[1],
    $imagePaths[2]
]);

header("Location: ../../dashboard.php?success=Report submitted successfully.");
exit();
?>