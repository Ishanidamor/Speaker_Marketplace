<?php
$pageTitle = 'Orders';
require_once 'config/config.php';

requireLogin();

// Get user bookings (orders)
$orders = fetchAll("SELECT b.*, 
                   COUNT(bi.id) as speaker_count,
                   GROUP_CONCAT(bi.speaker_name SEPARATOR ', ') as speakers
                   FROM bookings b 
                   LEFT JOIN booking_items bi ON b.id = bi.booking_id
                   WHERE b.organizer_id = ? 
                   GROUP BY b.id 
                   ORDER BY b.created_at DESC", 
                  [$_SESSION['user_id']], 'i');

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">
                <i class="fas fa-shopping-bag me-2"></i> My Orders
            </h1>
            <p class="text-muted">Track and manage your speaker booking orders</p>
        </div>
        
        <?php if (count($orders) > 0): ?>
            <div class="row g-4">
                <?php foreach ($orders as $order): 
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
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <small class="text-muted">Order Number</small>
                                        <h6 class="mb-0 fw-bold text-primary"><?php echo $order['booking_number']; ?></h6>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Event</small>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($order['event_name']); ?></h6>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">Status</small>
                                        <br><span class="badge bg-<?php echo $statusColors[$order['booking_status']]; ?>">
                                            <?php echo ucfirst($order['booking_status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">Payment</small>
                                        <br><span class="badge bg-<?php echo $paymentColors[$order['payment_status']]; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">Total</small>
                                        <h6 class="mb-0 fw-bold text-success">$<?php echo number_format($order['final_amount'], 2); ?></h6>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-users text-primary me-2"></i>Speakers (<?php echo $order['speaker_count']; ?>)
                                        </h6>
                                        <p class="text-muted mb-3"><?php echo htmlspecialchars($order['speakers']); ?></p>
                                        
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <small class="text-muted">Event Date</small>
                                                <p class="mb-0">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo $order['event_date'] ? date('M j, Y', strtotime($order['event_date'])) : 'TBD'; ?>
                                                </p>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted">Event Time</small>
                                                <p class="mb-0">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo $order['event_time'] ? date('g:i A', strtotime($order['event_time'])) : 'TBD'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <small class="text-muted">Event Type</small>
                                                <p class="mb-0">
                                                    <i class="fas fa-<?php echo $order['event_type'] == 'virtual' ? 'video' : ($order['event_type'] == 'physical' ? 'map-marker-alt' : 'globe'); ?> me-1"></i>
                                                    <?php echo ucfirst($order['event_type']); ?>
                                                </p>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted">Attendees</small>
                                                <p class="mb-0">
                                                    <i class="fas fa-user-friends me-1"></i>
                                                    <?php echo $order['expected_attendees'] ? number_format($order['expected_attendees']) : 'N/A'; ?>
                                                </p>
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted">Order Date</small>
                                                <p class="mb-0">
                                                    <i class="fas fa-calendar-plus me-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($order['event_description']): ?>
                                    <hr>
                                    <div>
                                        <small class="text-muted">Event Description</small>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['event_description'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($order['transaction_id']): ?>
                                            <small class="text-muted">Transaction ID: <?php echo $order['transaction_id']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="<?php echo SITE_URL; ?>/booking-details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                        <?php if ($order['booking_status'] == 'pending'): ?>
                                            <button class="btn btn-outline-danger btn-sm ms-2" 
                                                    onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination would go here if needed -->
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="card border-0">
                    <div class="card-body py-5">
                        <i class="fas fa-shopping-bag text-muted mb-3" style="font-size: 4rem;"></i>
                        <h3 class="fw-bold mb-3">No Orders Yet</h3>
                        <p class="text-muted mb-4">You haven't placed any speaker booking orders yet.</p>
                        <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i> Browse Speakers
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        // AJAX call to cancel order
        fetch('<?php echo SITE_URL; ?>/api/cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error cancelling order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the order.');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
