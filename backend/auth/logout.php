<?php
session_start();

$_SESSION = [];
session_unset();
session_destroy();    //destroy session completely

header("Location: ../../login.php?success=You have been logged out successfully.");
exit();   //Redirecting back to login page and exit