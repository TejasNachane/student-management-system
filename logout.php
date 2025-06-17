<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Start a new session for the notification
session_start();
$_SESSION['notification'] = [
    'message' => 'You have been logged out successfully',
    'type' => 'success'
];

// Redirect to login page
header("Location: login.php");
exit();
?>
