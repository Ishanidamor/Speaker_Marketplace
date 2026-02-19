<?php
$pageTitle = 'Contact Us';
require_once 'config/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        $stmt = executeQuery("INSERT INTO support_tickets (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)",
                            [$userId, $name, $email, $subject, $message], 'issss');
        
        if ($stmt) {
            setFlashMessage('success', 'Your message has been sent successfully! We will get back to you soon.');
            redirect(SITE_URL . '/contact.php');
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

$currentUser = getCurrentUser();

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">
                <i class="fas fa-envelope me-2"></i> Contact Us
            </h1>
            <p class="text-muted">Have a question? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
        
        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Send us a Message</h4>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Your Name</label>
                                    <input type="text" 
                                           name="name" 
                                           class="form-control" 
                                           value="<?php echo $currentUser ? htmlspecialchars($currentUser['name']) : ''; ?>"
                                           required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <input type="email" 
                                           name="email" 
                                           class="form-control" 
                                           value="<?php echo $currentUser ? htmlspecialchars($currentUser['email']) : ''; ?>"
                                           required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-bold">Subject</label>
                                    <input type="text" 
                                           name="subject" 
                                           class="form-control" 
                                           placeholder="How can we help you?"
                                           required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label fw-bold">Message</label>
                                    <textarea name="message" 
                                              class="form-control" 
                                              rows="6" 
                                              placeholder="Tell us more about your inquiry..."
                                              required></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-paper-plane me-2"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="fas fa-info-circle me-2"></i> Contact Information
                        </h5>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-envelope fa-lg text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Email</h6>
                                    <p class="mb-0 text-muted"><?php echo getSetting('site_email', 'info@speakermarket.com'); ?></p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-phone fa-lg text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Phone</h6>
                                    <p class="mb-0 text-muted">+1 (555) 123-4567</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-map-marker-alt fa-lg text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Address</h6>
                                    <p class="mb-0 text-muted">123 Audio Street<br>Music City, MC 12345</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start">
                                <i class="fas fa-clock fa-lg text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Business Hours</h6>
                                    <p class="mb-0 text-muted">Mon - Fri: 9:00 AM - 6:00 PM<br>Sat - Sun: Closed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h5 class="fw-bold mb-3">Follow Us</h5>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="#" class="btn btn-outline-primary btn-lg">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info btn-lg">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger btn-lg">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="btn btn-outline-dark btn-lg">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
