<?php
require_once '../includes/header.php';

require_once '../includes/db.php';
require_once '../includes/admin-auth.php';

require_admin();

$status = trim($_GET['status'] ?? '');
$category = trim($_GET['category'] ?? '');
$suburb = trim($_GET['suburb'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT reports.*, users.name AS user_name, users.email AS user_email
        FROM reports
        JOIN users ON reports.user_id = users.id
        WHERE 1=1";

$params = [];

if (!empty($status)) {
    $sql .= " AND reports.status = ?";
    $params[] = $status;
}

if (!empty($category)) {
    $sql .= " AND reports.category = ?";
    $params[] = $category;
}

if (!empty($suburb)) {
    $sql .= " AND reports.suburb LIKE ?";
    $params[] = "%$suburb%";
}

if (!empty($keyword)) {
    $sql .= " AND (reports.title LIKE ? OR reports.description LIKE ? OR reports.suburb LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY reports.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports - FindIt</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<main class="py-5">
  <div class="container">
    <h2>Admin Report Management</h2>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form method="GET" class="row g-3 mb-4">
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <?php foreach (['pending','active','resolved','rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
              <?= ucfirst($s) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach (['Pets','Electronics','Documents','Missing Persons'] as $cat): ?>
            <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>>
              <?= $cat ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <input type="text" name="suburb" class="form-control" placeholder="Suburb" value="<?= htmlspecialchars($suburb) ?>">
      </div>

      <div class="col-md-3">
        <input type="text" name="keyword" class="form-control" placeholder="Keyword" value="<?= htmlspecialchars($keyword) ?>">
      </div>

      <div class="col-md-1">
        <button class="btn btn-primary w-100">Filter</button>
      </div>

      <div class="col-md-2">
        <a href="reports.php" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <?php if (empty($reports)): ?>
      <p>No reports found.</p>
    <?php else: ?>
      <table border="1" cellpadding="8" width="100%">
        <tr>
          <th>ID</th>
          <th>Image</th>
          <th>Title</th>
          <th>Type</th>
          <th>Category</th>
          <th>Suburb</th>
          <th>Status</th>
          <th>User</th>
          <th>Actions</th>
        </tr>

        <?php foreach ($reports as $report): ?>
          <tr>
            <td><?= htmlspecialchars($report['id']) ?></td>

            <td>
              <?php if (!empty($report['image_1'])): ?>
                <img src="../<?= htmlspecialchars($report['image_1']) ?>" width="80">
              <?php else: ?>
                No image
              <?php endif; ?>
            </td>

            <td><?= htmlspecialchars($report['title']) ?></td>
            <td><?= htmlspecialchars($report['report_type']) ?></td>
            <td><?= htmlspecialchars($report['category']) ?></td>
            <td><?= htmlspecialchars($report['suburb']) ?></td>
            <td><?= htmlspecialchars($report['status']) ?></td>
            <td>
              <?= htmlspecialchars($report['user_name']) ?><br>
              <small><?= htmlspecialchars($report['user_email']) ?></small>
            </td>

            <td>
              <?php if ($report['status'] !== 'active'): ?>
                <a href="../backend/admin/approve-report.php?id=<?= $report['id'] ?>">Approve</a> |
              <?php endif; ?>

              <?php if ($report['status'] !== 'rejected'): ?>
                <a href="../backend/admin/reject-report.php?id=<?= $report['id'] ?>">Reject</a> |
              <?php endif; ?>

              <a href="../backend/admin/delete-report.php?id=<?= $report['id'] ?>" onclick="return confirm('Delete this report permanently?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>

  </div>
</main>

</body>
</html>
<?php require_once '../includes/footer.php'; ?>