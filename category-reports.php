<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$suburb = trim($_GET['suburb'] ?? '');
$date = $_GET['date'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT * FROM reports WHERE category = ? AND status = 'active'";
$params = [$category];

if (!empty($suburb)) {
    $sql .= " AND suburb LIKE ?";
    $params[] = "%$suburb%";
}

if (!empty($date)) {
    $sql .= " AND report_date = ?";
    $params[] = $date;
}

if (!empty($keyword)) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR suburb LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<main class="py-5">
  <div class="container">

    <h2 class="section-title">
      <?= htmlspecialchars($pageTitle) ?>
    </h2>

    <!-- Filter/Search Form -->
    <form method="GET" class="row g-3 mb-4">

      <div class="col-md-3">
        <input 
          type="text" 
          name="keyword" 
          class="form-control" 
          placeholder="Search keyword"
          value="<?= htmlspecialchars($keyword) ?>"
        >
      </div>

      <div class="col-md-3">
        <input 
          type="text" 
          name="suburb" 
          class="form-control" 
          placeholder="Suburb"
          value="<?= htmlspecialchars($suburb) ?>"
        >
      </div>

      <div class="col-md-2">
        <input 
          type="date" 
          name="date" 
          class="form-control"
          value="<?= htmlspecialchars($date) ?>"
        >
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          Filter
        </button>
      </div>

      <div class="col-md-2">
        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary w-100">
          Reset
        </a>
      </div>

    </form>

    <!-- Report Cards -->
    <?php if (empty($reports)): ?>

      <div class="alert alert-info">
        No reports found.
      </div>

    <?php else: ?>

      <div class="row">

        <?php foreach ($reports as $report): ?>

          <div class="col-md-4 mb-4">
            <div class="card h-100">

              <?php if (!empty($report['image_path'])): ?>
                <img 
                  src="<?= htmlspecialchars($report['image_path']) ?>" 
                  class="card-img-top" 
                  style="height:220px; object-fit:cover;"
                  alt="Report image"
                >
              <?php else: ?>
                <div 
                  class="d-flex align-items-center justify-content-center bg-light text-muted" 
                  style="height:220px;"
                >
                  No Image
                </div>
              <?php endif; ?>

              <div class="card-body">

                <span class="badge bg-secondary mb-2">
                  <?= htmlspecialchars(ucfirst($report['report_type'])) ?>
                </span>

                <h5 class="card-title">
                  <?= htmlspecialchars($report['title']) ?>
                </h5>

                <p class="card-text">
                  <?= htmlspecialchars(substr($report['description'], 0, 120)) ?>...
                </p>

                <p class="mb-1">
                  <strong>Suburb:</strong> <?= htmlspecialchars($report['suburb']) ?>
                </p>

                <p class="mb-1">
                  <strong>Date:</strong> <?= htmlspecialchars($report['report_date']) ?>
                  <div class="mt-3">
                  <a href="/report-detail.php?id=<?= $report['id'] ?>" class="btn btn-sm w-100" style="background-color:#EAF4F6; color:#0A7E8C; border:1px solid #0A7E8C;">
                    View Details →
                  </a>
                  </div>
                </p>

              </div>
            </div>
          </div>

        <?php endforeach; ?>

      </div>

    <?php endif; ?>

  </div>
</main>

<?php require_once 'includes/footer.php'; ?>