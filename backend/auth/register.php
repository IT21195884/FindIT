<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$name = sanitize($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$suburb = sanitize($_POST['suburb'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($name) || empty($email) || empty($suburb) || empty($password) || empty($confirmPassword)) {
    header("Location: ../../register.php?error=Please fill in all fields.");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../register.php?error=Please enter a valid email address.");
    exit();
}

if ($password !== $confirmPassword) {
    header("Location: ../../register.php?error=Passwords do not match.");
    exit();
}

if (strlen($password) < 8) {
    header("Location: ../../register.php?error=Password must be at least 8 characters long.");
    exit();
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    header("Location: ../../register.php?error=Email is already registered.");
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert new user
$stmt = $pdo->prepare("
    INSERT INTO users (name, email, suburb, password, role, status)
    VALUES (?, ?, ?, ?, 'user', 'active')
");
$stmt->execute([$name, $email, $suburb, $hashedPassword]);

header("Location: ../../login.php?success=Registration successful. Please log in.");
exit();
?>