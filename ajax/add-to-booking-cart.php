<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$speakerId = isset($_POST['speaker_id']) ? (int)$_POST['speaker_id'] : 0;
$format = isset($_POST['format']) ? sanitize($_POST['format']) : 'keynote';

if (!$speakerId) {
    echo json_encode(['success' => false, 'message' => 'Invalid speaker']);
    exit;
}

// Check if speaker exists and is active
$speaker = fetchOne("SELECT * FROM speakers WHERE id = ? AND status = 'active'", [$speakerId], 'i');

if (!$speaker) {
    echo json_encode(['success' => false, 'message' => 'Speaker not found']);
    exit;
}

// Validate format
$validFormats = ['keynote', 'workshop', 'virtual', 'panel'];
if (!in_array($format, $validFormats)) {
    $format = 'keynote';
}

// Check if already in cart
if (isLoggedIn()) {
    $existing = fetchOne("SELECT * FROM booking_cart WHERE user_id = ? AND speaker_id = ?", 
                        [$_SESSION['user_id'], $speakerId], 'ii');
} else {
    $sessionId = session_id();
    $existing = fetchOne("SELECT * FROM booking_cart WHERE session_id = ? AND speaker_id = ?", 
                        [$sessionId, $speakerId], 'si');
}

if ($existing) {
    // Update format if already in cart
    executeQuery("UPDATE booking_cart SET format = ? WHERE id = ?", [$format, $existing['id']], 'si');
    echo json_encode([
        'success' => true, 
        'message' => 'Booking cart updated',
        'cart_count' => getCartCount()
    ]);
    exit;
}

// Add to booking cart
try {
    if (isLoggedIn()) {
        executeQuery("INSERT INTO booking_cart (user_id, speaker_id, format) VALUES (?, ?, ?)", 
                    [$_SESSION['user_id'], $speakerId, $format], 'iis');
    } else {
        $sessionId = session_id();
        executeQuery("INSERT INTO booking_cart (session_id, speaker_id, format) VALUES (?, ?, ?)", 
                    [$sessionId, $speakerId, $format], 'sis');
    }
    
    $cartCount = getCartCount();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Speaker added to booking cart',
        'cart_count' => $cartCount
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
}
?>
