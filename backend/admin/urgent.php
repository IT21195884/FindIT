<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../backend/email/sendEmail.php';
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

$reportId = (int)($_POST['report_id'] ?? 0);
$adminId  = (int)$_SESSION['user_id'];

if (!$reportId) {
    header("Location: ../../admin/moderation.php?error=Invalid report.");
    exit();
}

// Fetch current report and owner
$stmt = $pdo->prepare("
    SELECT r.*, u.name AS owner_name, u.email AS owner_email
    FROM reports r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: ../../admin/moderation.php?error=Report not found.");
    exit();
}

// Toggle is_urgent
$newValue = $report['is_urgent'] ? 0 : 1;
$pdo->prepare("UPDATE reports SET is_urgent = ? WHERE id = ?")
    ->execute([$newValue, $reportId]);

// Log the action
require_once __DIR__ . '/../../includes/admin_helpers.php';
$actionLabel = $newValue ? 'flag_urgent' : 'unflag_urgent';
logAdminAction($pdo, $adminId, $actionLabel, $reportId, 'report');

// Send email notification to report owner if flagging as urgent
if ($newValue === 1) {
    $templatePath = __DIR__ . '/../../templates/email/urgent_flag.html';
    $body = loadEmailTemplate($templatePath, [
        '{{owner_name}}'   => htmlspecialchars($report['owner_name']),
        '{{report_title}}' => htmlspecialchars($report['title']),
        '{{report_link}}'  => 'http://' . $_SERVER['HTTP_HOST'] . '/findit/report-detail.php?id=' . $reportId,
    ]);
    sendEmail($report['owner_email'], "FindIt — Your report has been flagged as urgent", $body);
    $message = "Report flagged as urgent. Owner has been notified.";
} else {
    $message = "Urgent flag removed from report.";
}

header("Location: ../../admin/moderation.php?success=" . urlencode($message));
exit();
?>