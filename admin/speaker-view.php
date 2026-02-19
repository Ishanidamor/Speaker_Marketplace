<?php
$pageTitle = 'View Speaker';
require_once '../config/config.php';
requireAdmin();

$speakerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($speakerId <= 0) {
    setFlashMessage('error', 'Invalid speaker ID');
    redirect('speakers.php');
}

// Get speaker data
$speaker = fetchOne("SELECT * FROM speakers WHERE id = ?", [$speakerId], 'i');

if (!$speaker) {
    setFlashMessage('error', 'Speaker not found');
    redirect('speakers.php');
}

// Get speaker categories
$categories = fetchAll(
    "SELECT c.* FROM categories c 
     JOIN speaker_categories sc ON c.id = sc.category_id 
     WHERE sc.speaker_id = ? 
     ORDER BY c.name", 
    [$speakerId], 
    'i'
);

// Get upcoming bookings
$upcomingBookings = fetchAll(
    "SELECT b.*, u.name as organizer_name 
     FROM bookings b
     JOIN users u ON b.organizer_id = u.id
     WHERE b.speaker_id = ? 
     AND b.status IN ('confirmed', 'pending')
     AND b.event_date >= CURDATE()
     ORDER BY b.event_date ASC, b.event_time ASC
     LIMIT 5", 
    [$speakerId], 
    'i'
);

// Get recent reviews
$recentReviews = fetchAll(
    "SELECT r.*, u.name as user_name, u.photo as user_photo
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.speaker_id = ? 
     ORDER BY r.created_at DESC
     LIMIT 3", 
    [$speakerId], 
    'i'
);

// Calculate average rating
$ratingStats = fetchOne(
    "SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
     FROM reviews 
     WHERE speaker_id = ?", 
    [$speakerId], 
    'i'
);

// Format rating percentages
if ($ratingStats['total_reviews'] > 0) {
    $ratingStats['five_star_pct'] = round(($ratingStats['five_star'] / $ratingStats['total_reviews']) * 100);
    $ratingStats['four_star_pct'] = round(($ratingStats['four_star'] / $ratingStats['total_reviews']) * 100);
    $ratingStats['three_star_pct'] = round(($ratingStats['three_star'] / $ratingStats['total_reviews']) * 100);
    $ratingStats['two_star_pct'] = round(($ratingStats['two_star'] / $ratingStats['total_reviews']) * 100);
    $ratingStats['one_star_pct'] = round(($ratingStats['one_star'] / $ratingStats['total_reviews']) * 100);
    $ratingStats['avg_rating'] = round($ratingStats['avg_rating'], 1);
}

// Get speaker statistics
$stats = fetchOne(
    "SELECT 
        (SELECT COUNT(*) FROM bookings WHERE speaker_id = ?) as total_bookings,
        (SELECT COUNT(*) FROM bookings WHERE speaker_id = ? AND status = 'completed') as completed_bookings,
        (SELECT COUNT(*) FROM reviews WHERE speaker_id = ?) as total_reviews,
        (SELECT SUM(final_amount) FROM bookings WHERE speaker_id = ? AND status = 'completed') as total_earnings",
    [$speakerId, $speakerId, $speakerId, $speakerId],
    'iiii'
);

