<?php
require_once 'includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<main class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7">
        <div class="card p-4">
          <h2 class="section-title">Post a Lost or Found Report</h2>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php endif; ?>
          <form action="backend/report/create.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold">Report Type</label>
              <select name="type" class="form-select" required>
                <option value="">-- Select Type --</option>
                <option value="lost">Lost</option>
                <option value="found">Found</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Category</label>
              <select name="category" class="form-select" required>
                <option value="">-- Select Category --</option>
                <option value="Pets">🐾 Pets</option>
                <option value="Electronics">📱 Electronics & Valuables</option>
                <option value="Documents">📄 Important Documents</option>
                <option value="Missing Persons">👤 Missing Persons</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Title</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Lost black Labrador — Fremantle" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea name="description" class="form-control" rows="4" placeholder="Describe the item or person in detail including distinguishing features..." required></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Suburb</label>
              <input type="text" name="suburb" class="form-control" placeholder="e.g. Fremantle" required>
            </div>
            <div class="mb-4">
              <label class="form-label fw-bold">Date Lost/Found</label>
              <input type="date" name="date_occurred" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Submit Report</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
