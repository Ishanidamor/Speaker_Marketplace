<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Site Configuration
define('SITE_URL', 'http://localhost/speakermarketplace');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('SPEAKER_PHOTOS_PATH', UPLOAD_PATH . 'speakers/');
define('SPEAKER_PORTFOLIO_PATH', UPLOAD_PATH . 'portfolio/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(SPEAKER_PHOTOS_PATH)) {
    mkdir(SPEAKER_PHOTOS_PATH, 0755, true);
}
if (!file_exists(SPEAKER_PORTFOLIO_PATH)) {
    mkdir(SPEAKER_PORTFOLIO_PATH, 0755, true);
}

// Get settings from database
function getSetting($key, $default = '') {
    try {
        $result = fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key], 's');
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Update setting in database
function updateSetting($key, $value) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param('sss', $key, $value, $value);
    return $stmt->execute();
}

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']], 'i');
}

function getCurrentAdmin() {
    if (!isAdmin()) {
        return null;
    }
    return fetchOne("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']], 'i');
}

// Speaker authentication functions
function isSpeakerLoggedIn() {
    return isset($_SESSION['speaker_id']);
}

function requireSpeakerLogin() {
    if (!isSpeakerLoggedIn()) {
        header('Location: ' . SITE_URL . '/speaker-login.php');
        exit;
    }
}

function getCurrentSpeaker() {
    if (!isSpeakerLoggedIn()) {
        return null;
    }
    return fetchOne("SELECT * FROM speakers WHERE id = ?", [$_SESSION['speaker_id']], 'i');
}

// Notification functions
function getUnreadNotificationCount($userId) {
    try {
        $result = fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$userId], 'i');
        return $result ? $result['count'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getRecentNotifications($userId, $limit = 5) {
    try {
        return fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?", [$userId, $limit], 'ii');
    } catch (Exception $e) {
        return [];
    }
}

function markNotificationAsRead($notificationId, $userId) {
    try {
        executeQuery("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$notificationId, $userId], 'ii');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    if ($time < 31536000) return floor($time/2592000) . 'mo ago';
    return floor($time/31536000) . 'y ago';
}

// Utility functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function formatPrice($price) {
    $symbol = getSetting('currency_symbol', '$');
    return $symbol . number_format($price, 2);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Booking Cart functions
function getCartItems() {
    try {
        if (isLoggedIn()) {
            return fetchAll("SELECT c.*, s.name as speaker_name, s.keynote_rate, s.workshop_rate, s.virtual_rate, s.slug, s.location 
                            FROM booking_cart c 
                            JOIN speakers s ON c.speaker_id = s.id 
                            WHERE c.user_id = ?", [$_SESSION['user_id']], 'i');
        } else {
            $sessionId = session_id();
            // If session ID is empty, return empty array to avoid database errors
            if (empty($sessionId)) {
                return [];
            }
            return fetchAll("SELECT c.*, s.name as speaker_name, s.keynote_rate, s.workshop_rate, s.virtual_rate, s.slug, s.location 
                            FROM booking_cart c 
                            JOIN speakers s ON c.speaker_id = s.id 
                            WHERE c.session_id = ?", [$sessionId], 's');
        }
    } catch (Exception $e) {
        // Log error and return empty array to prevent breaking the page
        error_log("Cart items error: " . $e->getMessage());
        return [];
    }
}

function getCartCount() {
    try {
        $items = getCartItems();
        return count($items);
    } catch (Exception $e) {
        // Log error and return 0 to prevent breaking the page
        error_log("Cart count error: " . $e->getMessage());
        return 0;
    }
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
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
            default:
                $rate = $item['keynote_rate'];
        }
        $total += $rate;
    }
    return $total;
}

// Email function (basic - can be enhanced with PHPMailer)
function sendEmail($to, $subject, $message) {
    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $message, $headers);
}


?>
