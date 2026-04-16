<?php
declare(strict_types=1);

// ============================================================
// FindIt — Database Connection (PlanetScale)
// DO NOT commit this file to GitHub — it is in .gitignore
// ============================================================

$host     = "aws.connect.psdb.cloud";
$dbname   = "findit_db";
$username = "09e443smq3utpmr7m5mk";
$password = "YOUR_PLANETSCALE_PASSWORD";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_CA       => "C:/xampp/apache/conf/ssl.crt/cacert.pem",
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
} catch (PDOException $e) {
    error_log("DB Connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>