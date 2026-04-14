<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Admin access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php?error=Access denied.");
    exit();
}

// Live stats from database
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_reports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$active_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'active'")->fetchColumn();
$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

$cats = ['Pets','Electronics','Documents','Missing Persons'];
$cat_counts = [];
foreach ($cats as $cat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE category = ?");
    $stmt->execute([$cat]);
    $cat_counts[$cat] = $stmt->fetchColumn();
}

$recent = $pdo->query("SELECT r.*, u.name as user_name FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5")->fetchAll();
?>

<main class="py-5">
  <div class="container">
    <h2 class="section-title">Admin Dashboard</h2>
    <p class="text-muted mb-4">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>. Here is a live overview of FindIt platform activity.</p>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
      <?php
      $stats = [
        ['label' => 'Total Users', 'value' => $total_users, 'icon' => '👥', 'color' => '#0D2B55'],
        ['label' => 'Total Reports', 'value' => $total_reports, 'icon' => '📋', 'color' => '#0A7E8C'],
        ['label' => 'Active Reports', 'value' => $active_reports, 'icon' => '✅', 'color' => '#28a745'],
        ['label' => 'Pending Moderation', 'value' => $pending_reports, 'icon' => '⏳', 'color' => '#F4A827'],
      ];
      foreach ($stats as $s): ?>
        <div class="col-6 col-md-3">
          <div class="card p-3 text-center" style="border-top: 4px solid <?= $s['color'] ?>;">
            <div style="font-size:2rem;"><?= $s['icon'] ?></div>
            <h3 class="fw-bold mt-1" style="color:<?= $s['color'] ?>;"><?= $s['value'] ?></h3>
            <p class="text-muted small mb-0"><?= $s['label'] ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Reports by Category -->
    <div class="row g-4 mb-5">
      <div class="col-md-6">
        <div class="card p-4">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">Reports by Category</h5>
          <table class="table table-hover">
            <thead style="background-color:#EAF4F6;">
              <tr><th>Category</th><th>Total Reports</th></tr>
            </thead>
            <tbody>
              <?php foreach ($cat_counts as $cat => $count): ?>
              <tr><td><?= $cat ?></td><td><?= $count ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-md-6">
        <div class="card p-4">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">Recent Activity (Latest 5 Reports)</h5>
          <?php if (empty($recent)): ?>
            <p class="text-muted">No reports submitted yet.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($recent as $r): ?>
                <li class="list-group-item px-0">
                  <span class="badge <?= $r['type'] === 'lost' ? 'bg-danger' : 'bg-success' ?> me-2"><?= ucfirst($r['type']) ?></span>
                  <strong><?= htmlspecialchars($r['title']) ?></strong>
                  <span class="text-muted small ms-2">by <?= htmlspecialchars($r['user_name']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once '../includes/footer.php'; ?>
