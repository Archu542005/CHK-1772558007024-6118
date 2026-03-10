<?php
require_once 'config.php';

// Destroy session
session_destroy();

// Clear admin cookies if they exist
if (isset($_COOKIE['admin_username'])) {
    setcookie('admin_username', '', time() - 3600, '/');
}
if (isset($_COOKIE['admin_department'])) {
    setcookie('admin_department', '', time() - 3600, '/');
}

// Redirect to admin login page
redirect('admin_login.html');
?>
