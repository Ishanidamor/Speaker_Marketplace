<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$currentAdmin = getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Panel</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 60px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #1976d2 0%, #1565c0 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            width: 30px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Top Header */
        .top-header {
            background: white;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Cards */
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block !important;
            }
        }
        
        .mobile-toggle {
            display: none;
        }
        
        /* Dark theme support */
        [data-theme="dark"] {
            background-color: #121212;
            color: #e0e0e0;
        }
        
        [data-theme="dark"] .card {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }
        
        [data-theme="dark"] .top-header {
            background-color: #1e1e1e;
            border-bottom: 1px solid #333;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4 class="fw-bold mb-0">
                <i class="fas fa-shield-alt me-2"></i>
                Admin Panel
            </h4>
            <small class="d-block mt-2">Speaker Marketplace</small>
        </div>
        
        <div class="sidebar-menu">
            <a href="index.php" class="<?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="products.php" class="<?php echo $currentPage == 'products' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            
            <a href="categories.php" class="<?php echo $currentPage == 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
            
            <a href="orders.php" class="<?php echo $currentPage == 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
            
            <a href="users.php" class="<?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            
            <a href="support.php" class="<?php echo $currentPage == 'support' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i>
                <span>Support Tickets</span>
            </a>
            
            <a href="faqs.php" class="<?php echo $currentPage == 'faqs' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                <span>FAQs</span>
            </a>
            
            <hr style="border-color: rgba(255,255,255,0.2);">
            
            <a href="../index.php" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>View Website</span>
            </a>
            
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="d-flex align-items-center">
                <button class="btn btn-link mobile-toggle me-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h5 class="mb-0 fw-bold"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h5>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <span class="theme-toggle" onclick="toggleTheme()" style="cursor: pointer;">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </span>
                
                <div class="dropdown">
                    <a class="text-decoration-none text-dark dropdown-toggle" href="#" role="button" data-mdb-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg me-2"></i>
                        <span class="fw-bold"><?php echo htmlspecialchars($currentAdmin['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <div class="page-content">
