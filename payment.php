<?php
require_once 'config/config.php';
requireLogin();

$bookingNumber = isset($_GET['booking']) ? sanitize($_GET['booking']) : '';
$currentUser = getCurrentUser();

if (empty($bookingNumber)) {
    setFlashMessage('danger', 'Invalid booking reference.');
    redirect(SITE_URL . '/bookings.php');
}

// Get booking details
$booking = fetchOne(
    "SELECT * FROM bookings WHERE booking_number = ? AND organizer_id = ?",
    [$bookingNumber, $currentUser['id']], 'si'
);

if (!$booking) {
    setFlashMessage('danger', 'Booking not found or access denied.');
    redirect(SITE_URL . '/bookings.php');
}

// Check if payment is already completed
if ($booking['payment_status'] === 'completed') {
    setFlashMessage('info', 'This booking has already been paid for.');
    redirect(SITE_URL . '/bookings.php');
}

// Get booking items (speakers) and check if all are accepted
$bookingItems = fetchAll(
    "SELECT bi.*, s.name as speaker_name, s.email as speaker_email, s.title as speaker_title 
     FROM booking_items bi 
     JOIN speakers s ON bi.speaker_id = s.id 
     WHERE bi.booking_id = ?",
    [$booking['id']], 'i'
);

// Validate that all speakers have accepted
$allAccepted = true;
$anyRejected = false;
foreach ($bookingItems as $item) {
    if ($item['speaker_status'] === 'pending') {
        $allAccepted = false;
    } elseif ($item['speaker_status'] === 'rejected') {
        $anyRejected = true;
    }
}

if (!$allAccepted || $anyRejected) {
    setFlashMessage('warning', 'Payment is not available until all speakers accept your booking request.');
    redirect(SITE_URL . '/bookings.php');
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = sanitize($_POST['payment_method']);
    $cardNumber = sanitize($_POST['card_number']);
    $expiryDate = sanitize($_POST['expiry_date']);
    $cvv = sanitize($_POST['cvv']);
    $cardName = sanitize($_POST['card_name']);
    
    // Basic validation
    if (empty($paymentMethod) || empty($cardNumber) || empty($expiryDate) || empty($cvv) || empty($cardName)) {
        $error = 'Please fill in all payment details.';
    } elseif (strlen($cardNumber) < 16) {
        $error = 'Please enter a valid card number.';
    } elseif (strlen($cvv) < 3) {
        $error = 'Please enter a valid CVV.';
    } else {
        try {
            // Simulate payment processing (in real app, integrate with payment gateway)
            $transactionId = 'TXN-' . date('Ymd') . '-' . rand(100000, 999999);
            
            // Update booking status
            executeQuery(
                "UPDATE bookings SET payment_status = 'completed', booking_status = 'confirmed', payment_method = ?, transaction_id = ? WHERE id = ?",
                [$paymentMethod, $transactionId, $booking['id']],
                'ssi'
            );
            
            // Create success notification for user
            executeQuery(
                "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)",
                [$currentUser['id'], 'payment_success', 'Payment Successful', 'Your payment for booking ' . $booking['booking_number'] . ' has been processed successfully. Your event is now confirmed!'],
                'isss'
            );
            
            // Notify speakers that booking is confirmed
            foreach ($bookingItems as $item) {
                $speaker = fetchOne("SELECT * FROM speakers WHERE id = ?", [$item['speaker_id']], 'i');
                if ($speaker) {
                    // You could send email notifications here
                    // For now, we'll just create a notification if speakers had a notification system
                }
            }
            
            setFlashMessage('success', 'Payment successful! Your booking is now confirmed.');
            redirect(SITE_URL . '/booking-success.php?booking=' . $booking['booking_number']);
            
        } catch (Exception $e) {
            $error = 'Payment processing failed. Please try again.';
        }
    }
}

$pageTitle = 'Payment - ' . $booking['event_name'];
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Booking Summary -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>Payment for Booking
                    </h4>
                    <p class="mb-0 mt-2">Booking #<?php echo htmlspecialchars($booking['booking_number']); ?></p>
                </div>
                <div class="card-body">
                    <!-- Event Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary">Event Details</h6>
                            <p class="mb-1"><strong>Event:</strong> <?php echo htmlspecialchars($booking['event_name']); ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['event_date'])); ?></p>
                            <p class="mb-1"><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['event_time'])); ?></p>
                            <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($booking['event_location']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary">Confirmed Speakers</h6>
                            <?php foreach ($bookingItems as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['speaker_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo ucfirst($item['format']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold"><?php echo formatPrice($item['rate']); ?></span><br>
                                        <span class="badge bg-success">Accepted</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Summary -->
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Payment Summary</h6>
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
                                <h5 class="fw-bold">Total Amount:</h5>
                                <h5 class="text-success fw-bold"><?php echo formatPrice($booking['final_amount']); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Secure Payment
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Payment Method</label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                        <label class="form-check-label" for="credit_card">
                                            <i class="fas fa-credit-card me-2"></i>Credit Card
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card">
                                        <label class="form-check-label" for="debit_card">
                                            <i class="fas fa-credit-card me-2"></i>Debit Card
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                        <label class="form-check-label" for="paypal">
                                            <i class="fab fa-paypal me-2"></i>PayPal
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="card_name" class="form-label fw-bold">Cardholder Name</label>
                                <input type="text" class="form-control" id="card_name" name="card_name" 
                                       placeholder="John Doe" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="card_number" class="form-label fw-bold">Card Number</label>
                                <input type="text" class="form-control" id="card_number" name="card_number" 
                                       placeholder="1234 5678 9012 3456" maxlength="19" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cvv" class="form-label fw-bold">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                       placeholder="123" maxlength="4" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label fw-bold">Expiry Date</label>
                                <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                       placeholder="MM/YY" maxlength="5" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Secure Payment:</strong> Your payment information is encrypted and secure. 
                            This is a demo - no real charges will be made.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <a href="<?php echo SITE_URL; ?>/bookings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Bookings
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-lock me-2"></i>Pay <?php echo formatPrice($booking['final_amount']); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format card number input
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
});

// Format expiry date input
document.getElementById('expiry_date').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// Only allow numbers for CVV
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});
</script>

<?php require_once 'includes/footer.php'; ?>
