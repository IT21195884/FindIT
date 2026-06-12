<?php
session_start();
require_once '../../includes/db.php';

// Security check:
// Only logged-in users with admin role can approve reports
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../../login.php?error=Admin access required.");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;




// Validate report ID before running database query

if ($id <= 0) {
    header("Location: ../../admin/reports.php?error=Invalid report ID.");
    exit();
}

// Validate report ID before running database query
$stmt = $pdo->prepare("UPDATE reports SET status = 'active' WHERE id = ?");
$stmt->execute([$id]);

header("Location: ../../admin/reports.php?success=Report approved successfully.");
exit();