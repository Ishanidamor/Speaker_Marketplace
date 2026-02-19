<?php
require_once 'config/config.php';

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (!$slug) {
    redirect(SITE_URL . '/speakers.php');
}

// Get speaker details
$speaker = fetchOne("SELECT s.*, c.name as category_name, c.slug as category_slug
                     FROM speakers s 
                     LEFT JOIN categories c ON s.category_id = c.id 
                     WHERE s.slug = ? AND s.status = 'active'", [$slug], 's');

if (!$speaker) {
    redirect(SITE_URL . '/speakers.php');
}

// Update view count
executeQuery("UPDATE speakers SET views = views + 1 WHERE id = ?", [$speaker['id']], 'i');

// Get speaker photos
$photos = fetchAll("SELECT * FROM speaker_photos WHERE speaker_id = ? ORDER BY display_order", 
                   [$speaker['id']], 'i');

// Get speaker expertise tags
$expertiseTags = fetchAll("SELECT expertise FROM speaker_expertise WHERE speaker_id = ?", 
                         [$speaker['id']], 'i');

// Get reviews
$reviews = fetchAll("SELECT r.*, u.name as organizer_name, b.event_name 
                    FROM speaker_reviews r
                    JOIN users u ON r.organizer_id = u.id
                    LEFT JOIN bookings b ON r.booking_id = b.id
                    WHERE r.speaker_id = ? AND r.status = 'approved'
                    ORDER BY r.created_at DESC
                    LIMIT 10", [$speaker['id']], 'i');

// Get related speakers
$relatedSpeakers = fetchAll("SELECT s.*, 
                             (SELECT image_path FROM speaker_photos WHERE speaker_id = s.id ORDER BY display_order LIMIT 1) as image
                             FROM speakers s 
                             WHERE s.category_id = ? AND s.id != ? AND s.status = 'active' 
                             ORDER BY s.rating DESC
                             LIMIT 4", 
                            [$speaker['category_id'], $speaker['id']], 'ii');

$pageTitle = $speaker['name'];
require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/speakers.php">Speakers</a></li>
                <?php if ($speaker['category_name']): ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/speakers.php?category=<?php echo $speaker['category_slug']; ?>">
                            <?php echo htmlspecialchars($speaker['category_name']); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($speaker['name']); ?></li>
            </ol>
        </nav>
        
        <!-- Speaker Profile -->
        <div class="row g-4 mb-5">
            <!-- Speaker Photos -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (count($photos) > 0): ?>
                            <div id="speakerCarousel" class="carousel slide" data-mdb-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                            <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo $photo['image_path']; ?>" 
                                                 class="d-block w-100" 
                                                 style="height: 400px; object-fit: cover;"
                                                 alt="<?php echo htmlspecialchars($speaker['name']); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (count($photos) > 1): ?>
                                    <button class="carousel-control-prev" type="button" data-mdb-target="#speakerCarousel" data-mdb-slide="prev">
                                        <span class="carousel-control-prev-icon"></span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-mdb-target="#speakerCarousel" data-mdb-slide="next">
                                        <span class="carousel-control-next-icon"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light" style="height: 400px;">
                                <i class="fas fa-user fa-5x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Video Introduction -->
                <?php if ($speaker['video_intro_url']): ?>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-video me-2"></i> Video Introduction
                            </h6>
                            <div class="ratio ratio-16x9">
                                <iframe src="<?php echo htmlspecialchars($speaker['video_intro_url']); ?>" 
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Social Links -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Connect</h6>
                        <div class="d-flex gap-2">
                            <?php if ($speaker['linkedin_url']): ?>
                                <a href="<?php echo htmlspecialchars($speaker['linkedin_url']); ?>" 
                                   target="_blank" class="btn btn-outline-primary">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($speaker['twitter_url']): ?>
                                <a href="<?php echo htmlspecialchars($speaker['twitter_url']); ?>" 
                                   target="_blank" class="btn btn-outline-info">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($speaker['website_url']): ?>
                                <a href="<?php echo htmlspecialchars($speaker['website_url']); ?>" 
                                   target="_blank" class="btn btn-outline-secondary">
                                    <i class="fas fa-globe"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Speaker Info -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge bg-primary fs-6">
                                <?php echo htmlspecialchars($speaker['category_name'] ?? 'Professional Speaker'); ?>
                            </span>
                            <?php if ($speaker['featured']): ?>
                                <span class="badge bg-warning fs-6 ms-2">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($speaker['name']); ?></h1>
                        <h5 class="text-muted mb-4"><?php echo htmlspecialchars($speaker['title']); ?></h5>
                        
                        <!-- Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-warning mb-1">
                                        <i class="fas fa-star fa-2x"></i>
                                    </div>
                                    <h4 class="mb-0"><?php echo number_format($speaker['rating'], 1); ?></h4>
                                    <small class="text-muted"><?php echo $speaker['total_reviews']; ?> reviews</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-primary mb-1">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                    <h4 class="mb-0"><?php echo $speaker['total_events']; ?></h4>
                                    <small class="text-muted">Events</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-success mb-1">
                                        <i class="fas fa-briefcase fa-2x"></i>
                                    </div>
                                    <h4 class="mb-0"><?php echo $speaker['years_experience']; ?></h4>
                                    <small class="text-muted">Years Exp.</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-info mb-1">
                                        <i class="fas fa-eye fa-2x"></i>
                                    </div>
                                    <h4 class="mb-0"><?php echo $speaker['views']; ?></h4>
                                    <small class="text-muted">Views</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bio -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">About</h5>
                            <p class="text-muted" style="white-space: pre-line;">
                                <?php echo htmlspecialchars($speaker['bio']); ?>
                            </p>
                        </div>
                        
                        <!-- Expertise Tags -->
                        <?php if (count($expertiseTags) > 0): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Expertise</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($expertiseTags as $tag): ?>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($tag['expertise']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Location & Languages -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <strong>Location:</strong> <?php echo htmlspecialchars($speaker['location']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <i class="fas fa-language text-primary me-2"></i>
                                        <strong>Languages:</strong> <?php echo htmlspecialchars($speaker['languages']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Event Formats & Rates -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Event Formats & Rates</h5>
                            <div class="row g-3">
                                <?php if ($speaker['keynote_rate']): ?>
                                    <div class="col-md-6">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-microphone text-primary me-2"></i>
                                                            Keynote Speech
                                                        </h6>
                                                        <small class="text-muted">30-60 minutes</small>
                                                    </div>
                                                    <h4 class="text-primary mb-0">
                                                        <?php echo formatPrice($speaker['keynote_rate']); ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($speaker['workshop_rate']): ?>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-chalkboard-teacher text-success me-2"></i>
                                                            Workshop
                                                        </h6>
                                                        <small class="text-muted">2-4 hours</small>
                                                    </div>
                                                    <h4 class="text-success mb-0">
                                                        <?php echo formatPrice($speaker['workshop_rate']); ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($speaker['virtual_rate']): ?>
                                    <div class="col-md-6">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-video text-info me-2"></i>
                                                            Virtual Event
                                                        </h6>
                                                        <small class="text-muted">Online session</small>
                                                    </div>
                                                    <h4 class="text-info mb-0">
                                                        <?php echo formatPrice($speaker['virtual_rate']); ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($speaker['panel_rate']): ?>
                                    <div class="col-md-6">
                                        <div class="card border-warning">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <i class="fas fa-users text-warning me-2"></i>
                                                            Panel Discussion
                                                        </h6>
                                                        <small class="text-muted">Moderated panel</small>
                                                    </div>
                                                    <h4 class="text-warning mb-0">
                                                        <?php echo formatPrice($speaker['panel_rate']); ?>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Booking Buttons -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" onclick="showBookingModal()">
                                <i class="fas fa-calendar-plus me-2"></i> Request Booking
                            </button>
                            <a href="<?php echo SITE_URL; ?>/contact.php?speaker=<?php echo $speaker['slug']; ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-envelope me-2"></i> Send Inquiry
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <?php if (count($reviews) > 0): ?>
            <section class="mb-5">
                <h3 class="fw-bold mb-4">
                    <i class="fas fa-star text-warning me-2"></i> Reviews & Testimonials
                </h3>
                <div class="row g-4">
                    <?php foreach ($reviews as $review): ?>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($review['organizer_name']); ?></h6>
                                            <?php if ($review['event_name']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($review['event_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-warning">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i < $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($review['review_title']): ?>
                                        <h6 class="mb-2"><?php echo htmlspecialchars($review['review_title']); ?></h6>
                                    <?php endif; ?>
                                    
                                    <p class="mb-2"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    
                                    <?php if ($review['would_recommend']): ?>
                                        <div class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <small>Would recommend</small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Related Speakers -->
        <?php if (count($relatedSpeakers) > 0): ?>
            <section class="mb-5">
                <h3 class="fw-bold mb-4">Similar Speakers</h3>
                <div class="row g-4">
                    <?php foreach ($relatedSpeakers as $relatedSpeaker): ?>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="card product-card h-100">
                                <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $relatedSpeaker['slug']; ?>">
                                    <?php if ($relatedSpeaker['image']): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo $relatedSpeaker['image']; ?>" 
                                             class="product-image" 
                                             alt="<?php echo htmlspecialchars($relatedSpeaker['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="card-body">
                                    <h6 class="card-title mb-2">
                                        <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $relatedSpeaker['slug']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($relatedSpeaker['name']); ?>
                                        </a>
                                    </h6>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($relatedSpeaker['title']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-warning">
                                            <i class="fas fa-star"></i> <?php echo number_format($relatedSpeaker['rating'], 1); ?>
                                        </div>
                                        <small class="text-muted">
                                            From <?php echo formatPrice($relatedSpeaker['virtual_rate'] ?? $relatedSpeaker['keynote_rate']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check me-2"></i> Request Booking
                </h5>
                <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Select event format for <strong><?php echo htmlspecialchars($speaker['name']); ?></strong>:</h6>
                <div class="row g-3">
                    <?php if ($speaker['keynote_rate']): ?>
                        <div class="col-md-6">
                            <button class="btn btn-outline-primary w-100 p-3" onclick="addToBookingCart('keynote')">
                                <i class="fas fa-microphone fa-2x d-block mb-2"></i>
                                <strong>Keynote Speech</strong><br>
                                <span class="text-primary"><?php echo formatPrice($speaker['keynote_rate']); ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($speaker['workshop_rate']): ?>
                        <div class="col-md-6">
                            <button class="btn btn-outline-success w-100 p-3" onclick="addToBookingCart('workshop')">
                                <i class="fas fa-chalkboard-teacher fa-2x d-block mb-2"></i>
                                <strong>Workshop</strong><br>
                                <span class="text-success"><?php echo formatPrice($speaker['workshop_rate']); ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($speaker['virtual_rate']): ?>
                        <div class="col-md-6">
                            <button class="btn btn-outline-info w-100 p-3" onclick="addToBookingCart('virtual')">
                                <i class="fas fa-video fa-2x d-block mb-2"></i>
                                <strong>Virtual Event</strong><br>
                                <span class="text-info"><?php echo formatPrice($speaker['virtual_rate']); ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($speaker['panel_rate']): ?>
                        <div class="col-md-6">
                            <button class="btn btn-outline-warning w-100 p-3" onclick="addToBookingCart('panel')">
                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                <strong>Panel Discussion</strong><br>
                                <span class="text-warning"><?php echo formatPrice($speaker['panel_rate']); ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showBookingModal() {
    const modal = new mdb.Modal(document.getElementById('bookingModal'));
    modal.show();
}

function addToBookingCart(format) {
    fetch('<?php echo SITE_URL; ?>/ajax/add-to-booking-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `speaker_id=<?php echo $speaker['id']; ?>&format=${format}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badges = document.querySelectorAll('.cart-badge, .bottom-nav-item .badge');
            badges.forEach(badge => {
                badge.textContent = data.cart_count;
                badge.style.display = 'flex';
            });
            
            const modal = mdb.Modal.getInstance(document.getElementById('bookingModal'));
            modal.hide();
            
            // Redirect to cart
            if (confirm('Speaker added to booking cart! Go to cart now?')) {
                window.location.href = '<?php echo SITE_URL; ?>/cart.php';
            }
        } else {
            alert(data.message || 'Failed to add speaker to cart');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
