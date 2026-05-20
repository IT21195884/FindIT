<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch user's reports
$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$reports = $stmt->fetchAll();

// Fetch potential matches for user's reports
// A match involves either report_id_1 or report_id_2 belonging to this user
$stmt = $pdo->prepare("
    SELECT
        m.id AS match_id,
        m.match_score,
        m.status AS match_status,
        m.created_at AS matched_at,
        -- The matched report (the one NOT belonging to this user)
        CASE WHEN r1.user_id = :uid THEN r2.id   ELSE r1.id   END AS matched_report_id,
        CASE WHEN r1.user_id = :uid THEN r2.title ELSE r1.title END AS matched_title,
        CASE WHEN r1.user_id = :uid THEN r2.suburb ELSE r1.suburb END AS matched_suburb,
        CASE WHEN r1.user_id = :uid THEN r2.category ELSE r1.category END AS matched_category,
        CASE WHEN r1.user_id = :uid THEN r2.report_type ELSE r1.report_type END AS matched_type,
        -- The user's own report involved in this match
        CASE WHEN r1.user_id = :uid THEN r1.title ELSE r2.title END AS own_report_title
    FROM matches m
    JOIN reports r1 ON m.report_id_1 = r1.id
    JOIN reports r2 ON m.report_id_2 = r2.id
    WHERE (r1.user_id = :uid OR r2.user_id = :uid)
    AND m.status != 'dismissed'
    ORDER BY m.created_at DESC
");
$stmt->execute([':uid' => $user_id]);
$matches = $stmt->fetchAll();

// Generate CSRF token for dismiss actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Handle match dismiss action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss_match'])) {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $matchId = (int)$_POST['match_id'];
        // Verify this match belongs to the user before dismissing
        $checkStmt = $pdo->prepare("
            SELECT m.id FROM matches m
            JOIN reports r1 ON m.report_id_1 = r1.id
            JOIN reports r2 ON m.report_id_2 = r2.id
            WHERE m.id = ? AND (r1.user_id = ? OR r2.user_id = ?)
        ");
        $checkStmt->execute([$matchId, $user_id, $user_id]);
        if ($checkStmt->fetch()) {
            $pdo->prepare("UPDATE matches SET status = 'dismissed' WHERE id = ?")
                ->execute([$matchId]);
        }
        header("Location: dashboard.php");
        exit();
    }
}

// Mark viewed matches as 'viewed' when they appear on dashboard
if (!empty($matches)) {
    $matchIds = array_column($matches, 'match_id');
    $placeholders = implode(',', array_fill(0, count($matchIds), '?'));
    $pdo->prepare("UPDATE matches SET status = 'viewed' WHERE id IN ($placeholders) AND status = 'new'")
        ->execute($matchIds);
}
?>

<main class="py-5">
  <div class="container">
    <h2 class="section-title">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Quick Actions -->
      <div class="col-md-4">
        <div class="card p-4 h-100" style="border-top: 4px solid #0A7E8C;">
          <h5 class="fw-bold" style="color:#0D2B55;">Quick Actions</h5>
          <a href="report-create.php" class="btn btn-primary w-100 mb-2 mt-3">+ Post a New Report</a>
          <a href="browse.php" class="btn btn-outline-secondary w-100">Browse All Reports</a>
        </div>
      </div>

      <!-- My Reports -->
      <div class="col-md-8">
        <div class="card p-4">
          <h5 class="fw-bold mb-3" style="color:#0D2B55;">My Reports</h5>
          <?php if (empty($reports)): ?>
            <p class="text-muted">You haven't posted any reports yet. <a href="report-create.php">Post your first report</a>.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead style="background-color:#EAF4F6;">
                  <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reports as $report): ?>
                  <tr>
                    <td><?= htmlspecialchars($report['title']) ?></td>
                    <td><?= htmlspecialchars($report['category']) ?></td>
                    <td>
                      <span class="badge <?= $report['report_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>">
                        <?= ucfirst($report['report_type']) ?>
                      </span>
                    </td>
                    <td>
                      <?php
                      $statusColors = ['pending'=>'warning','active'=>'success','hidden'=>'secondary','resolved'=>'info'];
                      $sc = $statusColors[$report['status']] ?? 'secondary';
                      ?>
                      <span class="badge bg-<?= $sc ?> text-<?= $sc==='warning'?'dark':'white' ?>">
                        <?= ucfirst($report['status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="report-edit.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                      <a href="report-delete.php?id=<?= $report['id'] ?>"
                         class="btn btn-sm btn-outline-danger"
                         onclick="return confirm('Are you sure you want to delete this report?')">Delete</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ─── Potential Matches Section ─────────────────────────── -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card p-4" style="border-top: 4px solid #F4A827;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color:#0D2B55;">
              🔗 Potential Matches
              <?php $newCount = count(array_filter($matches, fn($m) => $m['match_status'] === 'new')); ?>
              <?php if ($newCount > 0): ?>
                <span class="badge bg-warning text-dark ms-2"><?= $newCount ?> New</span>
              <?php endif; ?>
            </h5>
          </div>

          <?php if (empty($matches)): ?>
            <p class="text-muted">
              No potential matches found yet. Matches are automatically detected when a lost and found report share similar details.
            </p>
          <?php else: ?>
            <p class="text-muted small mb-3">
              The system has detected the following potential matches for your reports. Review each one and contact the report owner if you think it's relevant.
            </p>
            <div class="row g-3">
              <?php foreach ($matches as $match): ?>
              <div class="col-md-6">
                <div class="card border <?= $match['match_status'] === 'new' ? 'border-warning' : '' ?> h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <span class="badge <?= $match['matched_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>">
                        <?= ucfirst($match['matched_type']) ?>
                      </span>
                      <?php if ($match['match_status'] === 'new'): ?>
                        <span class="badge bg-warning text-dark">New</span>
                      <?php endif; ?>
                    </div>
                    <h6 class="fw-bold"><?= htmlspecialchars($match['matched_title']) ?></h6>
                    <p class="text-muted small mb-1">
                      📍 <?= htmlspecialchars($match['matched_suburb']) ?> &nbsp;|&nbsp;
                      🏷️ <?= htmlspecialchars($match['matched_category']) ?>
                    </p>
                    <p class="text-muted small mb-2">
                      Matched with your report: <em><?= htmlspecialchars($match['own_report_title']) ?></em>
                    </p>
                    <p class="text-muted small mb-3">
                      Match score: <strong><?= round($match['match_score'] * 100) ?>%</strong> &nbsp;|&nbsp;
                      <?= date('d M Y', strtotime($match['matched_at'])) ?>
                    </p>
                    <div class="d-flex gap-2">
                      <a href="report-detail.php?id=<?= $match['matched_report_id'] ?>"
                         class="btn btn-sm btn-primary">View Report</a>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="match_id" value="<?= $match['match_id'] ?>">
                        <input type="hidden" name="dismiss_match" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- ─── End Potential Matches ──────────────────────────────── -->

  </div>
</main>

<?php require_once 'includes/footer.php'; ?>