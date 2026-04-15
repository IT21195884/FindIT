//tester email php. delet after testing. 

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// FindIt — Email Debug Test File
// Access at: http://localhost/findit/test_email_debug.php
// DELETE after testing!

// Show ALL errors on screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<style>
body{font-family:Arial;padding:30px;background:#f4f4f4}
.card{background:#fff;border-radius:10px;padding:24px;margin-bottom:16px;border-left:4px solid #ccc}
.pass{border-left-color:#28a745}
.fail{border-left-color:#dc3545}
.warn{border-left-color:#F4A827}
h2{color:#0D2B55}
h3{margin:0 0 8px}
p{margin:4px 0;font-size:14px;color:#444}
code{background:#f0f0f0;padding:2px 8px;border-radius:4px;font-size:13px}
</style>";

echo "<h2>🔍 FindIt — Email Debug Report</h2>";

// ── Step 1: Check vendor folder ──────────────────────────────
echo "<div class='card " . (is_dir(__DIR__.'/vendor') ? 'pass' : 'fail') . "'>";
echo "<h3>" . (is_dir(__DIR__.'/vendor') ? '✅' : '❌') . " Step 1 — Composer vendor folder</h3>";
if (is_dir(__DIR__.'/vendor')) {
    echo "<p>Vendor folder found at: <code>" . __DIR__ . "/vendor</code></p>";
} else {
    echo "<p>❌ Vendor folder NOT found. Run this in your findit folder:</p>";
    echo "<p><code>composer require phpmailer/phpmailer</code></p>";
}
echo "</div>";

// ── Step 2: Check PHPMailer autoload ────────────────────────
$autoload = __DIR__ . '/vendor/autoload.php';
echo "<div class='card " . (file_exists($autoload) ? 'pass' : 'fail') . "'>";
echo "<h3>" . (file_exists($autoload) ? '✅' : '❌') . " Step 2 — PHPMailer autoload</h3>";
if (file_exists($autoload)) {
    require_once $autoload;
    echo "<p>PHPMailer autoload.php found and loaded successfully.</p>";
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p>✅ PHPMailer class is available.</p>";
    } else {
        echo "<p>❌ PHPMailer class NOT found even though autoload exists. Try reinstalling:</p>";
        echo "<p><code>composer require phpmailer/phpmailer</code></p>";
    }
} else {
    echo "<p>❌ autoload.php not found.</p>";
}
echo "</div>";

// ── Step 3: Check email template ────────────────────────────
$template = __DIR__ . '/templates/email/confirmation.html';
echo "<div class='card " . (file_exists($template) ? 'pass' : 'fail') . "'>";
echo "<h3>" . (file_exists($template) ? '✅' : '❌') . " Step 3 — Email template file</h3>";
if (file_exists($template)) {
    echo "<p>Template found at: <code>templates/email/confirmation.html</code></p>";
} else {
    echo "<p>❌ Template not found. Make sure confirmation.html is in your templates/email/ folder.</p>";
}
echo "</div>";

// ── Step 4: Check PHP extensions ────────────────────────────
$openssl = extension_loaded('openssl');
$curl    = extension_loaded('curl');
echo "<div class='card " . ($openssl && $curl ? 'pass' : 'fail') . "'>";
echo "<h3>" . ($openssl && $curl ? '✅' : '❌') . " Step 4 — PHP Extensions</h3>";
echo "<p>OpenSSL: " . ($openssl ? '✅ Enabled' : '❌ Disabled — needed for SMTP TLS') . "</p>";
echo "<p>cURL: "    . ($curl    ? '✅ Enabled' : '❌ Disabled') . "</p>";
if (!$openssl) {
    echo "<p>To enable OpenSSL: open <code>D:/Xampp/php/php.ini</code>, find <code>;extension=openssl</code> and remove the semicolon. Restart Apache.</p>";
}
echo "</div>";

// ── Step 5: Try SMTP connection ──────────────────────────────
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<div class='card warn'>";
    echo "<h3>⚙️ Step 5 — Attempting SMTP connection (detailed)</h3>";
    echo "<p>Connecting to <code>mail.smtp2go.com:587</code>...</p>";

    $mail = new PHPMailer(true);

    try {
        // Enable verbose debug output
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = function($str, $level) {
            echo "<p style='font-size:12px;color:#666;margin:2px 0'><code>" . htmlspecialchars($str) . "</code></p>";
        };

        $mail->isSMTP();
        $mail->Host       = 'mail.smtp2go.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'capstone2026';
        $mail->Password   = 'capstonescu2026';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 2525;

        $mail->setFrom('p.das.11@student.scu.edu.au', 'FindIt Test');
        $mail->addAddress('pattriciaruth@gmail.com');
        $mail->isHTML(true);
        $mail->Subject = 'FindIt Debug Test Email';
        $mail->Body    = '<h2>Test email from FindIt!</h2><p>If you see this, your email system is working!</p>';

        $mail->send();
        echo "<p style='color:#28a745;font-weight:bold;font-size:16px'>✅ Email sent successfully to {$test_to_email}!</p>";

    } catch (Exception $e) {
        echo "<p style='color:#dc3545;font-weight:bold'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:#dc3545'>Mailer error: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    }
    echo "</div>";
}

// ── Step 6: PHP Info Summary ─────────────────────────────────
echo "<div class='card'>";
echo "<h3>ℹ️ Step 6 — PHP Environment Info</h3>";
echo "<p>PHP Version: <code>" . phpversion() . "</code></p>";
echo "<p>SMTP setting in php.ini: <code>" . ini_get('SMTP') . "</code></p>";
echo "<p>smtp_port in php.ini: <code>" . ini_get('smtp_port') . "</code></p>";
echo "<p>Error log location: <code>" . ini_get('error_log') . "</code></p>";
echo "</div>";

echo "<div class='card warn'>";
echo "<h3>⚠️ Remember</h3>";
echo "<p>Delete <code>test_email_debug.php</code> after you finish debugging!</p>";
echo "</div>";
?>
