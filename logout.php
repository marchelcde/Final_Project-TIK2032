<?php
// logout.php
// Location: FINAL_PROJECT-TIK2032/logout.php

// Ensure session is started before accessing session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the main index.php after logout (now at root)
header("location: index.php");
exit;
?>
