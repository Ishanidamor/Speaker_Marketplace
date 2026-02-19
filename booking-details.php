<?php
require_once 'config/config.php';
requireLogin();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = getCurrentUser();

if (!$bookingId) {
    setFlashMessage('danger', 'Invalid booking ID.');
    redirect(SITE_URL . '/bookings.php');
}

// Get booking details
$booking = fetchOne(
    "SELECT * FROM bookings WHERE id = ? AND organizer_id = ?",
    [$bookingId, $currentUser['id']], 'ii'
);

if (!$booking) {
    setFlashMessage('danger', 'Booking not found or access denied.');
    redirect(SITE_URL . '/bookings.php');
}

// Get booking items (speakers)
$bookingItems = fetchAll(
    "SELECT bi.*, s.name as speaker_name, s.email as speaker_email, s.title as speaker_title, 
            s.bio as speaker_bio, s.location as speaker_location, s.linkedin_url, s.twitter_url, s.website_url
     FROM booking_items bi 
     JOIN speakers s ON bi.speaker_id = s.id 
     WHERE bi.booking_id = ?",
    [$booking['id']], 'i'
);

// Calculate status summary
$allAccepted = true;
$anyAccepted = false;
$anyRejected = false;
$pendingCount = 0;

foreach ($bookingItems as $item) {
    if ($item['speaker_status'] === 'pending') {
        $allAccepted = false;
        $pendingCount++;
    } elseif ($item['speaker_status'] === 'accepted') {
        $anyAccepted = true;
    } elseif ($item['speaker_status'] === 'rejected') {
        $anyRejected = true;
    }
}

