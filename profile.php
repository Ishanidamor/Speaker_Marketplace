<?php
$pageTitle = 'My Profile';
require_once 'config/config.php';

requireLogin();

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email is taken by another user
        $existing = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", 
                            [$email, $_SESSION['user_id']], 'si');
        
        if ($existing) {
            $error = 'Email already in use';
        } else {
            executeQuery("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?",
                        [$name, $email, $phone, $_SESSION['user_id']], 'sssi');
            $_SESSION['user_name'] = $name;
            setFlashMessage('success', 'Profile updated successfully');
            redirect(SITE_URL . '/profile.php');
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (!password_verify($currentPassword, $currentUser['password'])) {
        $error = 'Current password is incorrect';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        executeQuery("UPDATE users SET password = ? WHERE id = ?", 
                    [$hashedPassword, $_SESSION['user_id']], 'si');
        setFlashMessage('success', 'Password changed successfully');
        redirect(SITE_URL . '/profile.php');
    }
}

// Get user statistics
$totalOrders = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE organizer_id = ?", 
                       [$_SESSION['user_id']], 'i')['count'];
$totalSpent = fetchOne("SELECT SUM(final_amount) as total FROM bookings WHERE organizer_id = ? AND payment_status = 'completed'", 
                      [$_SESSION['user_id']], 'i')['total'] ?? 0;

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <h1 class="fw-bold mb-4">
            <i class="fas fa-user-circle me-2"></i> My Profile
        </h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- Profile Stats -->
            <div class="col-md-12">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card text-center p-4">
                            <i class="fas fa-shopping-bag fa-3x text-primary mb-3"></i>
                            <h3 class="fw-bold mb-0"><?php echo $totalOrders; ?></h3>
                            <p class="text-muted mb-0">Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center p-4">
                            <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                            <h3 class="fw-bold mb-0"><?php echo formatPrice($totalSpent); ?></h3>
                            <p class="text-muted mb-0">Total Spent</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center p-4">
                            <i class="fas fa-download fa-3x text-info mb-3"></i>
                            <h3 class="fw-bold mb-0"><?php echo $totalOrders; ?></h3>
                            <p class="text-muted mb-0">Downloads</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i> Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Member Since</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?>" readonly>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-lock me-2"></i> Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-link me-2"></i> Quick Links
                        </h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-bag me-2 text-primary"></i> My Orders
                        </a>
                        <a href="<?php echo SITE_URL; ?>/products.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-store me-2 text-primary"></i> Browse Products
                        </a>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2 text-primary"></i> Contact Support
                        </a>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
