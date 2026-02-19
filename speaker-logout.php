<?php
require_once 'config/config.php';

// Destroy speaker session
if (isSpeakerLoggedIn()) {
    unset($_SESSION['speaker_id']);
}

// Redirect to speaker login
redirect(SITE_URL . '/speaker-login.php');
?>
