<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect before header
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}



$userId = (int)$_SESSION['user_id'];

// Handle form submission
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $name   = sanitize($_POST['name']   ?? '');
        $suburb = sanitize($_POST['suburb'] ?? '');

        if (empty($name) || empty($suburb)) {
            $error = "Name and suburb cannot be empty.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, suburb = ? WHERE id = ?");
            $stmt->execute([$name, $suburb, $userId]);
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully.";
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch user's reports
$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$reports = $stmt->fetchAll();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>

<main class="py-5">
  <div class="container">

    <h2 class="section-title mb-1">My Profile</h2>
    <div style="width:80px; height:3px; background:#0A7E8C; margin-bottom:24px;"></div>

    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Profile edit form -->
      <div class="col-md-4">
        <div class="card p-4 h-100" style="border-top: 4px solid #0A7E8C;">
          <h5 class="fw-bold mb-4" style="color:#0D2B55;">Edit Profile</h5>

          <form method="POST" action="/profile.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="mb-3">
              <label class="form-label fw-bold" style="color:#0D2B55;">Full Name</label>
              <input type="text" name="name" class="form-control"
                     value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold" style="color:#0D2B55;">Email Address</label>
              <input type="email" class="form-control"
                     value="<?= htmlspecialchars($user['email']) ?>"
                     disabled>
              <small class="text-muted">Email address cannot be changed.</small>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold" style="color:#0D2B55;">Suburb</label>
              <input type="text" name="suburb" class="form-control"
                     value="<?= htmlspecialchars($user['suburb']) ?>" required>
            </div>

            <button type="submit" class="btn w-100 fw-bold"
                    style="background-color:#0D2B55; color:#ffffff;">
              Save Changes
            </button>
          </form>

          <!-- Account info -->
          <hr class="my-4">
          <div class="small text-muted">
            <p class="mb-1">
              <strong>Account Status:</strong>
              <span class="badge bg-success">Active</span>
            </p>
            <p class="mb-1">
              <strong>Member Since:</strong>
              <?= date('d M Y', strtotime($user['created_at'])) ?>
            </p>
            <p class="mb-0">
              <strong>Total Reports:</strong>
              <?= count($reports) ?>
            </p>
          </div>
        </div>
      </div>

      <!-- My Reports -->
      <div class="col-md-8">
        <div class="card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color:#0D2B55;">My Reports</h5>
            <a href="/report-create.php" class="btn btn-sm btn-primary">
              + Post New Report
            </a>
          </div>

          <?php if (empty($reports)): ?>
            <p class="text-muted">
              You haven't submitted any reports yet.
              <a href="/report-create.php">Post your first report</a>.
            </p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead style="background-color:#EAF4F6;">
                  <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reports as $report):
                    $typeColor   = $report['report_type'] === 'lost' ? 'bg-danger' : 'bg-success';
                    $statusColor = match($report['status']) {
                        'pending'  => 'bg-warning text-dark',
                        'active'   => 'bg-success',
                        'hidden'   => 'bg-secondary',
                        'resolved' => 'bg-info',
                        default    => 'bg-secondary'
                    };
                  ?>
                  <tr>
                    <td class="fw-bold" style="color:#0D2B55; max-width:200px;">
                      <?= htmlspecialchars($report['title']) ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($report['category']) ?></td>
                    <td>
                      <span class="badge <?= $typeColor ?>">
                        <?= ucfirst($report['report_type']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= $statusColor ?>">
                        <?= ucfirst($report['status']) ?>
                      </span>
                    </td>
                    <td class="small text-muted">
                      <?= date('d M Y', strtotime($report['report_date'])) ?>
                    </td>
                    <td>
                      <a href="/report-edit.php?id=<?= $report['id'] ?>"
                         class="btn btn-sm btn-outline-primary me-1">Edit</a>
                      <a href="/report-delete.php?id=<?= $report['id'] ?>"
                         class="btn btn-sm btn-outline-danger"
                         onclick="return confirm('Are you sure you want to delete this report?')">
                        Delete
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <div class="mt-3">
            <a href="/dashboard.php" class="btn btn-outline-secondary btn-sm">
              ← Back to Dashboard
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>