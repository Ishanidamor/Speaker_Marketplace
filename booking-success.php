<?php
require_once 'config/config.php';
requireLogin();

$bookingNumber = isset($_GET['booking']) ? sanitize($_GET['booking']) : '';
$currentUser = getCurrentUser();

if (empty($bookingNumber)) {
    redirect(SITE_URL . '/bookings.php');
}

// Get booking details
$booking = fetchOne(
    "SELECT * FROM bookings WHERE booking_number = ? AND organizer_id = ?",
    [$bookingNumber, $currentUser['id']], 'si'
);

if (!$booking) {
    setFlashMessage('danger', 'Booking not found.');
    redirect(SITE_URL . '/bookings.php');
}

// Get booking items (speakers)
$bookingItems = fetchAll(
    "SELECT bi.*, s.name as speaker_name, s.email as speaker_email, s.title as speaker_title 
     FROM booking_items bi 
     JOIN speakers s ON bi.speaker_id = s.id 
     WHERE bi.booking_id = ?",
    [$booking['id']], 'i'
);

$pageTitle = 'Booking Confirmed';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Success Message -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-success text-white text-center py-4">
                    <i class="fas fa-check-circle fa-4x mb-3"></i>
                    <h2 class="mb-0">Booking Confirmed!</h2>
                    <p class="mb-0 mt-2">Your payment has been processed successfully</p>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4>Booking Number: <span class="text-primary"><?php echo htmlspecialchars($booking['booking_number']); ?></span></h4>
                        <p class="text-muted">Transaction ID: <?php echo htmlspecialchars($booking['transaction_id']); ?></p>
                    </div>
                    
                    <!-- What's Next -->
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle me-2"></i>What happens next?
                        </h6>
                        <ul class="mb-0">
                            <li>You will receive a confirmation email shortly</li>
                            <li>Speakers will be notified about the confirmed booking</li>
                            <li>You can contact speakers directly for event coordination</li>
                            <li>Event details and speaker contact information are available in your bookings</li>
                        </ul>
                    </div>
                    
                    <!-- Event Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>Event Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Event:</strong> <?php echo htmlspecialchars($booking['event_name']); ?></p>
                                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['event_date'])); ?></p>
                                    <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['event_time'])); ?></p>
                                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($booking['event_duration']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Type:</strong> <?php echo ucfirst($booking['event_type']); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['event_location']); ?></p>
                                    <p><strong>Attendees:</strong> <?php echo number_format($booking['expected_attendees']); ?></p>
                                    <p><strong>Amount Paid:</strong> <span class="text-success fw-bold"><?php echo formatPrice($booking['final_amount']); ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirmed Speakers -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-microphone-alt me-2"></i>Confirmed Speakers
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($bookingItems as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['speaker_name']); ?></h6>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($item['speaker_title']); ?></p>
                                        <span class="badge bg-info"><?php echo ucfirst($item['format']); ?></span>
                                    </div>
                                    <div class="text-end">
                                        <h6 class="mb-1"><?php echo formatPrice($item['rate']); ?></h6>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Confirmed
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo SITE_URL; ?>/bookings.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>View All Bookings
                        </a>
                        <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Book More Speakers
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="fas fa-print me-2"></i>Print Confirmation
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Support Information -->
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="fw-bold mb-2">Need Help?</h6>
                    <p class="text-muted mb-3">Our support team is here to help with any questions about your booking.</p>
                    <a href="<?php echo SITE_URL; ?>/contact.php?booking=<?php echo $booking['booking_number']; ?>" 
                       class="btn btn-outline-info">
                        <i class="fas fa-headset me-2"></i>Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
