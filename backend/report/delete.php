<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=Please login first.");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ../../dashboard.php?error=Invalid report ID.");
    exit();
}

$stmt = $pdo->prepare("DELETE FROM reports WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

header("Location: ../../dashboard.php?success=Report deleted successfully.");
exit();
?>