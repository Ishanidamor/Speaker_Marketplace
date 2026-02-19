<?php
$pageTitle = 'Reset Password';
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/profile.php');
}

$error = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    redirect(SITE_URL . '/forgot-password.php');
}

// Verify token
$resetRequest = fetchOne("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()", 
                        [$token], 's');

if (!$resetRequest) {
    $error = 'Invalid or expired reset token';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        executeQuery("UPDATE users SET password = ? WHERE email = ?",
                    [$hashedPassword, $resetRequest['email']], 'ss');
        
        // Delete used token
        executeQuery("DELETE FROM password_resets WHERE token = ?", [$token], 's');
        
        setFlashMessage('success', 'Password reset successfully! You can now login with your new password.');
        redirect(SITE_URL . '/login.php');
    }
}

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-lock fa-4x text-primary mb-3"></i>
                            <h2 class="fw-bold">Reset Password</h2>
                            <p class="text-muted">Enter your new password</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                            
                            <?php if (strpos($error, 'expired') !== false): ?>
                                <div class="text-center">
                                    <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="btn btn-primary">
                                        Request New Reset Link
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" 
                                               name="password" 
                                               class="form-control" 
                                               placeholder="Enter new password"
                                               minlength="6"
                                               required>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" 
                                               name="confirm_password" 
                                               class="form-control" 
                                               placeholder="Confirm new password"
                                               required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                                    <i class="fas fa-check me-2"></i> Reset Password
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="text-primary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
