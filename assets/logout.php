<?php
// Include configuration and security helper files (initializes session)
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session on the server side
session_destroy();

// Redirect back to login page
header("Location: login.php");
exit;
