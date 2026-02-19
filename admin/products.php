<?php
$pageTitle = 'Manage Products';
require_once '../config/config.php';
requireAdmin();

// This is a placeholder page as products are replaced by speakers in this marketplace
setFlashMessage('info', 'This is a speaker marketplace. Products are managed as speakers.');
redirect('speakers.php');
