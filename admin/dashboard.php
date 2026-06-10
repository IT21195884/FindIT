<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Admin access control
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=Access denied.");
    exit();
}

// Live stats from database
$total_users     = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_reports   = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$active_reports  = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'active'")->fetchColumn();
$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$hidden_reports  = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'hidden'")->fetchColumn();
$total_matches   = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();

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

// ── ANALYTICS DATA ──────────────────────────────────────────

// Monthly report submissions — last 6 months
$monthlyStmt = $pdo->query("
    SELECT
        DATE_FORMAT(created_at, '%b %Y') AS month_label,
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        COUNT(*) AS total
    FROM reports
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key ASC
");
$monthlyData  = $monthlyStmt->fetchAll();
$monthLabels  = array_column($monthlyData, 'month_label');
$monthCounts  = array_column($monthlyData, 'total');

// Reports by type
$lost_count  = $pdo->query("SELECT COUNT(*) FROM reports WHERE report_type = 'lost'")->fetchColumn();
$found_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE report_type = 'found'")->fetchColumn();

// Top 5 suburbs by report count
$suburbStmt = $pdo->query("
    SELECT suburb, COUNT(*) AS total
    FROM reports
    WHERE suburb IS NOT NULL AND suburb != ''
    GROUP BY suburb
    ORDER BY total DESC
    LIMIT 5
");
$suburbData = $suburbStmt->fetchAll();
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
        ['label' => 'Total Users',        'value' => $total_users,     'icon' => '👥', 'color' => '#0D2B55'],
        ['label' => 'Total Reports',      'value' => $total_reports,   'icon' => '📋', 'color' => '#0A7E8C'],
        ['label' => 'Active Reports',     'value' => $active_reports,  'icon' => '✅', 'color' => '#28a745'],
        ['label' => 'Pending Moderation', 'value' => $pending_reports, 'icon' => '⏳', 'color' => '#F4A827'],
        ['label' => 'Hidden Reports',     'value' => $hidden_reports,  'icon' => '🙈', 'color' => '#6c757d'],
        ['label' => 'Matches Detected',   'value' => $total_matches,   'icon' => '🔗', 'color' => '#9673a6'],
      ];
      foreach ($stats as $s): ?>
        <div class="col-6 col-md-2">
          <div class="card p-3 text-center h-100"
               style="border-top: 4px solid <?= $s['color'] ?>;">
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

    <!-- Analytics Section -->
    <div class="row g-4 mb-5">

      <!-- Monthly Submissions Chart -->
      <div class="col-md-8">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            📈 Monthly Report Submissions (Last 6 Months)
          </h5>
          <?php if (empty($monthlyData)): ?>
            <p class="text-muted">No report data available yet.</p>
          <?php else: ?>
            <canvas id="monthlyChart" style="max-height:260px;"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <!-- Report Type Ratio -->
      <div class="col-md-4">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            🥧 Lost vs Found Ratio
          </h5>
          <?php if ($total_reports == 0): ?>
            <p class="text-muted">No reports yet.</p>
          <?php else: ?>
            <canvas id="typeChart" style="max-height:220px;"></canvas>
            <div class="d-flex justify-content-center gap-4 mt-3">
              <div class="text-center">
                <div class="fw-bold fs-4" style="color:#dc3545;"><?= $lost_count ?></div>
                <div class="text-muted small">Lost</div>
              </div>
              <div class="text-center">
                <div class="fw-bold fs-4" style="color:#28a745;"><?= $found_count ?></div>
                <div class="text-muted small">Found</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Reports by Category + Top Suburbs + Recent Activity -->
    <div class="row g-4 mb-5">

      <!-- Reports by Category -->
      <div class="col-md-4">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            🏷️ Reports by Category
          </h5>
          <table class="table table-hover">
            <thead style="background-color:#EAF4F6;">
              <tr>
                <th>Category</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cat_counts as $cat => $count): ?>
                <tr>
                  <td><?= htmlspecialchars($cat) ?></td>
                  <td class="text-end fw-bold"><?= $count ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Suburbs -->
      <div class="col-md-4">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            📍 Top 5 Suburbs by Reports
          </h5>
          <?php if (empty($suburbData)): ?>
            <p class="text-muted">No suburb data yet.</p>
          <?php else: ?>
            <table class="table table-hover">
              <thead style="background-color:#EAF4F6;">
                <tr>
                  <th>Suburb</th>
                  <th class="text-end">Reports</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($suburbData as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['suburb']) ?></td>
                    <td class="text-end fw-bold"><?= $row['total'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-md-4">
        <div class="card p-4 h-100">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">
            🕐 Recent Activity (Latest 5 Reports)
          </h5>
          <?php if (empty($recent)): ?>
            <p class="text-muted">No reports submitted yet.</p>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($recent as $r):
                $badgeClass  = $r['report_type'] === 'lost' ? 'bg-danger' : 'bg-success';
                $statusClass = match($r['status']) {
                    'pending'  => 'bg-warning text-dark',
                    'active'   => 'bg-success',
                    'hidden'   => 'bg-secondary',
                    default    => 'bg-secondary'
                };
              ?>
                <li class="list-group-item px-0">
                  <span class="badge <?= $badgeClass ?> me-1">
                    <?= ucfirst($r['report_type']) ?>
                  </span>
                  <span class="badge <?= $statusClass ?> me-1">
                    <?= ucfirst($r['status']) ?>
                  </span>
                  <strong><?= htmlspecialchars($r['title']) ?></strong>
                  <span class="text-muted small ms-1">
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
        <a href="users.php" class="btn btn-outline-primary">
          Manage Users
        </a>
        <a href="audit-log.php" class="btn btn-outline-primary">
          📋 View Audit Log
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($monthlyData)): ?>
// Monthly submissions bar chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Reports Submitted',
            data: <?= json_encode(array_map('intval', $monthCounts)) ?>,
            backgroundColor: '#0A7E8C',
            borderColor: '#0D2B55',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
<?php endif; ?>

<?php if ($total_reports > 0): ?>
// Lost vs Found doughnut chart
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: ['Lost', 'Found'],
        datasets: [{
            data: [<?= (int)$lost_count ?>, <?= (int)$found_count ?>],
            backgroundColor: ['#dc3545', '#28a745'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 12 } }
            }
        },
        cutout: '65%'
    }
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>