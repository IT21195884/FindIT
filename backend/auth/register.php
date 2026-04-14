<?php
require_once '../../includes/db.php';

// Sanitise inputs
$name = trim(htmlspecialchars($_POST['name']));
$email = trim(htmlspecialchars($_POST['email']));
$suburb = trim(htmlspecialchars($_POST['suburb']));
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// Validate
if (empty($name) || empty($email) || empty($suburb) || empty($password)) {
    header("Location: ../../register.php?error=All fields are required.");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../register.php?error=Invalid email address.");
    exit();
}

if (strlen($password) < 8) {
    header("Location: ../../register.php?error=Password must be at least 8 characters.");
    exit();
}

if ($password !== $confirm) {
    header("Location: ../../register.php?error=Passwords do not match.");
    exit();
}

// Check duplicate email
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header("Location: ../../register.php?error=This email is already registered. Please login.");
    exit();
}

// Hash password and insert user
$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, suburb, role, status, created_at) VALUES (?, ?, ?, ?, 'user', 'active', NOW())");
$stmt->execute([$name, $email, $hashed, $suburb]);

// Send confirmation email
require_once '../../backend/email/sendEmail.php';
$subject = "Welcome to FindIt!";
$body = file_get_contents('../../templates/email/confirmation.html');
$body = str_replace('{{name}}', $name, $body);
$body = str_replace('{{email}}', $email, $body);
sendEmail($email, $subject, $body);

header("Location: ../../login.php?success=Account created successfully! Please login.");
exit();
?>
