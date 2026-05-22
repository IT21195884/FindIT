<?php
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get report ID from URL
$reportId = (int)($_GET['id'] ?? 0);

if (!$reportId) {
    header("Location: /index.php");
    exit();
}

// Fetch report with owner details
$stmt = $pdo->prepare("
    SELECT r.*, u.name AS owner_name, u.email AS owner_email
    FROM reports r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
    AND r.status = 'active'
");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

// If report not found or not active redirect to homepage
if (!$report) {
    header("Location: /index.php?error=Report not found or no longer available.");
    exit();
}

// Map category to page link for back button
$categoryLinks = [
    'Pets'            => '/pets.php',
    'Electronics'     => '/electronics.php',
    'Documents'       => '/documents.php',
    'Missing Persons' => '/missing-persons.php',
];
$backLink  = $categoryLinks[$report['category']] ?? '/index.php';
$backLabel = $report['category'] . ' Reports';
?>

<main class="py-5">
  <div class="container">

    <!-- Back link -->
    <a href="<?= htmlspecialchars($backLink) ?>" class="text-decoration-none mb-4 d-inline-block"
       style="color:#0A7E8C; font-size:0.95rem;">
      ← Back to <?= htmlspecialchars($backLabel) ?>
    </a>

    <div class="card mt-3 p-4" style="border-radius:12px; border:1px solid #dddddd;">

      <!-- Badges row -->
      <div class="d-flex flex-wrap gap-2 mb-3">

        <?php if ($report['is_urgent']): ?>
          <span class="badge fs-6 px-3 py-2" style="background-color:#c0392b;">
            🚨 URGENT
          </span>
        <?php endif; ?>

        <span class="badge fs-6 px-3 py-2
          <?= $report['report_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>">
          <?= ucfirst(htmlspecialchars($report['report_type'])) ?>
        </span>

        <span class="badge fs-6 px-3 py-2"
              style="background-color:#EAF4F6; color:#0A7E8C; border:1px solid #0A7E8C;">
          <?= htmlspecialchars($report['category']) ?>
        </span>

      </div>

      <!-- Report title -->
      <h2 class="fw-bold mb-4" style="color:#0D2B55;">
        <?= htmlspecialchars($report['title']) ?>
      </h2>

      <!-- Two column layout -->
      <div class="row g-4">

        <!-- Left column: Image -->
        <div class="col-md-6">
          <?php if (!empty($report['image_path'])): ?>
            <img src="<?= htmlspecialchars($report['image_path']) ?>"
                 alt="Report image"
                 class="img-fluid rounded"
                 style="width:100%; max-height:380px; object-fit:cover; border:1px solid #eeeeee;">
          <?php else: ?>
            <div class="d-flex align-items-center justify-content-center rounded bg-light text-muted"
                 style="height:280px; border:1px solid #eeeeee; font-size:1rem;">
              No Image Provided
            </div>
          <?php endif; ?>
        </div>

        <!-- Right column: Details -->
        <div class="col-md-6">
          <div class="p-4 rounded h-100" style="background-color:#EAF4F6;">

            <h5 class="fw-bold mb-3" style="color:#0D2B55;">Report Details</h5>

            <!-- Description -->
            <p class="fw-bold mb-1" style="color:#0D2B55;">Description:</p>
            <p class="mb-3" style="color:#444444; line-height:1.6;">
              <?= nl2br(htmlspecialchars($report['description'])) ?>
            </p>

            <!-- Details grid -->
            <table class="table table-borderless mb-3" style="font-size:0.95rem;">
              <tbody>
                <tr>
                  <td class="fw-bold ps-0" style="color:#0D2B55; width:40%;">
                    📍 Suburb:
                  </td>
                  <td style="color:#444444;">
                    <?= htmlspecialchars($report['suburb']) ?>
                  </td>
                </tr>
                <tr>
                  <td class="fw-bold ps-0" style="color:#0D2B55;">
                    📅 Date <?= $report['report_type'] === 'lost' ? 'Lost' : 'Found' ?>:
                  </td>
                  <td style="color:#444444;">
                    <?= date('d M Y', strtotime($report['report_date'])) ?>
                  </td>
                </tr>
                <tr>
                  <td class="fw-bold ps-0" style="color:#0D2B55;">
                    👤 Submitted By:
                  </td>
                  <td style="color:#444444;">
                    <?= htmlspecialchars($report['owner_name']) ?>
                  </td>
                </tr>
                <tr>
                  <td class="fw-bold ps-0" style="color:#0D2B55;">
                    🗓️ Posted On:
                  </td>
                  <td style="color:#444444;">
                    <?= date('d M Y', strtotime($report['created_at'])) ?>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Contact Owner button -->
            <?php if (isset($_SESSION['user_id'])): ?>

              <?php
              // Reveal email only if logged in
              $showEmail = isset($_GET['contact']) && $_GET['contact'] === '1';
              ?>

              <?php if ($showEmail): ?>
                <div class="alert mb-0"
                     style="background-color:#d5e8d4; border:1px solid #82b366; color:#2e7d32; font-size:0.95rem;">
                  <strong>📧 Owner's Email:</strong>
                  <a href="mailto:<?= htmlspecialchars($report['owner_email']) ?>"
                     style="color:#2e7d32;">
                    <?= htmlspecialchars($report['owner_email']) ?>
                  </a>
                  <br>
                  <small class="text-muted">Please mention the FindIt report title when contacting.</small>
                </div>
              <?php else: ?>
                <a href="/report-detail.php?id=<?= $reportId ?>&contact=1"
                   class="btn w-100 fw-bold"
                   style="background-color:#0A7E8C; color:#ffffff; font-size:1rem; padding:12px;">
                  📧 Contact Owner
                </a>
                <small class="text-muted d-block mt-2">
                  Click to reveal the owner's contact email.
                </small>
              <?php endif; ?>

            <?php else: ?>

              <!-- Not logged in — prompt to login -->
              <a href="/login.php"
                 class="btn w-100 fw-bold"
                 style="background-color:#0D2B55; color:#ffffff; font-size:1rem; padding:12px;">
                Login to Contact Owner
              </a>
              <small class="text-muted d-block mt-2">
                You must be logged in to contact the report owner.
              </small>

            <?php endif; ?>

          </div>
        </div>

      </div><!-- end row -->

      <!-- Back button bottom -->
      <div class="mt-4">
        <a href="<?= htmlspecialchars($backLink) ?>"
           class="btn btn-outline-secondary">
          ← Back to <?= htmlspecialchars($backLabel) ?>
        </a>
      </div>

    </div><!-- end card -->

  </div>
</main>

<?php require_once 'includes/footer.php'; ?>