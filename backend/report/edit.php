<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=Please login first.");
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$type = sanitize($_POST['type'] ?? '');
$category = sanitize($_POST['category'] ?? '');
$title = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$suburb = sanitize($_POST['suburb'] ?? '');
$dateOccurred = $_POST['date_occurred'] ?? '';

if ($id <= 0 || empty($type) || empty($category) || empty($title) || empty($description) || empty($suburb) || empty($dateOccurred)) {
    header("Location: ../../report-edit.php?id=$id&error=Please fill in all fields.");
    exit();
}

if (!in_array($type, ['lost', 'found'], true)) {
    header("Location: ../../report-edit.php?id=$id&error=Invalid report type.");
    exit();
}

// Make sure the report belongs to the logged-in user
$checkStmt = $pdo->prepare("SELECT id FROM reports WHERE id = ? AND user_id = ?");
$checkStmt->execute([$id, $_SESSION['user_id']]);
$report = $checkStmt->fetch();

if (!$report) {
    header("Location: ../../dashboard.php?error=Report not found or access denied.");
    exit();
}

$stmt = $pdo->prepare("
    UPDATE reports
    SET report_type = ?, category = ?, title = ?, description = ?, suburb = ?, report_date = ?
    WHERE id = ? AND user_id = ?
");

$stmt->execute([
    $type,
    $category,
    $title,
    $description,
    $suburb,
    $dateOccurred,
    $id,
    $_SESSION['user_id']
]);

header("Location: ../../dashboard.php?success=Report updated successfully.");
exit();
?>