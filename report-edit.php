<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: dashboard.php?error=Report not found.");
    exit();
}
?>

<main class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7">
        <div class="card p-4">
          <h2 class="section-title">Edit Report</h2>
          <form action="backend/report/edit.php" method="POST">
            <input type="hidden" name="id" value="<?= $report['id'] ?>">
            <div class="mb-3">
              <label class="form-label fw-bold">Report Type</label>
              <select name="type" class="form-select" required>
                <option value="lost" <?= $report['type'] === 'lost' ? 'selected' : '' ?>>Lost</option>
                <option value="found" <?= $report['type'] === 'found' ? 'selected' : '' ?>>Found</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Category</label>
              <select name="category" class="form-select" required>
                <?php foreach (['Pets','Electronics','Documents','Missing Persons'] as $cat): ?>
                  <option value="<?= $cat ?>" <?= $report['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Title</label>
              <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($report['title']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($report['description']) ?></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Suburb</label>
              <input type="text" name="suburb" class="form-control" value="<?= htmlspecialchars($report['suburb']) ?>" required>
            </div>
            <div class="mb-4">
              <label class="form-label fw-bold">Date Lost/Found</label>
              <input type="date" name="date_occurred" class="form-control" value="<?= $report['date_occurred'] ?>" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Save Changes</button>
            <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
