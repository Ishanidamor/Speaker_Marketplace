<?php
$pageTitle = 'Booking Cart';
require_once 'includes/header.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'remove' && isset($_POST['cart_id'])) {
            $cartId = (int)$_POST['cart_id'];
            executeQuery("DELETE FROM booking_cart WHERE id = ?", [$cartId], 'i');
            setFlashMessage('success', 'Speaker removed from booking cart');
            redirect(SITE_URL . '/cart.php');
        } elseif ($_POST['action'] === 'update_format' && isset($_POST['cart_id']) && isset($_POST['format'])) {
            $cartId = (int)$_POST['cart_id'];
            $format = sanitize($_POST['format']);
            executeQuery("UPDATE booking_cart SET format = ? WHERE id = ?", [$format, $cartId], 'si');
            setFlashMessage('success', 'Event format updated');
            redirect(SITE_URL . '/cart.php');
        }
    }
}

$cartItems = getCartItems();
$subtotal = 0;
?>

<div class="main-content">
    <div class="container">
        <h1 class="fw-bold mb-4">
            <i class="fas fa-calendar-check me-2"></i> Booking Cart
        </h1>
        
        <?php if (count($cartItems) > 0): ?>
            <div class="row g-4">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <?php foreach ($cartItems as $item): 
                        // Calculate rate based on format
                        $rate = 0;
                        switch($item['format']) {
                            case 'keynote':
                                $rate = $item['keynote_rate'];
                                break;
                            case 'workshop':
                                $rate = $item['workshop_rate'];
                                break;
                            case 'virtual':
                                $rate = $item['virtual_rate'];
                                break;
                            case 'panel':
                                $rate = $item['panel_rate'] ?? $item['keynote_rate'];
                                break;
                            default:
                                $rate = $item['keynote_rate'];
                        }
                        $subtotal += $rate;
                    ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2 col-3 mb-3 mb-md-0">
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="height: 80px;">
                                            <i class="fas fa-user fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-5 col-9 mb-3 mb-md-0">
                                        <h5 class="mb-1">
                                            <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $item['slug']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($item['speaker_name']); ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($item['location']); ?>
                                        </p>
                                        <span class="badge bg-primary">
                                            <?php echo ucfirst($item['format']); ?> Session
                                        </span>
                                    </div>
                                    
                                    <div class="col-md-3 col-8 mb-3 mb-md-0">
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="update_format">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <label class="form-label small fw-bold">Event Format:</label>
                                            <select name="format" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <?php if ($item['keynote_rate']): ?>
                                                    <option value="keynote" <?php echo $item['format'] == 'keynote' ? 'selected' : ''; ?>>
                                                        Keynote - <?php echo formatPrice($item['keynote_rate']); ?>
                                                    </option>
                                                <?php endif; ?>
                                                <?php if ($item['workshop_rate']): ?>
                                                    <option value="workshop" <?php echo $item['format'] == 'workshop' ? 'selected' : ''; ?>>
                                                        Workshop - <?php echo formatPrice($item['workshop_rate']); ?>
                                                    </option>
                                                <?php endif; ?>
                                                <?php if ($item['virtual_rate']): ?>
                                                    <option value="virtual" <?php echo $item['format'] == 'virtual' ? 'selected' : ''; ?>>
                                                        Virtual - <?php echo formatPrice($item['virtual_rate']); ?>
                                                    </option>
                                                <?php endif; ?>
                                                <?php if ($item['panel_rate']): ?>
                                                    <option value="panel" <?php echo $item['format'] == 'panel' ? 'selected' : ''; ?>>
                                                        Panel - <?php echo formatPrice($item['panel_rate']); ?>
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                        </form>
                                    </div>
                                    
                                    <div class="col-md-2 col-4 text-end">
                                        <h5 class="text-primary fw-bold mb-2">
                                            <?php echo formatPrice($rate); ?>
                                        </h5>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-link text-danger p-0" 
                                                    onclick="return confirm('Remove this speaker from cart?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Booking Summary -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <h4 class="fw-bold mb-4">Booking Summary</h4>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span>Speakers (<?php echo count($cartItems); ?>)</span>
                                <span class="fw-bold"><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tax (<?php echo getSetting('tax_rate', '10'); ?>%)</span>
                                <span class="fw-bold">
                                    <?php 
                                    $taxRate = (float)getSetting('tax_rate', '10');
                                    $taxAmount = $subtotal * ($taxRate / 100);
                                    echo formatPrice($taxAmount); 
                                    ?>
                                </span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-4">
                                <h5 class="fw-bold">Total</h5>
                                <h5 class="text-primary fw-bold">
                                    <?php echo formatPrice($subtotal + $taxAmount); ?>
                                </h5>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <?php if (isLoggedIn()): ?>
                                    <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-calendar-check me-2"></i> Proceed to Booking
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>/login.php?redirect=checkout" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i> Login to Continue
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i> Browse More Speakers
                                </a>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Speakers will review your booking request within 24-48 hours
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart -->
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-5x text-muted mb-4"></i>
                <h2 class="fw-bold mb-3">Your Booking Cart is Empty</h2>
                <p class="text-muted mb-4">Start by browsing our amazing speakers</p>
                <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i> Find Speakers
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>
