<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Admin access control — before header to allow redirects
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=Access denied.");
    exit();
}

require_once '../includes/header.php';

// Pagination
$perPage    = 20;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;

// Filter by action type
$filterAction = sanitize($_GET['action'] ?? '');
$allowedActions = [
    'approve_report', 'hide_report', 'delete_report',
    'flag_urgent', 'unflag_urgent',
    'deactivate_user', 'ban_user', 'reactivate_user'
];

// Build query
$conditions = [];
$params     = [];

if ($filterAction && in_array($filterAction, $allowedActions, true)) {
    $conditions[] = "al.action = ?";
    $params[]     = $filterAction;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM admin_log al $where");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));

// Fetch log entries with admin name
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare("
    SELECT al.*, u.name AS admin_name
    FROM admin_log al
    JOIN users u ON al.admin_id = u.id
    $where
    ORDER BY al.timestamp DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Action labels and colours for display
$actionConfig = [
    'approve_report'   => ['label' => 'Approved Report',      'color' => 'success'],
    'hide_report'      => ['label' => 'Hidden Report',         'color' => 'warning'],
    'delete_report'    => ['label' => 'Deleted Report',        'color' => 'danger'],
    'flag_urgent'      => ['label' => 'Flagged Urgent',        'color' => 'danger'],
    'unflag_urgent'    => ['label' => 'Removed Urgent Flag',   'color' => 'secondary'],
    'deactivate_user'  => ['label' => 'Deactivated User',      'color' => 'warning'],
    'ban_user'         => ['label' => 'Banned User',           'color' => 'danger'],
    'reactivate_user'  => ['label' => 'Reactivated User',      'color' => 'success'],
];
?>

<main class="py-5">
  <div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="section-title mb-1">Admin Audit Log</h2>
        <p class="text-muted mb-0">
          A full record of all moderation and account management actions taken by administrators.
        </p>
      </div>
      <a href="dashboard.php" class="btn btn-outline-secondary">
        ← Back to Admin Dashboard
      </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Summary stats -->
    <div class="row g-3 mb-4">
      <?php
      $actionStats = [
          'approve_report'  => ['icon' => '✅', 'color' => '#28a745'],
          'hide_report'     => ['icon' => '🙈', 'color' => '#F4A827'],
          'delete_report'   => ['icon' => '🗑️', 'color' => '#dc3545'],
          'flag_urgent'     => ['icon' => '🚨', 'color' => '#c0392b'],
          'ban_user'        => ['icon' => '🚫', 'color' => '#dc3545'],
          'deactivate_user' => ['icon' => '⏸️', 'color' => '#6c757d'],
      ];
      foreach ($actionStats as $action => $config):
          $count = $pdo->prepare("SELECT COUNT(*) FROM admin_log WHERE action = ?");
          $count->execute([$action]);
          $total = $count->fetchColumn();
          $label = $actionConfig[$action]['label'] ?? ucfirst($action);
      ?>
        <div class="col-6 col-md-2">
          <div class="card p-3 text-center h-100"
               style="border-top: 4px solid <?= $config['color'] ?>;">
            <div style="font-size:1.6rem;"><?= $config['icon'] ?></div>
            <h4 class="fw-bold mt-1 mb-0" style="color:<?= $config['color'] ?>;">
              <?= $total ?>
            </h4>
            <p class="text-muted small mb-0"><?= $label ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Filter bar -->
    <div class="card p-3 mb-4">
      <form method="GET" action="audit-log.php" class="d-flex gap-2 align-items-center flex-wrap">
        <label class="fw-bold text-muted small me-1">Filter by Action:</label>
        <select name="action" class="form-select form-select-sm" style="width:220px;">
          <option value="">All Actions</option>
          <?php foreach ($allowedActions as $a): ?>
            <option value="<?= $a ?>" <?= $filterAction === $a ? 'selected' : '' ?>>
              <?= $actionConfig[$a]['label'] ?? ucfirst($a) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <?php if ($filterAction): ?>
          <a href="audit-log.php" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
        <span class="text-muted small ms-auto">
          Showing <?= number_format($totalRecords) ?> record<?= $totalRecords !== 1 ? 's' : '' ?>
        </span>
      </form>
    </div>

    <!-- Log table -->
    <?php if (empty($logs)): ?>
      <div class="alert alert-info">No audit log entries found.</div>
    <?php else: ?>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead style="background-color:#EAF4F6;">
              <tr>
                <th class="ps-3">ID</th>
                <th>Timestamp</th>
                <th>Administrator</th>
                <th>Action</th>
                <th>Affected Type</th>
                <th>Record ID</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log):
                $config = $actionConfig[$log['action']] ?? ['label' => $log['action'], 'color' => 'secondary'];
              ?>
              <tr>
                <td class="ps-3 text-muted small">#<?= $log['id'] ?></td>
                <td class="small">
                  <?= date('d M Y H:i:s', strtotime($log['timestamp'])) ?>
                </td>
                <td class="fw-bold" style="color:#0D2B55;">
                  <?= htmlspecialchars($log['admin_name']) ?>
                </td>
                <td>
                  <span class="badge bg-<?= $config['color'] ?> px-2 py-1">
                    <?= htmlspecialchars($config['label']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <?= ucfirst(htmlspecialchars($log['affected_type'])) ?>
                  </span>
                </td>
                <td>
                  <?php if ($log['affected_type'] === 'report'): ?>
                    <a href="../report-detail.php?id=<?= $log['affected_record_id'] ?>"
                       class="text-decoration-none small" target="_blank"
                       style="color:#0A7E8C;">
                      Report #<?= $log['affected_record_id'] ?>
                    </a>
                  <?php else: ?>
                    <span class="small text-muted">
                      User #<?= $log['affected_record_id'] ?>
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link"
                 href="?page=<?= $page - 1 ?><?= $filterAction ? '&action=' . urlencode($filterAction) : '' ?>">
                ← Previous
              </a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="?page=<?= $i ?><?= $filterAction ? '&action=' . urlencode($filterAction) : '' ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link"
                 href="?page=<?= $page + 1 ?><?= $filterAction ? '&action=' . urlencode($filterAction) : '' ?>">
                Next →
              </a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</main>

<?php require_once '../includes/footer.php'; ?>