<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php?error=Access denied.");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Fetch all non-admin users with their report count
$users = $pdo->query("
    SELECT u.*, COUNT(r.id) AS report_count
    FROM users u
    LEFT JOIN reports r ON r.user_id = u.id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<main class="py-5">
  <div class="container">
    <h2 class="section-title">User Account Management</h2>
    <p class="text-muted mb-4">View and manage all registered community member accounts.</p>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
      <div class="alert alert-info">No registered users found.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead style="background-color:#EAF4F6;">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Suburb</th>
              <th>Reports</th>
              <th>Registered</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td class="text-muted small">#<?= $u['id'] ?></td>
              <td class="fw-bold"><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['suburb']) ?></td>
              <td><?= $u['report_count'] ?></td>
              <td class="small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <?php
                $statusConfig = [
                    'active'   => ['color' => 'success', 'label' => 'Active'],
                    'inactive' => ['color' => 'warning', 'label' => 'Inactive'],
                    'banned'   => ['color' => 'danger',  'label' => 'Banned'],
                ];
                $sc = $statusConfig[$u['status']] ?? ['color' => 'secondary', 'label' => ucfirst($u['status'])];
                ?>
                <span class="badge bg-<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <?php if ($u['status'] !== 'active'): ?>
                  <form method="POST" action="../backend/admin/users.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="btn btn-sm btn-success">Activate</button>
                  </form>
                  <?php endif; ?>

                  <?php if ($u['status'] === 'active'): ?>
                  <form method="POST" action="../backend/admin/users.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="btn btn-sm btn-warning"
                      onclick="return confirm('Deactivate <?= htmlspecialchars(addslashes($u['name'])) ?>? They will not be able to log in.')">
                      Deactivate
                    </button>
                  </form>
                  <?php endif; ?>

                  <?php if ($u['status'] !== 'banned'): ?>
                  <form method="POST" action="../backend/admin/users.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="action" value="ban">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Permanently ban <?= htmlspecialchars(addslashes($u['name'])) ?>? This will prevent them from logging in.')">
                      Ban
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="mt-3">
      <a href="dashboard.php" class="btn btn-outline-secondary">← Back to Admin Dashboard</a>
    </div>
  </div>
</main>

<?php require_once '../includes/footer.php'; ?>