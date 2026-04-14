<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$reports = $stmt->fetchAll();
?>

<main class="py-5">
  <div class="container">
    <h2 class="section-title">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
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
                    <td><span class="badge <?= $report['type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>"><?= ucfirst($report['type']) ?></span></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($report['status']) ?></span></td>
                    <td>
                      <a href="report-edit.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                      <a href="report-delete.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this report?')">Delete</a>
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
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
