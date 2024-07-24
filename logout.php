<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to portfolio.html
header("Location: portfolio.html");
exit();
?>
