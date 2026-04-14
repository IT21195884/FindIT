<?php require_once 'includes/header.php'; ?>

<main>
  <!-- Hero Section -->
  <section style="background-color: #0D2B55; color: white;" class="py-5">
    <div class="container text-center py-3">
      <h1 class="display-5 fw-bold">🔍 FindIt</h1>
      <p class="lead mb-4">Western Australia's community lost and found platform.<br>Post a report. Search. Reunite.</p>
      <form action="browse.php" method="GET" class="d-flex justify-content-center gap-2 flex-wrap">
        <input type="text" name="q" class="form-control w-50" placeholder="Search lost or found items...">
        <button type="submit" class="btn btn-warning fw-bold px-4">Search</button>
      </form>
      <div class="mt-3">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="register.php" class="btn btn-warning me-2 fw-bold">Register</a>
          <a href="login.php" class="btn btn-outline-light">Login</a>
        <?php else: ?>
          <a href="report-create.php" class="btn btn-warning fw-bold">+ Post a Report</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Categories Section -->
  <section class="py-5">
    <div class="container">
      <h2 class="section-title text-center">Browse by Category</h2>
      <div class="row g-4 text-center">
        <?php
        $categories = [
          ['icon' => '🐾', 'name' => 'Pets', 'desc' => 'Dogs, cats, birds and more'],
          ['icon' => '📱', 'name' => 'Electronics', 'desc' => 'Phones, laptops, wallets, keys'],
          ['icon' => '📄', 'name' => 'Documents', 'desc' => 'IDs, passports, bank cards'],
          ['icon' => '👤', 'name' => 'Missing Persons', 'desc' => 'Urgent community reports'],
        ];
        foreach ($categories as $cat): ?>
          <div class="col-6 col-md-3">
            <div class="card category-card p-4 h-100" style="border-top: 4px solid #0A7E8C;">
              <div style="font-size: 2.5rem;"><?= $cat['icon'] ?></div>
              <h5 class="mt-2 fw-bold" style="color: #0D2B55;"><?= $cat['name'] ?></h5>
              <p class="text-muted small"><?= $cat['desc'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="py-5" style="background-color: #EAF4F6;">
    <div class="container">
      <h2 class="section-title text-center">How It Works</h2>
      <div class="row g-4 text-center">
        <div class="col-md-4">
          <div class="card p-4 h-100">
            <div style="font-size:2rem;">📝</div>
            <h5 class="mt-3 fw-bold" style="color:#0D2B55;">1. Post a Report</h5>
            <p class="text-muted">Register and submit a lost or found report with photos, description, and location.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-4 h-100">
            <div style="font-size:2rem;">🔍</div>
            <h5 class="mt-3 fw-bold" style="color:#0D2B55;">2. We Search for Matches</h5>
            <p class="text-muted">FindIt automatically compares your report with others and alerts you of potential matches.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card p-4 h-100">
            <div style="font-size:2rem;">🤝</div>
            <h5 class="mt-3 fw-bold" style="color:#0D2B55;">3. Reunite</h5>
            <p class="text-muted">Connect with the finder or owner and recover your lost item or loved one.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require_once 'includes/footer.php'; ?>
