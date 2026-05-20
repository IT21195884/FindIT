<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindIt - Community Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/findit/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0D2B55;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/findit/index.php">🔍 FindIt</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/findit/index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/findit/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/findit/report-create.php">Report Lost/Found</a></li>
                    <li class="nav-item"><a class="nav-link" href="/findit/admin/moderation.php">Moderation</a></li>
                    <li class="nav-item"><a class="nav-link" href="/findit/admin/users.php">Users</a></li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/findit/admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/findit/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/findit/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-warning text-dark px-3 ms-2" href="/findit/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
