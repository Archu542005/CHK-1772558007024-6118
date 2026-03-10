<?php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'logged_in' => false,
    'user_name' => null,
    'admin_logged_in' => false,
    'admin_name' => null
];

if (is_logged_in()) {
    $response['logged_in'] = true;
    $response['user_name'] = $_SESSION['user_name'];
}

if (is_admin_logged_in()) {
    $response['admin_logged_in'] = true;
    $response['admin_name'] = $_SESSION['admin_name'];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
