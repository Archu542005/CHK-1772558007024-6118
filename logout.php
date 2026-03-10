<?php
require_once 'config.php';

// Destroy session
session_destroy();

// Clear cookies if they exist
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}
if (isset($_COOKIE['user_password'])) {
    setcookie('user_password', '', time() - 3600, '/');
}

// Redirect to home page
redirect('index.html');
?>
