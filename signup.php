<?php
$pageTitle = 'Sign Up';
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/profile.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$email], 's');
        
        if ($existing) {
            $error = 'Email already registered';
        } else {
            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = executeQuery("INSERT INTO users (name, email, password) VALUES (?, ?, ?)", 
                                [$name, $email, $hashedPassword], 'sss');
            
            if ($stmt) {
                $userId = $stmt->insert_id;
                
                // Auto login
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                
                // Transfer cart items
                $sessionId = session_id();
                executeQuery("UPDATE booking_cart SET user_id = ?, session_id = NULL WHERE session_id = ?", 
                            [$userId, $sessionId], 'is');
                
                setFlashMessage('success', 'Account created successfully! Welcome, ' . $name . '!');
                redirect(SITE_URL . '/profile.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-4x text-primary mb-3"></i>
                            <h2 class="fw-bold">Create Account</h2>
                            <p class="text-muted">Join our community today</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           name="name" 
                                           class="form-control" 
                                           placeholder="Enter your full name"
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                           required>
                                </div>
                            </div>
                            
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
                                           placeholder="Create a password"
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
                                           placeholder="Confirm your password"
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" class="text-primary">Terms & Conditions</a>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                                <i class="fas fa-user-plus me-2"></i> Create Account
                            </button>
                            
                            <div class="text-center">
                                <p class="mb-0">Already have an account? 
                                    <a href="<?php echo SITE_URL; ?>/login.php" class="text-primary fw-bold">
                                        Login
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
