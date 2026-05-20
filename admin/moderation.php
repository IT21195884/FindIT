<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php?error=Access denied.");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Fetch all pending reports first, then active, then hidden
$reports = $pdo->query("
    SELECT r.*, u.name AS owner_name, u.email AS owner_email
    FROM reports r
    JOIN users u ON r.user_id = u.id
    ORDER BY
        CASE r.status WHEN 'pending' THEN 1 WHEN 'active' THEN 2 ELSE 3 END,
        r.created_at DESC
")->fetchAll();
?>

<main class="py-5">
  <div class="container">
    <h2 class="section-title">Report Moderation</h2>
    <p class="text-muted mb-4">Review and action all submitted reports. Pending reports must be approved before they appear publicly.</p>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <ul class="nav nav-tabs mb-4">
      <li class="nav-item">
        <a class="nav-link <?= !isset($_GET['filter']) ? 'active' : '' ?>" href="moderation.php">All Reports</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= ($_GET['filter'] ?? '') === 'pending' ? 'active' : '' ?>" href="moderation.php?filter=pending">
          Pending
          <span class="badge bg-warning text-dark ms-1">
            <?= $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn() ?>
          </span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= ($_GET['filter'] ?? '') === 'active' ? 'active' : '' ?>" href="moderation.php?filter=active">Active</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= ($_GET['filter'] ?? '') === 'hidden' ? 'active' : '' ?>" href="moderation.php?filter=hidden">Hidden</a>
      </li>
    </ul>

    <?php
    // Apply filter if set
    $filter = sanitize($_GET['filter'] ?? '');
    if ($filter && in_array($filter, ['pending','active','hidden'])) {
        $reports = array_filter($reports, fn($r) => $r['status'] === $filter);
    }
    ?>

    <?php if (empty($reports)): ?>
      <div class="alert alert-info">No reports found.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead style="background-color:#EAF4F6;">
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Type</th>
              <th>Category</th>
              <th>Suburb</th>
              <th>Submitted By</th>
              <th>Date</th>
              <th>Status</th>
              <th>Urgent</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $r): ?>
            <tr class="<?= $r['status'] === 'pending' ? 'table-warning' : '' ?>">
              <td class="text-muted small">#<?= $r['id'] ?></td>
              <td>
                <a href="../report-detail.php?id=<?= $r['id'] ?>" target="_blank" class="text-decoration-none fw-bold">
                  <?= htmlspecialchars($r['title']) ?>
                </a>
                <?php if ($r['is_urgent']): ?>
                  <span class="badge bg-danger ms-1">URGENT</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $r['report_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>">
                  <?= ucfirst($r['report_type']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($r['category']) ?></td>
              <td><?= htmlspecialchars($r['suburb']) ?></td>
              <td>
                <?= htmlspecialchars($r['owner_name']) ?>
                <div class="text-muted small"><?= htmlspecialchars($r['owner_email']) ?></div>
              </td>
              <td class="small"><?= date('d M Y', strtotime($r['report_date'])) ?></td>
              <td>
                <?php
                $statusColors = ['pending'=>'warning','active'=>'success','hidden'=>'secondary','resolved'=>'info'];
                $color = $statusColors[$r['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $color ?> text-<?= $color==='warning'?'dark':'white' ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>
              <td>
                <!-- Urgent toggle -->
                <form method="POST" action="../backend/admin/urgent.php" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-sm <?= $r['is_urgent'] ? 'btn-danger' : 'btn-outline-danger' ?>"
                    title="<?= $r['is_urgent'] ? 'Remove urgent flag' : 'Flag as urgent' ?>">
                    🚨
                  </button>
                </form>
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <?php if ($r['status'] === 'pending' || $r['status'] === 'hidden'): ?>
                  <form method="POST" action="../backend/admin/moderate.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                  </form>
                  <?php endif; ?>

                  <?php if ($r['status'] === 'active'): ?>
                  <form method="POST" action="../backend/admin/moderate.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="hide">
                    <button type="submit" class="btn btn-sm btn-warning">Hide</button>
                  </form>
                  <?php endif; ?>

                  <form method="POST" action="../backend/admin/moderate.php" class="d-inline"
                    onsubmit="return confirm('Permanently delete this report? This cannot be undone.')">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="mt-3">
      <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Admin Dashboard</a>
    </div>
  </div>
</main>

<?php require_once '../includes/footer.php'; ?>