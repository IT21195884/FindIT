<?php
// ============================================================
// FindIt — Match Detection Engine
// Called automatically after every new report submission
// ============================================================

require_once __DIR__ . '/../../backend/email/sendEmail.php';

/**
 * Calculate a match score between two reports.
 * Returns 0.0 if category doesn't match (hard requirement).
 * Score breakdown:
 *   - Category match: 0.40 (required)
 *   - Suburb match:   0.35
 *   - Keyword overlap: 0.25
 */
function calculateMatchScore(array $newReport, array $existing): float
{
    // Category must match — hard requirement
    if ($newReport['category'] !== $existing['category']) {
        return 0.0;
    }
    $score = 0.40;

    // Suburb match
    if (!empty($newReport['suburb']) && strtolower($newReport['suburb']) === strtolower($existing['suburb'])) {
        $score += 0.35;
    }

    // Keyword overlap between title + description
    $text1 = strtolower($newReport['title'] . ' ' . $newReport['description']);
    $text2 = strtolower($existing['title'] . ' ' . $existing['description']);

    $words1 = array_unique(str_word_count($text1, 1));
    $words2 = array_unique(str_word_count($text2, 1));

    // Filter out common stop words
    $stopWords = ['the','a','an','is','it','in','on','at','to','of','and','or','for','with','my','i','was','were','been','have','has'];
    $words1 = array_diff($words1, $stopWords);
    $words2 = array_diff($words2, $stopWords);

    $overlap = count(array_intersect($words1, $words2));
    $total   = count(array_unique(array_merge($words1, $words2)));

    if ($total > 0) {
        $score += ($overlap / $total) * 0.25;
    }

    return round($score, 2);
}

/**
 * Send match notification emails to both report owners.
 */
function notifyMatchOwners(PDO $pdo, array $report1, array $report2): void
{
    // Get owner emails
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");

    $stmt->execute([$report1['user_id']]);
    $owner1 = $stmt->fetch();

    $stmt->execute([$report2['user_id']]);
    $owner2 = $stmt->fetch();

    if (!$owner1 || !$owner2) return;

    // Don't email if same user owns both reports
    if ($report1['user_id'] === $report2['user_id']) return;

    $templatePath = __DIR__ . '/../../templates/email/match_notification.html';

    // Notify owner 1
    $body1 = loadEmailTemplate($templatePath, [
        '{{owner_name}}'     => htmlspecialchars($owner1['name']),
        '{{matched_title}}'  => htmlspecialchars($report2['title']),
        '{{matched_suburb}}' => htmlspecialchars($report2['suburb']),
        '{{matched_type}}'   => ucfirst($report2['report_type']),
        '{{matched_category}}' => htmlspecialchars($report2['category']),
        '{{match_link}}'     => 'http://' . $_SERVER['HTTP_HOST'] . '/findit/report-detail.php?id=' . $report2['id'],
    ]);
    sendEmail($owner1['email'], "FindIt — A potential match has been found for your report", $body1);

    // Notify owner 2
    $body2 = loadEmailTemplate($templatePath, [
        '{{owner_name}}'     => htmlspecialchars($owner2['name']),
        '{{matched_title}}'  => htmlspecialchars($report1['title']),
        '{{matched_suburb}}' => htmlspecialchars($report1['suburb']),
        '{{matched_type}}'   => ucfirst($report1['report_type']),
        '{{matched_category}}' => htmlspecialchars($report1['category']),
        '{{match_link}}'     => 'http://' . $_SERVER['HTTP_HOST'] . '/findit/report-detail.php?id=' . $report1['id'],
    ]);
    sendEmail($owner2['email'], "FindIt — A potential match has been found for your report", $body2);
}

/**
 * Main function — run match detection for a newly submitted report.
 * Called from backend/report/create.php after insert.
 */
function runMatchDetection(PDO $pdo, int $newReportId): void
{
    $MATCH_THRESHOLD = 0.60;

    // Fetch the new report
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$newReportId]);
    $newReport = $stmt->fetch();

    if (!$newReport) return;

    // Opposite type to compare against
    $oppositeType = ($newReport['report_type'] === 'lost') ? 'found' : 'lost';

    // Fetch all active/pending reports of opposite type (exclude own reports)
    $stmt = $pdo->prepare("
        SELECT * FROM reports
        WHERE report_type = ?
        AND status IN ('active', 'pending')
        AND user_id != ?
        AND id != ?
    ");
    $stmt->execute([$oppositeType, $newReport['user_id'], $newReportId]);
    $candidates = $stmt->fetchAll();

    foreach ($candidates as $existing) {
        $score = calculateMatchScore($newReport, $existing);

        if ($score < $MATCH_THRESHOLD) continue;

        // Prevent duplicate matches — check both orderings
        $checkStmt = $pdo->prepare("
            SELECT id FROM matches
            WHERE (report_id_1 = ? AND report_id_2 = ?)
               OR (report_id_1 = ? AND report_id_2 = ?)
        ");
        $checkStmt->execute([$newReportId, $existing['id'], $existing['id'], $newReportId]);

        if ($checkStmt->fetch()) continue; // Already exists

        // Insert match record
        $insertStmt = $pdo->prepare("
            INSERT INTO matches (report_id_1, report_id_2, match_score, status, notified)
            VALUES (?, ?, ?, 'new', 0)
        ");
        $insertStmt->execute([$newReportId, $existing['id'], $score]);

        // Send notification emails
        notifyMatchOwners($pdo, $newReport, $existing);

        // Mark as notified
        $matchId = $pdo->lastInsertId();
        $pdo->prepare("UPDATE matches SET notified = 1 WHERE id = ?")->execute([$matchId]);
    }
}
?>