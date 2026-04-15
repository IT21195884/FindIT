<?php
session_start();
require_once '../../includes/db.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: ../../login.php?error=Please enter your email and password.");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: ../../login.php?error=Invalid email or password.");
    exit();
}

if ($user['status'] !== 'active') {
    header("Location: ../../login.php?error=Your account has been deactivated. Please contact support.");
    exit();
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

if ($user['role'] === 'admin') {
    header("Location: ../../admin/dashboard.php");
} else {
    header("Location: ../../dashboard.php");
}
exit();