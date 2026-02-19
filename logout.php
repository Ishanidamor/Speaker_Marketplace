<?php
require_once 'config/config.php';

// Destroy session
session_destroy();

// Redirect to home
setFlashMessage('success', 'You have been logged out successfully');
redirect(SITE_URL);
?>
