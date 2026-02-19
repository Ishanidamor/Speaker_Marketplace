<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Get statistics
$totalSpeakers = fetchOne("SELECT COUNT(*) as count FROM speakers")['count'];
$activeSpeakers = fetchOne("SELECT COUNT(*) as count FROM speakers WHERE status = 'active'")['count'];
$totalBookings = fetchOne("SELECT COUNT(*) as count FROM bookings")['count'];
$pendingBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'")['count'];
$totalUsers = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$totalRevenue = fetchOne("SELECT SUM(final_amount) as total FROM bookings WHERE payment_status = 'completed'")['total'] ?? 0;

// Recent bookings
$recentBookings = fetchAll("SELECT b.*, u.name as user_name, u.email as user_email 
                         FROM bookings b 
                         JOIN users u ON b.organizer_id = u.id 
                         ORDER BY b.created_at DESC 
                         LIMIT 10");

// Top speakers
$topSpeakers = fetchAll("SELECT s.name, s.keynote_rate, COUNT(bi.id) as bookings, SUM(bi.rate) as revenue
                        FROM speakers s
                        JOIN booking_items bi ON s.id = bi.speaker_id
                        JOIN bookings b ON bi.booking_id = b.id
                        WHERE b.payment_status = 'completed'
                        GROUP BY s.id
                        ORDER BY bookings DESC
                        LIMIT 5");

// Monthly revenue (last 6 months)
$monthlyRevenue = fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                           SUM(final_amount) as revenue,
                           COUNT(*) as bookings
                           FROM bookings 
                           WHERE payment_status = 'completed' 
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                           GROUP BY month
                           ORDER BY month DESC");
?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #1976d2;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Speakers</h6>
                        <h2 class="fw-bold mb-0"><?php echo $totalSpeakers; ?></h2>
                        <small class="text-success">
                            <i class="fas fa-check-circle"></i> <?php echo $activeSpeakers; ?> active
                        </small>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-microphone-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #f44336;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Bookings</h6>
                        <h2 class="fw-bold mb-0"><?php echo $totalBookings; ?></h2>
                        <small class="text-warning">
                            <i class="fas fa-clock"></i> <?php echo $pendingBookings; ?> pending
                        </small>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #4caf50;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Revenue</h6>
                        <h2 class="fw-bold mb-0"><?php echo formatPrice($totalRevenue); ?></h2>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> From completed bookings
                        </small>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #ff9800;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Users</h6>
                        <h2 class="fw-bold mb-0"><?php echo $totalUsers; ?></h2>
                        <small class="text-info">
                            <i class="fas fa-user-plus"></i> Event organizers
                        </small>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Bookings -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-calendar-check me-2"></i> Recent Bookings
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking #</th>
                                <th>Organizer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $booking['booking_number']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                    </td>
                                    <td class="fw-bold text-primary"><?php echo formatPrice($booking['final_amount']); ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusColors[$booking['booking_status']]; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <a href="bookings.php?view=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="bookings.php" class="btn btn-primary">
                    View All Bookings <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Top Speakers & Monthly Revenue -->
    <div class="col-lg-4">
        <!-- Top Speakers -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-star me-2"></i> Top Speakers
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($topSpeakers as $speaker): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($speaker['name']); ?></h6>
                            <small class="text-muted"><?php echo $speaker['bookings']; ?> bookings</small>
                        </div>
                        <h6 class="text-success mb-0"><?php echo formatPrice($speaker['revenue']); ?></h6>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Monthly Revenue -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line me-2"></i> Monthly Revenue
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($monthlyRevenue as $month): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></span>
                            <span class="fw-bold"><?php echo formatPrice($month['revenue']); ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo min(100, ($month['revenue'] / $totalRevenue) * 100); ?>%">
                            </div>
                        </div>
                        <small class="text-muted"><?php echo $month['orders']; ?> orders</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
