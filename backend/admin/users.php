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
    header("Location: ../../admin/users.php?error=Invalid request.");
    exit();
}

$action     = sanitize($_POST['action'] ?? '');
$targetUserId = (int)($_POST['user_id'] ?? 0);
$adminId    = (int)$_SESSION['user_id'];

if (!$targetUserId || !in_array($action, ['deactivate', 'ban', 'activate'], true)) {
    header("Location: ../../admin/users.php?error=Invalid action.");
    exit();
}

// Prevent admin acting on themselves
if ($targetUserId === $adminId) {
    header("Location: ../../admin/users.php?error=You cannot modify your own account.");
    exit();
}

// Fetch target user
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    header("Location: ../../admin/users.php?error=User not found.");
    exit();
}

// Prevent modifying another admin
if ($targetUser['role'] === 'admin') {
    header("Location: ../../admin/users.php?error=Cannot modify another administrator account.");
    exit();
}

require_once __DIR__ . '/../../includes/admin_helpers.php';

$templatePath = __DIR__ . '/../../templates/email/';

switch ($action) {
    case 'deactivate':
        $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")
            ->execute([$targetUserId]);
        logAdminAction($pdo, $adminId, 'deactivate_user', $targetUserId, 'user');

        $body = loadEmailTemplate($templatePath . 'account_action.html', [
            '{{user_name}}'  => htmlspecialchars($targetUser['name']),
            '{{action_msg}}' => 'Your FindIt account has been temporarily deactivated by a WA Police administrator. Please contact us if you believe this is an error.',
        ]);
        sendEmail($targetUser['email'], "FindIt — Your account has been deactivated", $body);
        $message = "User account deactivated successfully.";
        break;

    case 'ban':
        $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?")
            ->execute([$targetUserId]);
        logAdminAction($pdo, $adminId, 'ban_user', $targetUserId, 'user');

        $body = loadEmailTemplate($templatePath . 'account_action.html', [
            '{{user_name}}'  => htmlspecialchars($targetUser['name']),
            '{{action_msg}}' => 'Your FindIt account has been permanently banned due to a violation of our community guidelines.',
        ]);
        sendEmail($targetUser['email'], "FindIt — Your account has been banned", $body);
        $message = "User account banned successfully.";
        break;

    case 'activate':
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")
            ->execute([$targetUserId]);
        logAdminAction($pdo, $adminId, 'reactivate_user', $targetUserId, 'user');
        $message = "User account reactivated successfully.";
        break;
}

header("Location: ../../admin/users.php?success=" . urlencode($message));
exit();
?>