<?php require_once 'includes/header.php'; ?>

<main class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card p-4">
          <h2 class="section-title">Login to FindIt</h2>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php endif; ?>
          <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
          <?php endif; ?>
          <form action="backend/auth/login.php" method="POST">
            <div class="mb-3">
              <label class="form-label fw-bold">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="e.g. jane@email.com" required>
            </div>
            <div class="mb-4">
              <label class="form-label fw-bold">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Login</button>
          </form>
          <p class="text-center mt-3 text-muted">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
