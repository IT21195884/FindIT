<?php require_once 'includes/header.php'; ?>

<main class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card p-4">
          <h2 class="section-title">Create an Account</h2>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php endif; ?>
          <form action="backend/auth/register.php" method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label fw-bold">Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Jane Smith" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="e.g. jane@email.com" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Suburb</label>
              <input type="text" name="suburb" class="form-control" placeholder="e.g. Fremantle" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
            </div>
            <div class="mb-4">
              <label class="form-label fw-bold">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Create Account</button>
          </form>
          <p class="text-center mt-3 text-muted">Already have an account? <a href="login.php">Login here</a></p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
