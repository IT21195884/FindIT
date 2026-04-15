<?php
// ============================================================
// FindIt — sendEmail.php
// Reusable email sending function using PHPMailer + SMTP2GO
// ============================================================
// HOW TO USE from anywhere in the project:
//   require_once 'path/to/backend/email/sendEmail.php';
//   sendEmail("recipient@email.com", "Subject Here", $htmlBody);
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer via Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// ── SMTP2GO Credentials ──────────────────────────────────────
// TODO: Replace these with your actual SMTP2GO credentials
// Get them from: https://app.smtp2go.com → Settings → SMTP Users
define('SMTP_HOST',     'mail.smtp2go.com');
define('SMTP_PORT',     2525);
define('SMTP_USERNAME', 'capstone2026');
define('SMTP_PASSWORD', 'capstonescu2026');
define('MAIL_FROM',     'p.das.11@student.scu.edu.au');
define('MAIL_NAME',     'FindIt Community Platform');
// ─────────────────────────────────────────────────────────────

/**
 * Send an HTML email via SMTP2GO
 *
 * @param string $to        Recipient email address
 * @param string $subject   Email subject line
 * @param string $htmlBody  Full HTML content of the email
 * @return bool             Returns true if sent, false if failed
 */
function sendEmail(string $to, string $subject, string $htmlBody): bool {

    $mail = new PHPMailer(true);

    try {
        // ── Server Settings ──────────────────────────────
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // ── Sender & Recipient ───────────────────────────
        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(MAIL_FROM, MAIL_NAME);

        // ── Email Content ────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        // Plain text fallback for email clients that don't support HTML
        $mail->AltBody = strip_tags(
            str_replace(['<br>', '<br/>', '<br />', '</p>', '</h1>', '</h2>'], "\n", $htmlBody)
        );

        // ── Send ─────────────────────────────────────────
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log error silently — never expose SMTP details to the user
        error_log('[FindIt Email Error] Failed to send to: ' . $to . ' | Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Load an HTML email template and replace placeholders
 *
 * @param string $templateFile  Path to the HTML template file
 * @param array  $variables     Key-value pairs to replace e.g. ['{{name}}' => 'Jane']
 * @return string               Final HTML with placeholders replaced
 */
function loadEmailTemplate(string $templateFile, array $variables = []): string {
    if (!file_exists($templateFile)) {
        error_log('[FindIt Email] Template not found: ' . $templateFile);
        return '';
    }
    $html = file_get_contents($templateFile);
    foreach ($variables as $placeholder => $value) {
        $html = str_replace($placeholder, $value, $html);
    }
    return $html;
}
?>