// Format earnings
$stats['total_earnings'] = $stats['total_earnings'] ?? 0;

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas fa-microphone-alt me-2"></i> Speaker Profile
            </h1>
            <div>
                <a href="speakers.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Speakers
                </a>
                <a href="speaker-edit.php?id=<?php echo $speakerId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
            </div>
        </div>

        <!-- Speaker Header -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <?php if (!empty($speaker['photo'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo htmlspecialchars($speaker['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($speaker['name']); ?>" 
                                 class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                 style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($speaker['featured']): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-star me-1"></i> Featured
                            </span>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <span class="badge bg-<?php echo $speaker['status'] === 'active' ? 'success' : ($speaker['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($speaker['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h2 class="mb-1"><?php echo htmlspecialchars($speaker['name']); ?></h2>
                        <?php if (!empty($speaker['title'])): ?>
                            <h5 class="text-muted mb-3"><?php echo htmlspecialchars($speaker['title']); ?></h5>
                        <?php endif; ?>
                        
                        <?php if (!empty($speaker['company'])): ?>
                            <p class="mb-2">
                                <i class="fas fa-building me-2 text-muted"></i>
                                <?php echo htmlspecialchars($speaker['company']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($speaker['email'])): ?>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-muted"></i>
                                <a href="mailto:<?php echo htmlspecialchars($speaker['email']); ?>">
                                    <?php echo htmlspecialchars($speaker['email']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($speaker['phone'])): ?>
                            <p class="mb-2">
                                <i class="fas fa-phone me-2 text-muted"></i>
                                <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $speaker['phone'])); ?>">
                                    <?php echo htmlspecialchars($speaker['phone']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($speaker['website'])): ?>
                            <p class="mb-0">
                                <i class="fas fa-globe me-2 text-muted"></i>
                                <a href="<?php echo htmlspecialchars($speaker['website']); ?>" target="_blank">
                                    <?php echo parse_url($speaker['website'], PHP_URL_HOST); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Speaker Statistics</h5>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-calendar-check me-2 text-primary"></i>
                                        <strong><?php echo $stats['total_bookings']; ?></strong> Total Bookings
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle me-2 text-success"></i>
                                        <strong><?php echo $stats['completed_bookings']; ?></strong> Completed
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-star me-2 text-warning"></i>
                                        <strong><?php echo $ratingStats['total_reviews'] ?? 0; ?></strong> Reviews
                                        <?php if (!empty($ratingStats['avg_rating'])): ?>
                                            (<?php echo $ratingStats['avg_rating']; ?>/5.0)
                                        <?php endif; ?>
                                    </li>
                                    <li class="mb-0">
                                        <i class="fas fa-dollar-sign me-2 text-success"></i>
                                        <strong>$<?php echo number_format($stats['total_earnings'], 2); ?></strong> Earned
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <?php 
                $socialLinks = [
                    'linkedin' => ['icon' => 'linkedin', 'label' => 'LinkedIn'],
                    'twitter' => ['icon' => 'twitter', 'label' => 'Twitter'],
                    'facebook' => ['icon' => 'facebook', 'label' => 'Facebook'],
                    'instagram' => ['icon' => 'instagram', 'label' => 'Instagram'],
                    'youtube' => ['icon' => 'youtube', 'label' => 'YouTube']
                ];
                $hasSocial = false;
                
                foreach ($socialLinks as $key => $social) {
                    if (!empty($speaker[$key])) {
                        $hasSocial = true;
                        break;
                    }
                }
                
                if ($hasSocial): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">Connect</h6>
                        <div class="d-flex gap-2">
                            <?php foreach ($socialLinks as $key => $social): 
                                if (!empty($speaker[$key])): 
                                    $url = $speaker[$key];
                                    if ($key === 'twitter' && !preg_match("~^(?:f|ht)tps?://~i", $url)) {
                                        $url = 'https://twitter.com/' . ltrim($url, '@');
                                    } elseif ($key === 'instagram' && !preg_match("~^(?:f|ht)tps?://~i", $url)) {
                                        $url = 'https://instagram.com/' . ltrim($url, '@');
                                    }
                            ?>
                                <a href="<?php echo htmlspecialchars($url); ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-secondary btn-sm"
                                   data-bs-toggle="tooltip" 
                                   title="<?php echo $social['label']; ?>">
                                    <i class="fab fa-<?php echo $social['icon']; ?> fa-lg"></i>
                                </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- About Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">About</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($speaker['bio'])): ?>
                            <div class="mb-4">
                                <?php echo nl2br(htmlspecialchars($speaker['bio'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                No biography available for this speaker.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($categories)): ?>
                            <div class="mt-4">
                                <h6>Expertise</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($categories as $category): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($speaker['languages'])): ?>
                            <div class="mt-4">
                                <h6>Languages Spoken</h6>
                                <p class="mb-0">
                                    <?php 
                                    $languages = array_map('trim', explode(',', $speaker['languages']));
                                    echo htmlspecialchars(implode(', ', $languages));
                                    ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($speaker['travel_preferences'])): ?>
                            <div class="mt-4">
                                <h6>Travel Preferences</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($speaker['travel_preferences'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Speaking Engagements -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Speaking Engagements</h5>
                        <a href="bookings.php?speaker=<?php echo $speakerId; ?>" class="btn btn-sm btn-outline-primary">
                            View All Bookings <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Event</th>
                                        <th>Organizer</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($upcomingBookings)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="text-muted">No upcoming speaking engagements</div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($upcomingBookings as $booking): 
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'confirmed' => 'success',
                                                'cancelled' => 'danger',
                                                'completed' => 'info'
                                            ];
                                            
                                            $typeIcons = [
                                                'keynote' => 'microphone',
                                                'workshop' => 'chalkboard-teacher',
                                                'virtual' => 'video'
                                            ];
                                        ?>
                                            <tr>
                                                <td>
                                                    <a href="booking-view.php?id=<?php echo $booking['id']; ?>">
                                                        <?php echo htmlspecialchars($booking['event_name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['organizer_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $date = new DateTime($booking['event_date']);
                                                    echo $date->format('M j, Y'); 
                                                    ?>
                                                    <?php if (!empty($booking['event_time'])): ?>
                                                        <div class="text-muted small">
                                                            <?php 
                                                            $time = new DateTime($booking['event_time']);
                                                            echo $time->format('g:i A'); 
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-<?php echo $typeIcons[$booking['engagement_type']] ?? 'calendar'; ?> me-1"></i>
                                                    <?php echo ucfirst($booking['engagement_type']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $statusColors[$booking['status']] ?? 'secondary'; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Reviews</h5>
                        <a href="reviews.php?speaker=<?php echo $speakerId; ?>" class="btn btn-sm btn-outline-primary">
                            View All Reviews <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentReviews) && empty($ratingStats['total_reviews'])): ?>
                            <div class="text-center py-4">
                                <div class="text-muted mb-3">No reviews yet</div>
                                <p>This speaker hasn't received any reviews yet.</p>
                            </div>
                        <?php else: ?>
                            <!-- Rating Summary -->
                            <div class="row mb-4">
                                <div class="col-md-4 text-center">
                                    <div class="display-4 fw-bold text-primary">
                                        <?php echo $ratingStats['avg_rating'] ?? '0.0'; ?>
                                    </div>
                                    <div class="mb-2">
                                        <?php 
                                        $avgRating = $ratingStats['avg_rating'] ?? 0;
                                        $fullStars = floor($avgRating);
                                        $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                        
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="fas fa-star text-warning"></i> ';
                                        }
                                        
                                        if ($hasHalfStar) {
                                            echo '<i class="fas fa-star-half-alt text-warning"></i> ';
                                        }
                                        
                                        for ($i = 0; $i < $emptyStars; $i++) {
                                            echo '<i class="far fa-star text-warning"></i> ';
                                        }
                                        ?>
                                    </div>
                                    <div class="text-muted">
                                        <?php echo $ratingStats['total_reviews'] ?? 0; ?> reviews
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <?php for ($i = 5; $i >= 1; $i--): 
                                        $count = $ratingStats[$i . '_star'] ?? 0;
                                        $pct = $ratingStats[$i . '_star_pct'] ?? 0;
                                    ?>
                                        <div class="row align-items-center mb-2">
                                            <div class="col-2 text-end">
                                                <span class="text-muted"><?php echo $i; ?> <i class="fas fa-star text-warning"></i></span>
                                            </div>
                                            <div class="col-7">
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" role="progressbar" 
                                                         style="width: <?php echo $pct; ?>%" 
                                                         aria-valuenow="<?php echo $pct; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted"><?php echo $count; ?></small>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <!-- Recent Reviews -->
                            <h6 class="mb-3">Recent Reviews</h6>
                            <?php if (empty($recentReviews)): ?>
                                <div class="alert alert-info mb-0">
                                    No recent reviews to display.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentReviews as $review): ?>
                                        <div class="list-group-item border-0 px-0 py-3">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <?php if (!empty($review['user_photo'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>/uploads/users/<?php echo htmlspecialchars($review['user_photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($review['user_name']); ?>" 
                                                             class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 50px; height: 50px;">
                                                            <i class="fas fa-user text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php 
                                                            $date = new DateTime($review['created_at']);
                                                            echo $date->format('M j, Y'); 
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <div class="mb-2">
                                                        <?php 
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $review['rating'] 
                                                                ? '<i class="fas fa-star text-warning"></i> ' 
                                                                : '<i class="far fa-star text-warning"></i> ';
                                                        }
                                                        ?>
                                                    </div>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Rates Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Speaking Rates</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>Keynote Speech</span>
                                <span class="fw-bold">$<?php echo number_format($speaker['keynote_rate'], 2); ?></span>
                            </div>
                            <div class="small text-muted">45-60 minute presentation</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>Workshop</span>
                                <span class="fw-bold">$<?php echo number_format($speaker['workshop_rate'], 2); ?></span>
                            </div>
                            <div class="small text-muted">Half-day or full-day interactive session</div>
                        </div>
                        
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>Virtual Event</span>
                                <span class="fw-bold">$<?php echo number_format($speaker['virtual_rate'], 2); ?></span>
                            </div>
                            <div class="small text-muted">Live online presentation or webinar</div>
                        </div>
                        
                        <?php if (!empty($speaker['travel_preferences'])): ?>
                            <div class="alert alert-light mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Travel fees may apply. See travel preferences for details.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Documents</h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                            <i class="fas fa-plus me-1"></i> Add
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Speaker documents feature coming soon.
                        </div>
                        <!-- Document list would go here -->
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php 
                            // Sample activity data - in a real app, this would come from an activity log table
                            $activities = [
                                [
                                    'date' => '2023-11-05 14:30:00',
                                    'icon' => 'edit',
                                    'color' => 'primary',
                                    'title' => 'Profile updated',
                                    'description' => 'Speaker profile was updated by admin'
                                ],
                                [
                                    'date' => '2023-11-03 09:15:00',
                                    'icon' => 'calendar-check',
                                    'color' => 'success',
                                    'title' => 'Booking confirmed',
                                    'description' => 'Confirmed for Tech Conference 2023'
                                ],
                                [
                                    'date' => '2023-10-28 16:45:00',
                                    'icon' => 'star',
                                    'color' => 'warning',
                                    'title' => 'New review received',
                                    'description' => 'Received 5-star rating from John D.'
                                ],
                                [
                                    'date' => '2023-10-25 11:20:00',
                                    'icon' => 'file-invoice-dollar',
                                    'color' => 'info',
                                    'title' => 'Invoice sent',
                                    'description' => 'Invoice #INV-2023-105 sent to client'
                                ]
                            ];
                            
                            foreach ($activities as $activity): 
                                $date = new DateTime($activity['date']);
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon bg-soft-<?php echo $activity['color']; ?> text-<?php echo $activity['color']; ?>">
                                        <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-time text-muted small">
                                            <?php echo $date->format('M j, Y \a\t g:i A'); ?>
                                        </div>
                                        <h6 class="mb-1"><?php echo $activity['title']; ?></h6>
                                        <p class="mb-0 small text-muted"><?php echo $activity['description']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="#" class="btn btn-sm btn-outline-secondary w-100 mt-3">
                            View Full Activity Log
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Document upload feature coming soon.
                </div>
                <!-- Form would go here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" disabled>Upload</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<style>
.timeline {
    position: relative;
    padding-left: 2rem;
    margin: 0 0 0 1rem;
    border-left: 2px solid #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -2.5rem;
    top: 0;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.timeline-content {
    padding: 0.5rem 0 0.5rem 1rem;
}

.timeline-time {
    font-size: 0.75rem;
}
</style>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Initialize popovers
var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
});
</script>
