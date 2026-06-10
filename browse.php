<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$keyword  = sanitize($_GET['keyword']  ?? '');
$suburb   = sanitize($_GET['suburb']   ?? '');
$date     = $_GET['date']     ?? '';
$type     = sanitize($_GET['type']     ?? '');
$category = sanitize($_GET['category'] ?? '');

$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$conditions = ["status = 'active'"];
$params     = [];

if (!empty($keyword)) {
    $conditions[] = "(title LIKE ? OR description LIKE ? OR suburb LIKE ?)";
    $params[]     = "%$keyword%";
    $params[]     = "%$keyword%";
    $params[]     = "%$keyword%";
}
if (!empty($suburb)) {
    $conditions[] = "suburb LIKE ?";
    $params[]     = "%$suburb%";
}
if (!empty($date)) {
    $conditions[] = "report_date = ?";
    $params[]     = $date;
}
if (!empty($type) && in_array($type, ['lost', 'found'], true)) {
    $conditions[] = "report_type = ?";
    $params[]     = $type;
}
if (!empty($category) && in_array($category, ['Pets', 'Electronics', 'Documents', 'Missing Persons'], true)) {
    $conditions[] = "category = ?";
    $params[]     = $category;
}

$where = "WHERE " . implode(" AND ", $conditions);

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reports $where");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));

// Fetch reports
$fetchParams   = $params;
$fetchParams[] = $perPage;
$fetchParams[] = $offset;

$stmt = $pdo->prepare("
    SELECT * FROM reports
    $where
    ORDER BY is_urgent DESC, created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($fetchParams);
$reports = $stmt->fetchAll();
?>

<main class="py-5">
  <div class="container">

    <h2 class="section-title mb-1">Browse All Reports</h2>
    <div style="width:80px; height:3px; background:#0A7E8C; margin-bottom:24px;"></div>

    <!-- Search and Filter -->
    <form method="GET" action="/browse.php" class="card p-3 mb-4">
      <div class="row g-2 align-items-end">

        <div class="col-md-3">
          <label class="form-label small fw-bold text-muted">Keyword</label>
          <input type="text" name="keyword" class="form-control"
                 placeholder="Search reports..."
                 value="<?= htmlspecialchars($keyword) ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label small fw-bold text-muted">Category</label>
          <select name="category" class="form-select">
            <option value="">All Categories</option>
            <?php foreach (['Pets', 'Electronics', 'Documents', 'Missing Persons'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>>
                <?= $cat ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label small fw-bold text-muted">Type</label>
          <select name="type" class="form-select">
            <option value="">Lost &amp; Found</option>
            <option value="lost"  <?= $type === 'lost'  ? 'selected' : '' ?>>Lost Only</option>
            <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found Only</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label small fw-bold text-muted">Suburb</label>
          <input type="text" name="suburb" class="form-control"
                 placeholder="Suburb"
                 value="<?= htmlspecialchars($suburb) ?>">
        </div>

        <div class="col-md-1">
          <label class="form-label small fw-bold text-muted">Date</label>
          <input type="date" name="date" class="form-control"
                 value="<?= htmlspecialchars($date) ?>">
        </div>

        <div class="col-md-1">
          <button type="submit" class="btn btn-primary w-100 fw-bold"
                  style="background-color:#0A7E8C; border-color:#0A7E8C;">
            Filter
          </button>
        </div>

        <div class="col-md-1">
          <a href="/browse.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>

      </div>
    </form>

    <!-- Results count -->
    <p class="text-muted small mb-3">
      Showing <?= number_format($totalRecords) ?> report<?= $totalRecords !== 1 ? 's' : '' ?>
      <?= ($keyword || $suburb || $date || $type || $category) ? '— filtered results' : '' ?>
    </p>

    <!-- Report Cards -->
    <?php if (empty($reports)): ?>
      <div class="alert alert-info">
        No reports found<?= ($keyword || $suburb || $date || $type || $category) ? ' matching your search' : '' ?>.
        <?php if ($keyword || $suburb || $date || $type || $category): ?>
          <a href="/browse.php">Clear all filters</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($reports as $report): ?>
          <div class="col-md-4 mb-4">
            <div class="card h-100"
                 style="border-radius:10px;
                        <?= $report['is_urgent'] ? 'border:2px solid #c0392b;' : 'border:1px solid #dddddd;' ?>">

              <?php if (!empty($report['image_path'])): ?>
                <img src="<?= htmlspecialchars($report['image_path']) ?>"
                     class="card-img-top"
                     style="height:200px; object-fit:cover; border-radius:8px 8px 0 0;"
                     alt="Report image">
              <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light text-muted"
                     style="height:200px; border-radius:8px 8px 0 0; font-size:0.9rem;">
                  No Image
                </div>
              <?php endif; ?>

              <div class="card-body">
                <div class="d-flex gap-1 flex-wrap mb-2">
                  <?php if ($report['is_urgent']): ?>
                    <span class="badge" style="background-color:#c0392b; font-size:0.72rem;">
                      🚨 URGENT
                    </span>
                  <?php endif; ?>
                  <span class="badge <?= $report['report_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>"
                        style="font-size:0.72rem;">
                    <?= ucfirst($report['report_type']) ?>
                  </span>
                  <span class="badge bg-light text-dark border" style="font-size:0.72rem;">
                    <?= htmlspecialchars($report['category']) ?>
                  </span>
                </div>

                <h5 class="card-title fw-bold mb-1" style="color:#0D2B55; font-size:0.95rem;">
                  <?= htmlspecialchars($report['title']) ?>
                </h5>
                <p class="card-text text-muted small mb-2">
                  <?= htmlspecialchars(substr($report['description'], 0, 90)) ?>...
                </p>
                <p class="mb-1 small">
                  <strong style="color:#0D2B55;">📍</strong>
                  <?= htmlspecialchars($report['suburb']) ?>
                  &nbsp;|&nbsp;
                  <strong style="color:#0D2B55;">📅</strong>
                  <?= date('d M Y', strtotime($report['report_date'])) ?>
                </p>

                <div class="mt-3">
                  <a href="/report-detail.php?id=<?= $report['id'] ?>"
                     class="btn btn-sm w-100 fw-bold"
                     style="background-color:#EAF4F6; color:#0A7E8C; border:1px solid #0A7E8C;">
                    View Details →
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-2">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&suburb=<?= urlencode($suburb) ?>&type=<?= urlencode($type) ?>&category=<?= urlencode($category) ?>&date=<?= urlencode($date) ?>">← Previous</a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&suburb=<?= urlencode($suburb) ?>&type=<?= urlencode($type) ?>&category=<?= urlencode($category) ?>&date=<?= urlencode($date) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&suburb=<?= urlencode($suburb) ?>&type=<?= urlencode($type) ?>&category=<?= urlencode($category) ?>&date=<?= urlencode($date) ?>">Next →</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</main>

<?php require_once 'includes/footer.php'; ?>