<?php
$pageTitle = 'Booking Checkout';
require_once 'config/config.php';

requireLogin();

$cartItems = getCartItems();

if (count($cartItems) === 0) {
    redirect(SITE_URL . '/cart.php');
}

$currentUser = getCurrentUser();
$subtotal = getCartTotal();
$taxRate = (float)getSetting('tax_rate', '10');
$taxAmount = $subtotal * ($taxRate / 100);
$finalAmount = $subtotal + $taxAmount;

// Handle booking submission
if (isset($_POST['submit_booking'])) {
    $eventName = sanitize($_POST['event_name']);
    $eventDate = sanitize($_POST['event_date']);
    $eventTime = sanitize($_POST['event_time']);
    $eventDuration = sanitize($_POST['event_duration']);
    $eventType = sanitize($_POST['event_type']);
    $eventLocation = sanitize($_POST['event_location']);
    $expectedAttendees = (int)$_POST['expected_attendees'];
    $eventDescription = sanitize($_POST['event_description']);
    $paymentMethod = sanitize($_POST['payment_method']);
    
    // Generate booking number
    $bookingNumber = 'BK-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    
    // Create booking
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (organizer_id, booking_number, event_name, event_date, event_time, event_duration, event_type, event_location, expected_attendees, event_description, total_amount, tax_amount, final_amount, payment_method, booking_status, payment_status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
        $stmt->bind_param('isssssssisddds', 
            $_SESSION['user_id'], $bookingNumber, $eventName, $eventDate, $eventTime, 
            $eventDuration, $eventType, $eventLocation, $expectedAttendees, $eventDescription,
            $subtotal, $taxAmount, $finalAmount, $paymentMethod);
        $stmt->execute();
        $bookingId = $stmt->insert_id;
        
        // Insert booking items (speakers)
        foreach ($cartItems as $item) {
            // Calculate rate
            $rate = 0;
            switch($item['format']) {
                case 'keynote': $rate = $item['keynote_rate']; break;
                case 'workshop': $rate = $item['workshop_rate']; break;
                case 'virtual': $rate = $item['virtual_rate']; break;
                case 'panel': $rate = $item['panel_rate'] ?? $item['keynote_rate']; break;
                default: $rate = $item['keynote_rate'];
            }
            
            $stmt = $conn->prepare("INSERT INTO booking_items (booking_id, speaker_id, speaker_name, format, rate, speaker_status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('iissd', $bookingId, $item['speaker_id'], $item['speaker_name'], $item['format'], $rate);
            $stmt->execute();
        }
        
        // Clear cart
        $stmt = $conn->prepare("DELETE FROM booking_cart WHERE user_id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        
        $conn->commit();
        
        // Redirect to booking confirmation (not payment yet)
        $_SESSION['booking_id'] = $bookingId;
        setFlashMessage('success', 'Booking request submitted successfully! Speakers will review your request and you will be notified once they respond.');
        redirect(SITE_URL . '/booking-confirmation.php?booking=' . $bookingNumber);
        
    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('danger', 'Booking submission failed. Please try again.');
    }
}

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <h1 class="fw-bold mb-4">
            <i class="fas fa-calendar-check me-2"></i> Complete Your Booking
        </h1>
        
        <div class="row g-4">
            <!-- Booking Form -->
            <div class="col-lg-8">
                <form method="POST" action="" id="bookingForm">
                    <!-- Event Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i> Event Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Event Name *</label>
                                    <input type="text" name="event_name" class="form-control" 
                                           placeholder="e.g., Annual Tech Conference 2024" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Event Date *</label>
                                    <input type="date" name="event_date" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Event Time *</label>
                                    <input type="time" name="event_time" class="form-control" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Duration *</label>
                                    <select name="event_duration" class="form-select" required>
                                        <option value="">Select duration</option>
                                        <option value="30 minutes">30 minutes</option>
                                        <option value="1 hour">1 hour</option>
                                        <option value="2 hours">2 hours</option>
                                        <option value="3 hours">3 hours</option>
                                        <option value="4 hours">4 hours</option>
                                        <option value="Half day">Half day</option>
                                        <option value="Full day">Full day</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Event Type *</label>
                                    <select name="event_type" class="form-select" required>
                                        <option value="virtual">Virtual/Online</option>
                                        <option value="physical">In-Person</option>
                                        <option value="hybrid">Hybrid (Both)</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Event Location *</label>
                                    <input type="text" name="event_location" class="form-control" 
                                           placeholder="City, State or Virtual Platform" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Expected Attendees *</label>
                                    <input type="number" name="expected_attendees" class="form-control" 
                                           placeholder="e.g., 500" min="1" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Event Description *</label>
                                    <textarea name="event_description" class="form-control" rows="4" 
                                              placeholder="Provide details about your event, audience, and what you expect from the speaker..." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Organizer Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i> Organizer Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['name']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                                </div>
                                <?php if ($currentUser['organization']): ?>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Organization</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['organization']); ?>" readonly>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i> Payment Method
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php $paymentGateway = getSetting('payment_gateway', 'stripe'); ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="stripe" value="stripe" 
                                       <?php echo $paymentGateway === 'stripe' ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="stripe">
                                    <i class="fab fa-cc-stripe fa-2x me-2"></i> Credit/Debit Card (Stripe)
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="razorpay">
                                <label class="form-check-label" for="razorpay">
                                    <i class="fas fa-credit-card fa-2x me-2"></i> Razorpay
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                <label class="form-check-label" for="paypal">
                                    <i class="fab fa-paypal fa-2x me-2"></i> PayPal
                                </label>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Payment will be processed after speaker confirms availability
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selected Speakers -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i> Selected Speakers
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cartItems as $item): 
                                $rate = 0;
                                switch($item['format']) {
                                    case 'keynote': $rate = $item['keynote_rate']; break;
                                    case 'workshop': $rate = $item['workshop_rate']; break;
                                    case 'virtual': $rate = $item['virtual_rate']; break;
                                    case 'panel': $rate = $item['panel_rate'] ?? $item['keynote_rate']; break;
                                    default: $rate = $item['keynote_rate'];
                                }
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['speaker_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo ucfirst($item['format']); ?> Session
                                        </small>
                                    </div>
                                    <h6 class="text-primary mb-0">
                                        <?php echo formatPrice($rate); ?>
                                    </h6>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" class="text-primary">Terms & Conditions</a> and understand that 
                            speakers will review this booking request within 24-48 hours
                        </label>
                    </div>
                    
                    <button type="submit" name="submit_booking" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-paper-plane me-2"></i> Submit Booking Request - <?php echo formatPrice($finalAmount); ?>
                    </button>
                </form>
            </div>
            
            <!-- Booking Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (<?php echo $taxRate; ?>%)</span>
                            <span><?php echo formatPrice($taxAmount); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="fw-bold">Total</h5>
                            <h5 class="text-primary fw-bold"><?php echo formatPrice($finalAmount); ?></h5>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-clock me-2"></i> Next Steps
                            </h6>
                            <small>
                                1. Submit booking request<br>
                                2. Speakers review (24-48h)<br>
                                3. Receive confirmation<br>
                                4. Complete payment<br>
                                5. Event confirmed!
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
