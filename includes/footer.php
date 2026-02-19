    <!-- Footer -->
    <footer class="mt-5 py-5" style="background-color: var(--secondary-color); color: white;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-microphone-alt"></i> Speaker Marketplace
                    </h5>
                    <p class="text-light">Connect with world-class speakers for your next event. Virtual or in-person, find the perfect speaker for any occasion.</p>
                    <div class="mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>" class="text-light text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/speakers.php" class="text-light text-decoration-none">Find Speakers</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/faq.php" class="text-light text-decoration-none">FAQ</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/contact.php" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">For Organizers</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/bookings.php" class="text-light text-decoration-none">My Bookings</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/profile.php" class="text-light text-decoration-none">My Account</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/faq.php" class="text-light text-decoration-none">Help Center</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/contact.php" class="text-light text-decoration-none">Support</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Contact Info</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo getSetting('site_email', 'info@speakermarket.com'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            +1 (555) 123-4567
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            123 Audio Street, Music City
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Speaker Marketplace. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-light text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-light text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-light text-decoration-none">Refund Policy</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertType = '<?php echo $flash['type']; ?>';
            const alertMessage = '<?php echo addslashes($flash['message']); ?>';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${alertType} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${alertMessage}
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php
// Flush output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>