$pageTitle = 'Booking Details - ' . $booking['event_name'];
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1"><?php echo htmlspecialchars($booking['event_name']); ?></h2>
                    <p class="text-muted mb-0">Booking #<?php echo htmlspecialchars($booking['booking_number']); ?></p>
                </div>
                <div class="text-end">
                    <?php
                    $statusClass = [
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info'
                    ];
                    $paymentClass = [
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info'
                    ];
                    ?>
                    <span class="badge bg-<?php echo $statusClass[$booking['booking_status']] ?? 'secondary'; ?> me-2">
                        <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                    <span class="badge bg-<?php echo $paymentClass[$booking['payment_status']] ?? 'secondary'; ?>">
                        Payment: <?php echo ucfirst($booking['payment_status']); ?>
                    </span>
                </div>
            </div>

            <!-- Event Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Event Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Event Name</label>
                                <p class="mb-0"><?php echo htmlspecialchars($booking['event_name']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Date & Time</label>
                                <p class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('F j, Y', strtotime($booking['event_date'])); ?>
                                    <br>
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Duration</label>
                                <p class="mb-0"><?php echo htmlspecialchars($booking['event_duration']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Event Type</label>
                                <p class="mb-0">
                                    <span class="badge bg-info"><?php echo ucfirst($booking['event_type']); ?></span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Location</label>
                                <p class="mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($booking['event_location']); ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Expected Attendees</label>
                                <p class="mb-0">
                                    <i class="fas fa-users me-2"></i>
                                    <?php echo number_format($booking['expected_attendees']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($booking['event_description']): ?>
                        <div class="mt-3">
                            <label class="fw-bold text-muted">Event Description</label>
                            <div class="bg-light p-3 rounded">
                                <?php echo nl2br(htmlspecialchars($booking['event_description'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Speakers -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>Speakers (<?php echo count($bookingItems); ?>)
                    </h5>
                    <div>
                        <?php if ($pendingCount > 0): ?>
                            <span class="badge bg-warning"><?php echo $pendingCount; ?> Pending</span>
                        <?php endif; ?>
                        <?php if ($anyAccepted): ?>
                            <span class="badge bg-light text-success">Some Accepted</span>
                        <?php endif; ?>
                        <?php if ($anyRejected): ?>
                            <span class="badge bg-light text-danger">Some Rejected</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($bookingItems as $index => $item): ?>
                        <div class="p-4 <?php echo $index < count($bookingItems) - 1 ? 'border-bottom' : ''; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-user fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['speaker_name']); ?></h6>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($item['speaker_title']); ?></p>
                                            
                                            <?php if ($item['speaker_bio']): ?>
                                                <p class="small mb-2"><?php echo htmlspecialchars(substr($item['speaker_bio'], 0, 150)) . '...'; ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="badge bg-info"><?php echo ucfirst($item['format']); ?></span>
                                                <?php if ($item['speaker_location']): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($item['speaker_location']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Speaker Links -->
                                            <div class="d-flex gap-2">
                                                <?php if ($item['linkedin_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($item['linkedin_url']); ?>" target="_blank" class="text-primary">
                                                        <i class="fab fa-linkedin"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($item['twitter_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($item['twitter_url']); ?>" target="_blank" class="text-info">
                                                        <i class="fab fa-twitter"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($item['website_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($item['website_url']); ?>" target="_blank" class="text-secondary">
                                                        <i class="fas fa-globe"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="mailto:<?php echo htmlspecialchars($item['speaker_email']); ?>" class="text-success">
                                                    <i class="fas fa-envelope"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <h5 class="text-primary mb-2"><?php echo formatPrice($item['rate']); ?></h5>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'accepted' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusIcon = [
                                        'pending' => 'clock',
                                        'accepted' => 'check-circle',
                                        'rejected' => 'times-circle'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$item['speaker_status']]; ?> mb-2">
                                        <i class="fas fa-<?php echo $statusIcon[$item['speaker_status']]; ?> me-1"></i>
                                        <?php echo ucfirst($item['speaker_status']); ?>
                                    </span>
                                    
                                    <?php if ($item['speaker_notes']): ?>
                                        <br>
                                        <button class="btn btn-sm btn-outline-info" 
                                                data-mdb-toggle="modal" 
                                                data-mdb-target="#notesModal<?php echo $item['id']; ?>">
                                            <i class="fas fa-comment"></i> Notes
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Speaker Notes Modal -->
                            <?php if ($item['speaker_notes']): ?>
                                <div class="modal fade" id="notesModal<?php echo $item['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Notes from <?php echo htmlspecialchars($item['speaker_name']); ?></h5>
                                                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><?php echo nl2br(htmlspecialchars($item['speaker_notes'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>Financial Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?php echo formatPrice($booking['total_amount']); ?></span>
                            </div>
                            <?php if ($booking['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Discount:</span>
                                    <span>-<?php echo formatPrice($booking['discount_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span><?php echo formatPrice($booking['tax_amount']); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold">Total Amount:</h6>
                                <h6 class="text-success fw-bold"><?php echo formatPrice($booking['final_amount']); ?></h6>
                            </div>
                            
                            <?php if ($booking['payment_method']): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <small class="text-muted">
                                        <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?>
                                        <?php if ($booking['transaction_id']): ?>
                                            <br><strong>Transaction ID:</strong> <?php echo htmlspecialchars($booking['transaction_id']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Booking created on <?php echo date('F j, Y \a\t g:i A', strtotime($booking['created_at'])); ?>
                        </small>
                        <div class="d-flex gap-2">
                            <?php if ($booking['payment_status'] === 'pending' && $allAccepted && !$anyRejected): ?>
                                <a href="<?php echo SITE_URL; ?>/payment.php?booking=<?php echo $booking['booking_number']; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-credit-card me-1"></i> Proceed to Payment
                                </a>
                            <?php elseif ($booking['payment_status'] === 'pending' && $anyAccepted && !$allAccepted): ?>
                                <span class="badge bg-info">
                                    <i class="fas fa-clock me-1"></i> Waiting for all speakers
                                </span>
                            <?php elseif ($booking['payment_status'] === 'pending' && !$anyAccepted && !$anyRejected): ?>
                                <span class="badge bg-warning">
                                    <i class="fas fa-hourglass-half me-1"></i> Awaiting speaker response
                                </span>
                            <?php elseif ($anyRejected): ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i> Some speakers declined
                                </span>
                            <?php endif; ?>
                            
                            <a href="<?php echo SITE_URL; ?>/bookings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Bookings
                            </a>
                            
                            <a href="<?php echo SITE_URL; ?>/contact.php?booking=<?php echo $booking['booking_number']; ?>" 
                               class="btn btn-outline-info">
                                <i class="fas fa-headset me-1"></i> Support
                            </a>
                            
                            <button onclick="window.print()" class="btn btn-outline-primary">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .modal, .navbar, .footer {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
