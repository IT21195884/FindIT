<?php
// ============================================================
// FindIt — Admin Helper Functions
// Shared across all admin backend files
// ============================================================

function logAdminAction(PDO $pdo, int $adminId, string $action, int $recordId, string $type = 'report'): void
{
    $stmt = $pdo->prepare("
        INSERT INTO admin_log (admin_id, action, affected_record_id, affected_type, timestamp)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$adminId, $action, $recordId, $type]);
}