<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/admin_helpers.php';
 
// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../login.php?error=Access denied.");
    exit();
}
 
// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: ../../admin/moderation.php?error=Invalid request.");
    exit();
}
 
$action   = sanitize($_POST['action'] ?? '');
$reportId = (int)($_POST['report_id'] ?? 0);
$adminId  = (int)$_SESSION['user_id'];
 
if (!$reportId || !in_array($action, ['approve', 'hide', 'delete'], true)) {
    header("Location: ../../admin/moderation.php?error=Invalid action.");
    exit();
}
 
// Verify report exists
$stmt = $pdo->prepare("SELECT id FROM reports WHERE id = ?");
$stmt->execute([$reportId]);
if (!$stmt->fetch()) {
    header("Location: ../../admin/moderation.php?error=Report not found.");
    exit();
}
 
// Perform action
switch ($action) {
    case 'approve':
        $pdo->prepare("UPDATE reports SET status = 'active' WHERE id = ?")
            ->execute([$reportId]);
        logAdminAction($pdo, $adminId, 'approve_report', $reportId, 'report');
        $message = "Report approved and published successfully.";
        break;
 
    case 'hide':
        $pdo->prepare("UPDATE reports SET status = 'hidden' WHERE id = ?")
            ->execute([$reportId]);
        logAdminAction($pdo, $adminId, 'hide_report', $reportId, 'report');
        $message = "Report hidden from public view.";
        break;
 
    case 'delete':
        $pdo->prepare("DELETE FROM reports WHERE id = ?")
            ->execute([$reportId]);
        logAdminAction($pdo, $adminId, 'delete_report', $reportId, 'report');
        $message = "Report permanently deleted.";
        break;
}
 
header("Location: ../../admin/moderation.php?success=" . urlencode($message));
exit();
 
require_once __DIR__ . '/../../includes/admin_helpers.php';
?>