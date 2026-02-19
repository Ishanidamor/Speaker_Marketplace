<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

// Fetch featured speakers
$featuredSpeakers = fetchAll("SELECT s.*, c.name as category_name,
                               (SELECT image_path FROM speaker_photos WHERE speaker_id = s.id ORDER BY display_order LIMIT 1) as image
                               FROM speakers s 
                               LEFT JOIN categories c ON s.category_id = c.id 
                               WHERE s.status = 'active' AND s.featured = TRUE
                               ORDER BY s.rating DESC, s.total_events DESC 
                               LIMIT 8");

// Fetch categories
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// Fetch testimonials (sample data)
$testimonials = [
    ['name' => 'Sarah Mitchell', 'organization' => 'Tech Summit 2024', 'rating' => 5, 'comment' => 'Found the perfect keynote speaker for our conference. The booking process was seamless and professional.'],
    ['name' => 'David Chen', 'organization' => 'Corporate Training Inc', 'rating' => 5, 'comment' => 'Excellent platform! Booked multiple speakers for our workshops. Highly recommend for event organizers.'],
    ['name' => 'Emily Rodriguez', 'organization' => 'Innovation Forum', 'rating' => 5, 'comment' => 'The variety of speakers and transparent pricing made our decision easy. Will definitely use again!']
];

// Stats
$totalSpeakers = fetchOne("SELECT COUNT(*) as count FROM speakers WHERE status = 'active'")['count'];
$totalEvents = fetchOne("SELECT SUM(total_events) as total FROM speakers")['total'] ?? 0;
$avgRating = fetchOne("SELECT AVG(rating) as avg FROM speakers WHERE rating > 0")['avg'] ?? 0;
?>

<div class="main-content">
    <!-- Hero Section -->
    <section class="hero-section fade-in">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Find the Perfect Speaker for Your Event</h1>
            <p class="lead mb-4">Connect with world-class speakers for conferences, workshops, webinars, and corporate events</p>
            <div class="row g-3 justify-content-center mb-4">
                <div class="col-md-4">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search speakers by name or expertise..." id="heroSearch">
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-search me-2"></i> Search
                    </a>
                </div>
            </div>
            <div class="d-flex justify-content-center gap-4 text-white">
                <span><i class="fas fa-users me-2"></i> <?php echo $totalSpeakers; ?>+ Speakers</span>
                <span><i class="fas fa-calendar-check me-2"></i> <?php echo number_format($totalEvents); ?>+ Events</span>
                <span><i class="fas fa-star me-2"></i> <?php echo number_format($avgRating, 1); ?> Avg Rating</span>
            </div>
        </div>
    </section>
    
    <!-- How It Works -->
    <section class="container my-5">
        <h2 class="text-center fw-bold mb-5">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                </div>
                <h5 class="fw-bold">1. Search & Filter</h5>
                <p class="text-muted">Browse our directory of verified speakers. Filter by expertise, location, budget, and event format.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
                <h5 class="fw-bold">2. Review Profiles</h5>
                <p class="text-muted">View detailed speaker profiles, watch intro videos, read reviews, and check availability.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                    </div>
                </div>
                <h5 class="fw-bold">3. Send Booking Request</h5>
                <p class="text-muted">Submit your event details and booking request. Speakers respond within 24-48 hours.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
                <h5 class="fw-bold">4. Confirm & Pay</h5>
                <p class="text-muted">Once confirmed, complete secure payment and receive booking confirmation instantly.</p>
            </div>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="container my-5">
        <h2 class="text-center fw-bold mb-4">Browse by Expertise</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-md-3">
                    <a href="<?php echo SITE_URL; ?>/speakers.php?category=<?php echo $category['slug']; ?>" 
                       class="text-decoration-none">
                        <div class="card text-center p-4 h-100 product-card">
                            <div class="mb-3">
                                <i class="fas <?php echo $category['icon'] ?? 'fa-microphone-alt'; ?> fa-3x text-primary"></i>
                            </div>
                            <h6 class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Featured Speakers Section -->
    <section class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Featured Speakers</h2>
            <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featuredSpeakers as $speaker): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card product-card h-100">
                        <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $speaker['slug']; ?>">
                            <?php if ($speaker['image']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo $speaker['image']; ?>" 
                                     class="product-image" 
                                     alt="<?php echo htmlspecialchars($speaker['name']); ?>">
                            <?php else: ?>
                                <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($speaker['category_name'] ?? 'Speaker'); ?></span>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i> <?php echo number_format($speaker['rating'], 1); ?>
                                </div>
                            </div>
                            
                            <h5 class="card-title mb-2">
                                <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $speaker['slug']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($speaker['name']); ?>
                                </a>
                            </h5>
                            
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($speaker['title']); ?></p>
                            
                            <p class="card-text text-muted small mb-3 flex-grow-1">
                                <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($speaker['location']); ?><br>
                                <i class="fas fa-calendar-check me-1"></i> <?php echo $speaker['total_events']; ?> events
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Starting from</small>
                                    <h5 class="text-primary mb-0 fw-bold">
                                        <?php echo formatPrice($speaker['virtual_rate'] ?? $speaker['keynote_rate']); ?>
                                    </h5>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $speaker['slug']; ?>" 
                                   class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-info-circle me-1"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Event Types -->
    <section class="py-5" style="background-color: rgba(25, 118, 210, 0.05);">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Perfect for Any Event Type</h2>
            <div class="row g-4 text-center">
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="fas fa-microphone fa-3x text-primary mb-3"></i>
                        <h5 class="fw-bold">Keynote Speeches</h5>
                        <p class="text-muted small">Inspiring main stage presentations</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                        <h5 class="fw-bold">Workshops</h5>
                        <p class="text-muted small">Interactive training sessions</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="fas fa-video fa-3x text-info mb-3"></i>
                        <h5 class="fw-bold">Virtual Events</h5>
                        <p class="text-muted small">Online webinars & presentations</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <h5 class="fw-bold">Panel Discussions</h5>
                        <p class="text-muted small">Expert panel moderation</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials Section -->
    <section class="container my-5">
        <h2 class="text-center fw-bold mb-5">What Event Organizers Say</h2>
        <div class="row g-4">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="mb-3">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i < $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-3">"<?php echo htmlspecialchars($testimonial['comment']); ?>"</p>
                        <div class="d-flex align-items-center mt-auto">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($testimonial['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($testimonial['organization']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="container my-5 text-center">
        <div class="card p-5" style="background: linear-gradient(135deg, var(--primary-color), #1565c0); color: white;">
            <h2 class="fw-bold mb-3">Ready to Book Your Speaker?</h2>
            <p class="lead mb-4">Join thousands of successful events powered by our speaker marketplace</p>
            <div>
                <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-search me-2"></i> Browse Speakers
                </a>
                <a href="<?php echo SITE_URL; ?>/signup.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-user-plus me-2"></i> Sign Up Free
                </a>
            </div>
        </div>
    </section>
</div>

<script>
document.getElementById('heroSearch')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const query = this.value;
        window.location.href = '<?php echo SITE_URL; ?>/speakers.php?search=' + encodeURIComponent(query);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
