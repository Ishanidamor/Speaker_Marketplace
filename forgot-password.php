<?php
$pageTitle = 'Forgot Password';
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/profile.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email], 's');
        
        if ($user) {
            // Generate reset token
            $token = generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete old tokens for this email
            executeQuery("DELETE FROM password_resets WHERE email = ?", [$email], 's');
            
            // Insert new token
            executeQuery("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)",
                        [$email, $token, $expiresAt], 'sss');
            
            // In production, send email with reset link
            $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
            
            // For demo, just show success message
            $success = 'Password reset instructions have been sent to your email. (Demo: Check the link below)';
            $success .= '<br><small class="text-muted">Reset Link: <a href="' . $resetLink . '">' . $resetLink . '</a></small>';
        } else {
            // Don't reveal if email exists or not (security best practice)
            $success = 'If an account exists with this email, you will receive password reset instructions.';
        }
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
                            <i class="fas fa-key fa-4x text-primary mb-3"></i>
                            <h2 class="fw-bold">Forgot Password?</h2>
                            <p class="text-muted">Enter your email to reset your password</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" 
                                               name="email" 
                                               class="form-control" 
                                               placeholder="Enter your email"
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
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
