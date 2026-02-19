<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isSpeakerLoggedIn()) {
    redirect(SITE_URL . '/speaker-dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check speaker credentials
        $speaker = fetchOne("SELECT * FROM speakers WHERE email = ? AND status = 'active'", [$email], 's');
        
        if ($speaker && password_verify($password, $speaker['password'])) {
            $_SESSION['speaker_id'] = $speaker['id'];
            
            // Redirect to dashboard or requested page
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'speaker-dashboard.php';
            redirect(SITE_URL . '/' . $redirect);
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$pageTitle = 'Speaker Login';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Speaker Login
                    </h3>
                </div>
                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login to Dashboard
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="<?php echo SITE_URL; ?>/speaker-register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Register as Speaker
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Are you an event organizer?</p>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Organizer Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
