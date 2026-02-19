<?php
require_once 'config/config.php';
requireLogin();

$currentUser = getCurrentUser();

// Handle marking notification as read
if (isset($_GET['read'])) {
    $notificationId = (int)$_GET['read'];
    markNotificationAsRead($notificationId, $currentUser['id']);
    redirect(SITE_URL . '/bookings.php');
}

// Handle marking all as read
if (isset($_POST['mark_all_read'])) {
    try {
        executeQuery("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$currentUser['id']], 'i');
        setFlashMessage('success', 'All notifications marked as read.');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to update notifications.');
    }
}

// Get all notifications
$notifications = fetchAll(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC",
    [$currentUser['id']], 'i'
);

$pageTitle = 'Notifications';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
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
            
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </h4>
                    <?php if (!empty($notifications)): ?>
                        <form method="POST" action="" class="d-inline">
                            <button type="submit" name="mark_all_read" class="btn btn-light btn-sm">
                                <i class="fas fa-check-double me-1"></i>Mark All Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No notifications yet</h5>
                            <p class="text-muted">You'll receive notifications when speakers respond to your booking requests.</p>
                            <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Find Speakers
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light border-start border-primary border-3'; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 <?php echo $notification['is_read'] ? 'text-muted' : 'fw-bold'; ?>">
                                                <?php if (!$notification['is_read']): ?>
                                                    <i class="fas fa-circle text-primary me-2" style="font-size: 8px;"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h6>
                                            <p class="mb-1 <?php echo $notification['is_read'] ? 'text-muted' : ''; ?>">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo timeAgo($notification['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <?php
                                            $badgeClass = 'secondary';
                                            $icon = 'info-circle';
                                            
                                            if (strpos($notification['type'], 'accepted') !== false) {
                                                $badgeClass = 'success';
                                                $icon = 'check-circle';
                                            } elseif (strpos($notification['type'], 'rejected') !== false) {
                                                $badgeClass = 'danger';
                                                $icon = 'times-circle';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                                <?php echo ucfirst(str_replace('booking_', '', $notification['type'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($notifications)): ?>
                    <div class="card-footer text-center">
                        <a href="<?php echo SITE_URL; ?>/bookings.php" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-check me-2"></i>View My Bookings
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
