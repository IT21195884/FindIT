<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_admin(): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: ../login.php?error=Admin access required.");
        exit();
    }
}