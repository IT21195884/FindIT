<?php
// ============================================================
// FindIt — auth.php
// Reusable authentication helper functions
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php?error=Please login first.");
        exit();
    }
}

function require_admin(): void
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: /login.php?error=Access denied.");
        exit();
    }
}