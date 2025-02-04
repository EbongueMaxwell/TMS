<?php
session_start(); // Start the session
include 'dbconn.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit();
?>