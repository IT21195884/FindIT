<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$suburb  = sanitize($_GET['suburb']  ?? '');
$date    = $_GET['date']    ?? '';
$keyword = sanitize($_GET['keyword'] ?? '');
$type    = sanitize($_GET['type']    ?? '');

$sql    = "SELECT * FROM reports WHERE category = ? AND status = 'active'";
$params = [$category];

if (!empty($suburb)) {
    $sql     .= " AND suburb LIKE ?";
    $params[] = "%$suburb%";
}
if (!empty($date)) {
    $sql     .= " AND report_date = ?";
    $params[] = $date;
}
if (!empty($keyword)) {
    $sql     .= " AND (title LIKE ? OR description LIKE ? OR suburb LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}
if (!empty($type) && in_array($type, ['lost', 'found'], true)) {
    $sql     .= " AND report_type = ?";
    $params[] = $type;
}

// Urgent reports pinned to top
$sql .= " ORDER BY is_urgent DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<main class="py-5">
  <div class="container">

    <h2 class="section-title">
      <?= htmlspecialchars($pageTitle) ?>
    </h2>
    <div style="width:80px; height:3px; background:#0A7E8C; margin-bottom:24px;"></div>

    <!-- Filter / Search Form -->
    <form method="GET" class="row g-2 mb-4 align-items-end">

      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Keyword</label>
        <input type="text" name="keyword" class="form-control"
               placeholder="Search keyword"
               value="<?= htmlspecialchars($keyword) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Suburb</label>
        <input type="text" name="suburb" class="form-control"
               placeholder="Suburb"
               value="<?= htmlspecialchars($suburb) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Date</label>
        <input type="date" name="date" class="form-control"
               value="<?= htmlspecialchars($date) ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Type</label>
        <select name="type" class="form-select">
          <option value="">All Types</option>
          <option value="lost"  <?= $type === 'lost'  ? 'selected' : '' ?>>Lost</option>
          <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found</option>
        </select>
      </div>

      <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100 fw-bold"
                style="background-color:#0A7E8C; border-color:#0A7E8C;">
          Filter
        </button>
      </div>

      <div class="col-md-1">
        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
           class="btn btn-outline-secondary w-100">
          Reset
        </a>
      </div>

    </form>

    <!-- Results count -->
    <p class="text-muted small mb-3">
      <?= count($reports) ?> report<?= count($reports) !== 1 ? 's' : '' ?> found
      <?= ($keyword || $suburb || $date || $type) ? '(filtered)' : '' ?>
    </p>

    <!-- Report Cards -->
    <?php if (empty($reports)): ?>
      <div class="alert alert-info">
        No reports found<?= ($keyword || $suburb || $date || $type) ? ' matching your search' : '' ?>.
        <?php if ($keyword || $suburb || $date || $type): ?>
          <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Clear filters</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($reports as $report): ?>
          <div class="col-md-4 mb-4">
            <div class="card h-100"
                 style="border-radius:10px;
                        <?= $report['is_urgent'] ? 'border:2px solid #c0392b;' : 'border:1px solid #dddddd;' ?>">

              <!-- Image -->
              <?php if (!empty($report['image_path'])): ?>
                <img src="<?= htmlspecialchars($report['image_path']) ?>"
                     class="card-img-top"
                     style="height:220px; object-fit:cover; border-radius:8px 8px 0 0;"
                     alt="Report image">
              <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light text-muted"
                     style="height:220px; border-radius:8px 8px 0 0; font-size:0.9rem;">
                  No Image
                </div>
              <?php endif; ?>

              <div class="card-body">

                <!-- Badges -->
                <div class="d-flex gap-1 flex-wrap mb-2">
                  <?php if ($report['is_urgent']): ?>
                    <span class="badge"
                          style="background-color:#c0392b; font-size:0.75rem;">
                      🚨 URGENT
                    </span>
                  <?php endif; ?>
                  <span class="badge <?= $report['report_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>"
                        style="font-size:0.75rem;">
                    <?= ucfirst(htmlspecialchars($report['report_type'])) ?>
                  </span>
                </div>

                <!-- Title -->
                <h5 class="card-title fw-bold" style="color:#0D2B55; font-size:1rem;">
                  <?= htmlspecialchars($report['title']) ?>
                </h5>

                <!-- Description preview -->
                <p class="card-text text-muted small mb-2">
                  <?= htmlspecialchars(substr($report['description'], 0, 100)) ?>...
                </p>

                <!-- Suburb and date -->
                <p class="mb-1 small">
                  <strong style="color:#0D2B55;">📍 Suburb:</strong>
                  <?= htmlspecialchars($report['suburb']) ?>
                </p>
                <p class="mb-3 small">
                  <strong style="color:#0D2B55;">📅 Date:</strong>
                  <?= date('d M Y', strtotime($report['report_date'])) ?>
                </p>

                <!-- View Details button -->
                <a href="/report-detail.php?id=<?= $report['id'] ?>"
                   class="btn btn-sm w-100 fw-bold"
                   style="background-color:#EAF4F6; color:#0A7E8C; border:1px solid #0A7E8C;">
                  View Details →
                </a>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once 'includes/footer.php'; ?>