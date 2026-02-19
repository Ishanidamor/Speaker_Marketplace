<?php
$pageTitle = 'Manage Orders';
require_once '../config/config.php';
requireAdmin();

// This is a speaker marketplace, so we use bookings instead of orders
setFlashMessage('info', 'This is a speaker marketplace. Orders are managed as bookings.');
redirect('bookings.php');
