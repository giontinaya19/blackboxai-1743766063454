<?php
require_once 'db-connect.php';

// Log logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    executeQuery(
        "INSERT INTO security_logs (user_id, action, ip_address, user_agent) 
        VALUES (?, ?, ?, ?)",
        [$_SESSION['user_id'], 'logout', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
    );
}

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();