<?php
declare(strict_types=1);

// ============================================================
// FindIt — Database Connection
// Works on both XAMPP (local) and Render (production)
// DO NOT commit real passwords to GitHub
// ============================================================

$host     = getenv('DB_HOST')     ?: 'aws.connect.psdb.cloud';
$dbname   = getenv('DB_NAME')     ?: 'findit_db';
$username = getenv('DB_USERNAME') ?: '09e443smq3utpmr7m5mk';
$password = getenv('DB_PASSWORD') ?: 'YOUR_PLANETSCALE_PASSWORD';

// SSL cert path — Linux (Render) vs Windows (XAMPP)
$sslCert = file_exists('/etc/ssl/certs/ca-certificates.crt')
    ? '/etc/ssl/certs/ca-certificates.crt'
    : 'C:/xampp/apache/conf/ssl.crt/cacert.pem';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_CA       => $sslCert,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
} catch (PDOException $e) {
    error_log("DB Connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>