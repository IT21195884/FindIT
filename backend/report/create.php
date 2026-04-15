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

if (empty($type) || empty($category) || empty($title) || empty($description) || empty($suburb) || empty($dateOccurred)) {
    header("Location: ../../report-create.php?error=Please fill in all fields.");
    exit();
}

if (!in_array($type, ['lost', 'found'], true)) {
    header("Location: ../../report-create.php?error=Invalid report type.");
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO reports (user_id, report_type, category, title, description, suburb, report_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
");

$stmt->execute([
    $_SESSION['user_id'],
    $type,
    $category,
    $title,
    $description,
    $suburb,
    $dateOccurred
]);

header("Location: ../../dashboard.php?success=Report submitted successfully.");
exit();
?>