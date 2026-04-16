<?php
session_start();
require_once '../../includes/db.php';

$email = trim($_POST['email'] ?? '');    //Get login credentials(email and password) from the login form
$password = $_POST['password'] ?? '';


//checking empty fields
if (empty($email) || empty($password)) {
    header("Location: ../../login.php?error=Please enter your email and password.");
    exit();
}


//Matching details with the Databse and figuring out the user
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();


//Check weather the password is correct 
if (!$user || !password_verify($password, $user['password'])) {
    header("Location: ../../login.php?error=Invalid email or password.");
    exit();
}

//Check weather the active is active or not
if ($user['status'] !== 'active') {
    header("Location: ../../login.php?error=Your account has been deactivated. Please contact support.");
    exit();
}

session_regenerate_id(true);   //create unique session ID ans store importrant user details in each session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

if ($user['role'] === 'admin') {
    header("Location: ../../admin/dashboard.php");
} else {
    header("Location: ../../dashboard.php");
}
exit();