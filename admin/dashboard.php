<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Admin access control
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=Access denied.");
    exit();
}

// Live stats from database
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_reports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$active_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'active'")->fetchColumn();
$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$rejected_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'rejected'")->fetchColumn();

$cats = ['Pets', 'Electronics', 'Documents', 'Missing Persons'];
$cat_counts = [];

foreach ($cats as $cat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE category = ?");
    $stmt->execute([$cat]);
    $cat_counts[$cat] = $stmt->fetchColumn();
}

// Latest 5 reports
$recent = $pdo->query("
    SELECT r.*, u.name AS user_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetchAll();
?>

<main class="py-5">
  <div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="section-title mb-1">Admin Dashboard</h2>
        <p class="text-muted mb-0">
          Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>. 
          Here is a live overview of FindIt platform activity.
        </p>
      </div>

      <a href="moderation.php" class="btn btn-primary fw-bold">
        Manage Reports
      </a>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
      <?php
      $stats = [
        ['label' => 'Total Users', 'value' => $total_users, 'icon' => '👥', 'color' => '#0D2B55'],
        ['label' => 'Total Reports', 'value' => $total_reports, 'icon' => '📋', 'color' => '#0A7E8C'],
        ['label' => 'Active Reports', 'value' => $active_reports, 'icon' => '✅', 'color' => '#28a745'],
        ['label' => 'Pending Moderation', 'value' => $pending_reports, 'icon' => '⏳', 'color' => '#F4A827'],
        ['label' => 'Rejected Reports', 'value' => $rejected_reports, 'icon' => '🚫', 'color' => '#dc3545'],
      ];

      foreach ($stats as $s): ?>
        <div class="col-6 col-md">
          <div class="card p-3 text-center h-100" style="border-top: 4px solid <?= $s['color'] ?>;">
            <div style="font-size:2rem;"><?= $s['icon'] ?></div>
            <h3 class="fw-bold mt-1" style="color:<?= $s['color'] ?>;">
              <?= htmlspecialchars((string)$s['value']) ?>
            </h3>
            <p class="text-muted small mb-0">
              <?= htmlspecialchars($s['label']) ?>
            </p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Reports by Category and Recent Activity -->
    <div class="row g-4 mb-5">

      <!-- Reports by Category -->
      <div class="col-md-6">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            Reports by Category
          </h5>

          <table class="table table-hover">
            <thead style="background-color:#EAF4F6;">
              <tr>
                <th>Category</th>
                <th>Total Reports</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($cat_counts as $cat => $count): ?>
                <tr>
                  <td><?= htmlspecialchars($cat) ?></td>
                  <td><?= htmlspecialchars((string)$count) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-md-6">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            Recent Activity (Latest 5 Reports)
          </h5>

          <?php if (empty($recent)): ?>

            <p class="text-muted">No reports submitted yet.</p>

          <?php else: ?>

            <ul class="list-group list-group-flush">
              <?php foreach ($recent as $r): ?>

                <?php
                  $badgeClass = $r['report_type'] === 'lost' ? 'bg-danger' : 'bg-success';

                  if ($r['status'] === 'pending') {
                      $statusClass = 'bg-warning text-dark';
                  } elseif ($r['status'] === 'active') {
                      $statusClass = 'bg-success';
                  } elseif ($r['status'] === 'rejected') {
                      $statusClass = 'bg-danger';
                  } else {
                      $statusClass = 'bg-secondary';
                  }
                ?>

                <li class="list-group-item px-0">
                  <span class="badge <?= $badgeClass ?> me-2">
                    <?= htmlspecialchars(ucfirst($r['report_type'])) ?>
                  </span>

                  <span class="badge <?= $statusClass ?> me-2">
                    <?= htmlspecialchars(ucfirst($r['status'])) ?>
                  </span>

                  <strong><?= htmlspecialchars($r['title']) ?></strong>

                  <span class="text-muted small ms-2">
                    by <?= htmlspecialchars($r['user_name']) ?>
                  </span>
                </li>

              <?php endforeach; ?>
            </ul>

          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Admin Quick Actions -->
    <div class="card p-4">
      <h5 class="fw-bold mb-3" style="color:#0D2B55;">
        Admin Quick Actions
      </h5>

      <div class="d-flex gap-2 flex-wrap">
        <a href="moderation.php" class="btn btn-primary">
          View / Approve / Delete Reports
        </a>

        <a href="reports.php?status=pending" class="btn btn-warning">
          View Pending Reports
        </a>

        <a href="../index.php" class="btn btn-outline-secondary">
          Back to Website
        </a>
      </div>
    </div>

  </div>
</main>

<?php require_once '../includes/footer.php'; ?>