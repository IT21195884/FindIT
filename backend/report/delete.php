<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=Please login first.");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;    //Converting the report ID into an Integer(Using URL)


//Validating the report ID
if ($id <= 0) {
    header("Location: ../../dashboard.php?error=Invalid report ID.");
    exit();
}

//Delete only the selected report from logged in user
$stmt = $pdo->prepare("DELETE FROM reports WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);


//Redirecting back to the dashboard
header("Location: ../../dashboard.php?success=Report deleted successfully.");
exit();
?>