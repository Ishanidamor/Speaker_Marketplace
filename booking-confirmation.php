<?php
require_once 'config/config.php';
requireLogin();

$bookingNumber = isset($_GET['booking']) ? sanitize($_GET['booking']) : '';

if (empty($bookingNumber)) {
    redirect(SITE_URL . '/bookings.php');
}

// Get booking details
$booking = fetchOne(
    "SELECT * FROM bookings WHERE booking_number = ? AND organizer_id = ?",
    [$bookingNumber, $_SESSION['user_id']], 'si'
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

$pageTitle = 'Booking Request Submitted';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Success Card -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-success text-white text-center py-4">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h3 class="mb-0">Booking Request Submitted!</h3>
                    <p class="mb-0 mt-2">Your request has been sent to the speakers</p>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h5>Booking Number: <span class="text-primary"><?php echo htmlspecialchars($booking['booking_number']); ?></span></h5>
                        <p class="text-muted">Please save this number for your records</p>
                    </div>
                    
                    <!-- What Happens Next -->
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle me-2"></i>What happens next?
                        </h6>
                        <ol class="mb-0">
                            <li><strong>Speaker Review</strong> - Speakers will review your request (usually within 24-48 hours)</li>
                            <li><strong>Notification</strong> - You'll receive notifications when speakers accept or decline</li>
                            <li><strong>Payment</strong> - Once speakers accept, you can proceed with payment</li>
                            <li><strong>Confirmation</strong> - Your event will be confirmed after payment</li>
                        </ol>
                    </div>
                    
                    <!-- Event Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Event Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Event Name:</strong> <?php echo htmlspecialchars($booking['event_name']); ?></p>
                                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['event_date'])); ?></p>
                                    <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['event_time'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Type:</strong> <?php echo ucfirst($booking['event_type']); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['event_location']); ?></p>
                                    <p><strong>Expected Attendees:</strong> <?php echo number_format($booking['expected_attendees']); ?></p>
                                </div>
                            </div>
                            <?php if ($booking['event_description']): ?>
                                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($booking['event_description'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Requested Speakers -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-microphone-alt me-2"></i>Requested Speakers
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
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Pending Review
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <h5 class="mb-0">Total Amount:</h5>
                                <h5 class="text-primary mb-0"><?php echo formatPrice($booking['final_amount']); ?></h5>
                            </div>
                            <small class="text-muted">*Payment will be processed after speaker acceptance</small>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo SITE_URL; ?>/bookings.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>View All Bookings
                        </a>
                        <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Browse More Speakers
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Status Tracking -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Request Status Tracking
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                                <h6 class="mt-2">Request Sent</h6>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                                <h6 class="mt-2">Speaker Review</h6>
                                <small class="text-muted">In Progress</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-credit-card fa-2x text-muted"></i>
                                <h6 class="mt-2">Payment</h6>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-calendar-check fa-2x text-muted"></i>
                                <h6 class="mt-2">Confirmed</h6>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
