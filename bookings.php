<?php
$pageTitle = 'My Bookings';
require_once 'config/config.php';

requireLogin();

// Get user bookings
$bookings = fetchAll("SELECT * FROM bookings WHERE organizer_id = ? ORDER BY created_at DESC", 
                    [$_SESSION['user_id']], 'i');

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <h1 class="fw-bold mb-4">
            <i class="fas fa-calendar-check me-2"></i> My Bookings
        </h1>
        
        <?php if (count($bookings) > 0): ?>
            <div class="row g-4">
                <?php foreach ($bookings as $booking): 
                    // Get booking items (speakers)
                    $speakers = fetchAll("SELECT * FROM booking_items WHERE booking_id = ?", [$booking['id']], 'i');
                    
                    // Status badge colors
                    $statusColors = [
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info'
                    ];
                    $paymentColors = [
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info'
                    ];
                ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <small class="text-muted">Booking Number</small>
                                        <h6 class="mb-0 fw-bold"><?php echo $booking['booking_number']; ?></h6>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Event Name</small>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($booking['event_name']); ?></h6>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">Event Date</small>
                                        <h6 class="mb-0"><?php echo date('M j, Y', strtotime($booking['event_date'])); ?></h6>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">Status</small>
                                        <h6 class="mb-0">
                                            <span class="badge bg-<?php echo $statusColors[$booking['booking_status']]; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </h6>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">Total</small>
                                        <h6 class="mb-0 text-primary fw-bold"><?php echo formatPrice($booking['final_amount']); ?></h6>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <!-- Event Details -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-info-circle text-primary me-2"></i> Event Details
                                        </h6>
                                        <p class="mb-1">
                                            <i class="fas fa-calendar me-2"></i>
                                            <strong>Date & Time:</strong> 
                                            <?php echo date('F j, Y', strtotime($booking['event_date'])); ?> 
                                            at <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-clock me-2"></i>
                                            <strong>Duration:</strong> <?php echo $booking['event_duration']; ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <strong>Location:</strong> <?php echo htmlspecialchars($booking['event_location']); ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-users me-2"></i>
                                            <strong>Attendees:</strong> <?php echo number_format($booking['expected_attendees']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="fas fa-video me-2"></i>
                                            <strong>Type:</strong> <?php echo ucfirst($booking['event_type']); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-credit-card text-primary me-2"></i> Payment Information
                                        </h6>
                                        <p class="mb-1">
                                            <strong>Payment Status:</strong>
                                            <span class="badge bg-<?php echo $paymentColors[$booking['payment_status']]; ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        </p>
                                        <p class="mb-1">
                                            <strong>Method:</strong> <?php echo ucfirst($booking['payment_method']); ?>
                                        </p>
                                        <?php if ($booking['transaction_id']): ?>
                                            <p class="mb-1">
                                                <strong>Transaction ID:</strong> <?php echo $booking['transaction_id']; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Speakers -->
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-microphone-alt text-primary me-2"></i> Booked Speakers
                                </h6>
                                <?php foreach ($speakers as $speaker): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($speaker['speaker_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo ucfirst($speaker['format']); ?> Session
                                                <?php if ($speaker['session_title']): ?>
                                                    - "<?php echo htmlspecialchars($speaker['session_title']); ?>"
                                                <?php endif; ?>
                                            </small>
                                            <div class="mt-1">
                                                <span class="badge bg-<?php 
                                                    echo $speaker['speaker_status'] == 'accepted' ? 'success' : 
                                                        ($speaker['speaker_status'] == 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    Speaker: <?php echo ucfirst($speaker['speaker_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="text-primary mb-2">
                                                <?php echo formatPrice($speaker['rate']); ?>
                                            </h6>
                                            <?php if ($speaker['speaker_notes']): ?>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        data-mdb-toggle="modal" 
                                                        data-mdb-target="#notesModal<?php echo $speaker['id']; ?>">
                                                    <i class="fas fa-comment"></i> Notes
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Speaker Notes Modal -->
                                    <?php if ($speaker['speaker_notes']): ?>
                                        <div class="modal fade" id="notesModal<?php echo $speaker['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Speaker Notes</h5>
                                                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><?php echo nl2br(htmlspecialchars($speaker['speaker_notes'])); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <!-- Financial Summary -->
                                <div class="row mt-4">
                                    <div class="col-md-6 offset-md-6">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Subtotal:</span>
                                            <span><?php echo formatPrice($booking['total_amount']); ?></span>
                                        </div>
                                        <?php if ($booking['discount_amount'] > 0): ?>
                                            <div class="d-flex justify-content-between mb-1 text-success">
                                                <span>Discount:</span>
                                                <span>-<?php echo formatPrice($booking['discount_amount']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Tax:</span>
                                            <span><?php echo formatPrice($booking['tax_amount']); ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <h6 class="fw-bold">Total:</h6>
                                            <h6 class="text-primary fw-bold"><?php echo formatPrice($booking['final_amount']); ?></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Booking created on <?php echo date('F j, Y \a\t g:i A', strtotime($booking['created_at'])); ?>
                                    </small>
                                    <div>
                                        <?php
                                        // Check if all speakers have accepted
                                        $allAccepted = true;
                                        $anyAccepted = false;
                                        $anyRejected = false;
                                        
                                        foreach ($speakers as $speaker) {
                                            if ($speaker['speaker_status'] === 'pending') {
                                                $allAccepted = false;
                                            } elseif ($speaker['speaker_status'] === 'accepted') {
                                                $anyAccepted = true;
                                            } elseif ($speaker['speaker_status'] === 'rejected') {
                                                $anyRejected = true;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if ($booking['payment_status'] === 'pending' && $allAccepted && !$anyRejected): ?>
                                            <a href="<?php echo SITE_URL; ?>/payment.php?booking=<?php echo $booking['booking_number']; ?>" 
                                               class="btn btn-sm btn-success me-2">
                                                <i class="fas fa-credit-card me-1"></i> Proceed to Payment
                                            </a>
                                        <?php elseif ($booking['payment_status'] === 'pending' && $anyAccepted && !$allAccepted): ?>
                                            <span class="badge bg-info me-2">
                                                <i class="fas fa-clock me-1"></i> Waiting for all speakers
                                            </span>
                                        <?php elseif ($booking['payment_status'] === 'pending' && !$anyAccepted && !$anyRejected): ?>
                                            <span class="badge bg-warning me-2">
                                                <i class="fas fa-hourglass-half me-1"></i> Awaiting speaker response
                                            </span>
                                        <?php elseif ($anyRejected): ?>
                                            <span class="badge bg-danger me-2">
                                                <i class="fas fa-times me-1"></i> Some speakers declined
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['payment_status'] === 'completed' && $booking['booking_status'] === 'completed'): ?>
                                            <button class="btn btn-sm btn-outline-warning me-2" 
                                                    onclick="leaveReview(<?php echo $booking['id']; ?>)">
                                                <i class="fas fa-star me-1"></i> Leave Review
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo SITE_URL; ?>/contact.php?booking=<?php echo $booking['booking_number']; ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-headset me-1"></i> Support
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-5x text-muted mb-4"></i>
                <h2 class="fw-bold mb-3">No Bookings Yet</h2>
                <p class="text-muted mb-4">You haven't made any speaker bookings yet</p>
                <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i> Find Speakers
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function leaveReview(bookingId) {
    // Redirect to review page (to be implemented)
    alert('Review feature coming soon! Booking ID: ' + bookingId);
}
</script>

<?php require_once 'includes/footer.php'; ?>
