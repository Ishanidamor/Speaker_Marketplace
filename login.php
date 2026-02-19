<?php
$pageTitle = 'Login';
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/profile.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $user = fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email], 's');
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Transfer cart items from session to user
            $sessionId = session_id();
            executeQuery("UPDATE booking_cart SET user_id = ?, session_id = NULL WHERE session_id = ?", 
                        [$user['id'], $sessionId], 'is');
            
            setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(SITE_URL . '/profile.php');
        } else {
            $error = 'Invalid email or password';
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
                            <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                            <h2 class="fw-bold">Welcome Back</h2>
                            <p class="text-muted">Login to your account</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
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
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           name="password" 
                                           class="form-control" 
                                           placeholder="Enter your password"
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="text-primary">
                                    Forgot Password?
                                </a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                            
                            <div class="text-center">
                                <p class="mb-0">Don't have an account? 
                                    <a href="<?php echo SITE_URL; ?>/signup.php" class="text-primary fw-bold">
                                        Sign Up
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
