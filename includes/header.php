<?php
// Prevent any output before headers
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

$currentUser = getCurrentUser();
$currentSpeaker = getCurrentSpeaker();
$cartCount = getCartCount();
$notificationCount = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Speaker Marketplace</title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1976d2;
            --secondary-color: #424242;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
            --light-bg: #f5f5f5;
            --dark-bg: #121212;
            --card-bg-light: #ffffff;
            --card-bg-dark: #1e1e1e;
            --text-light: #212121;
            --text-dark: #e0e0e0;
            --border-light: #e0e0e0;
            --border-dark: #333333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Light Theme Only */
        body {
            background-color: var(--light-bg);
            color: var(--text-light);
        }
        
        .card {
            background-color: var(--card-bg-light);
            border: 1px solid var(--border-light);
        }
        
        .navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 0;
        }
        
        /* Enhanced Navigation Spacing */
        .navbar-nav .nav-item {
            margin: 0 0.5rem;
        }
        
        .navbar-nav .nav-link {
            padding: 0.75rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: rgba(25, 118, 210, 0.1);
            color: var(--primary-color) !important;
            transform: translateY(-1px);
        }
        
        .navbar-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white !important;
        }
        
        /* Brand styling */
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-right: 2rem;
        }
        
        /* Login button enhancements */
        .btn.dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        
        /* Notification and cart badges */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            min-width: 18px;
            text-align: center;
        }
        
        /* Dropdown menu improvements */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(25, 118, 210, 0.1);
            color: var(--primary-color);
        }
        
        
        /* Desktop Navbar */
        .desktop-navbar {
            display: none;
        }
        
        @media (min-width: 768px) {
            .desktop-navbar {
                display: block;
            }
            .mobile-appbar,
            .bottom-nav {
                display: none !important;
            }
        }
        
        /* Mobile AppBar */
        .mobile-appbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--primary-color), #1565c0);
            color: white;
            padding: 12px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .mobile-appbar .logo {
            font-size: 20px;
            font-weight: 600;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
        }
        
        [data-theme="dark"] .bottom-nav {
            background-color: #1e1e1e;
            border-top: 1px solid var(--border-dark);
        }
        
        .bottom-nav-item {
            flex: 1;
            text-align: center;
            padding: 8px;
            text-decoration: none;
            color: #757575;
            transition: all 0.3s;
            position: relative;
        }
        
        .bottom-nav-item.active {
            color: var(--primary-color);
        }
        
        .bottom-nav-item i {
            font-size: 24px;
            display: block;
            margin-bottom: 4px;
        }
        
        .bottom-nav-item span {
            font-size: 12px;
            display: block;
        }
        
        .bottom-nav-item .badge {
            position: absolute;
            top: 4px;
            right: 50%;
            transform: translateX(12px);
        }
        
        /* Content spacing for mobile */
        @media (max-width: 767px) {
            .main-content {
                margin-top: 60px;
                margin-bottom: 70px;
                padding: 16px;
            }
        }
        
        @media (min-width: 768px) {
            .main-content {
                padding: 40px 0;
            }
        }
        
        /* Theme Toggle */
        .theme-toggle {
            cursor: pointer;
            font-size: 20px;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .theme-toggle:hover {
            background-color: rgba(0,0,0,0.1);
        }
        
        /* Product Cards */
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #1565c0);
            border: none;
            padding: 12px 24px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #1565c0);
            color: white;
            padding: 80px 0;
            text-align: center;
            border-radius: 0 0 30px 30px;
        }
        
        @media (max-width: 767px) {
            .hero-section {
                padding: 40px 20px;
                border-radius: 0 0 20px 20px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Badge */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Desktop Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light desktop-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-microphone-alt text-primary"></i> Speaker Marketplace
            </a>
            
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <a class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link <?php echo $currentPage == 'speakers' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/speakers.php">Find Speakers</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link <?php echo $currentPage == 'faq' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/faq.php">FAQ</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link <?php echo $currentPage == 'contact' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
                            <i class="fas fa-calendar-check"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if ($currentUser): ?>
                        <li class="nav-item dropdown me-3">
                            <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-mdb-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if ($notificationCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $notificationCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php 
                                $notifications = getRecentNotifications($currentUser['id']);
                                if (empty($notifications)): 
                                ?>
                                    <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <li>
                                            <a class="dropdown-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                               href="<?php echo SITE_URL; ?>/notifications.php?read=<?php echo $notification['id']; ?>">
                                                <div class="d-flex justify-content-between">
                                                    <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                                    <small class="text-muted"><?php echo timeAgo($notification['created_at']); ?></small>
                                                </div>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars(substr($notification['message'], 0, 60)) . '...'; ?></p>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-center" href="<?php echo SITE_URL; ?>/notifications.php">View All Notifications</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-mdb-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/bookings.php">My Bookings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php elseif ($currentSpeaker): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="speakerDropdown" role="button" data-mdb-toggle="dropdown">
                                <i class="fas fa-microphone-alt"></i> <?php echo htmlspecialchars($currentSpeaker['name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/speaker-dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/speaker-profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/speaker-logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown ms-2">
                            <a class="btn btn-primary dropdown-toggle px-4 py-2 rounded-pill shadow-sm" href="#" id="loginDropdown" role="button" data-mdb-toggle="dropdown" style="border: none; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); transition: all 0.3s ease;">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Event Organizers</h6></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/login.php">
                                    <i class="fas fa-calendar-alt me-2"></i>Organizer Login
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/register.php">
                                    <i class="fas fa-user-plus me-2"></i>Organizer Register
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Speakers</h6></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/speaker-login.php">
                                    <i class="fas fa-microphone-alt me-2"></i>Speaker Login
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/speaker-register.php">
                                    <i class="fas fa-user-plus me-2"></i>Speaker Register
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile AppBar -->
    <div class="mobile-appbar d-flex justify-content-between align-items-center">
        <div class="logo">
            <i class="fas fa-microphone-alt"></i> Speaker Market
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php if ($currentUser): ?>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="text-white">
                    <i class="fas fa-user-circle" style="font-size: 24px;"></i>
                </a>
            <?php elseif ($currentSpeaker): ?>
                <a href="<?php echo SITE_URL; ?>/speaker-dashboard.php" class="text-white">
                    <i class="fas fa-microphone-alt" style="font-size: 24px;"></i>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/login.php" class="text-white">
                    <i class="fas fa-sign-in-alt" style="font-size: 24px;"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bottom Navigation (Mobile) -->
    <div class="bottom-nav">
        <a href="<?php echo SITE_URL; ?>" class="bottom-nav-item <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/speakers.php" class="bottom-nav-item <?php echo $currentPage == 'speakers' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Speakers</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/cart.php" class="bottom-nav-item <?php echo $currentPage == 'cart' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>Bookings</span>
            <?php if ($cartCount > 0): ?>
                <span class="badge bg-danger"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo SITE_URL; ?>/<?php echo $currentUser ? 'bookings.php' : 'login.php'; ?>" class="bottom-nav-item <?php echo in_array($currentPage, ['bookings', 'profile']) ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Account</span>
        </a>
    </div>
    
    <script>
        // Add hover effect to login button
        document.addEventListener('DOMContentLoaded', function() {
            const loginBtn = document.getElementById('loginDropdown');
            if (loginBtn) {
                loginBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(0, 123, 255, 0.3)';
                });
                loginBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
                });
            }
        });
    </script>
