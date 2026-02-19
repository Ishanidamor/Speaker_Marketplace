<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isSpeakerLoggedIn()) {
    redirect(SITE_URL . '/speaker-dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $title = sanitize($_POST['title']);
    $bio = sanitize($_POST['bio']);
    $location = sanitize($_POST['location']);
    $keynoteRate = (float)$_POST['keynote_rate'];
    $workshopRate = (float)$_POST['workshop_rate'];
    $virtualRate = (float)$_POST['virtual_rate'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($title)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email already exists
        $existingSpeaker = fetchOne("SELECT id FROM speakers WHERE email = ?", [$email], 's');
        if ($existingSpeaker) {
            $error = 'Email already registered';
        } else {
            // Create speaker account
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $slug = generateSlug($name);
            
            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (fetchOne("SELECT id FROM speakers WHERE slug = ?", [$slug], 's')) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            try {
                $stmt = executeQuery(
                    "INSERT INTO speakers (name, email, password, title, bio, slug, keynote_rate, workshop_rate, virtual_rate, location, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                    [$name, $email, $hashedPassword, $title, $bio, $slug, $keynoteRate, $workshopRate, $virtualRate, $location],
                    'ssssssddds'
                );
                
                $success = 'Registration successful! Your account is pending approval. You will receive an email once approved.';
            } catch (Exception $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Speaker Registration';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Join as a Speaker
                    </h3>
                    <p class="mb-0 mt-2">Share your expertise with event organizers worldwide</p>
                </div>
                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php else: ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="name" class="form-label fw-bold">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       placeholder="Your full name" required>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label fw-bold">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       placeholder="your@email.com" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">Professional Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                   placeholder="e.g., AI Expert, Leadership Coach, Marketing Strategist" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="bio" class="form-label fw-bold">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                      placeholder="Tell us about your expertise and experience..."><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="location" class="form-label fw-bold">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                   placeholder="City, Country">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="password" class="form-label fw-bold">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="At least 6 characters" required>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="confirm_password" class="form-label fw-bold">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Repeat password" required>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold mb-3 text-primary">Speaking Rates (USD)</h5>
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label for="keynote_rate" class="form-label fw-bold">Keynote Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="keynote_rate" name="keynote_rate" 
                                           value="<?php echo isset($_POST['keynote_rate']) ? $_POST['keynote_rate'] : ''; ?>" 
                                           placeholder="5000" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <label for="workshop_rate" class="form-label fw-bold">Workshop Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="workshop_rate" name="workshop_rate" 
                                           value="<?php echo isset($_POST['workshop_rate']) ? $_POST['workshop_rate'] : ''; ?>" 
                                           placeholder="8000" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <label for="virtual_rate" class="form-label fw-bold">Virtual Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="virtual_rate" name="virtual_rate" 
                                           value="<?php echo isset($_POST['virtual_rate']) ? $_POST['virtual_rate'] : ''; ?>" 
                                           placeholder="3000" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                Register as Speaker
                            </button>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <p class="mb-2">Already have an account?</p>
                        <a href="<?php echo SITE_URL; ?>/speaker-login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login to Dashboard
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Are you an event organizer?</p>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-outline-secondary">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Organizer Registration
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
