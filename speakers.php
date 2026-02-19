<?php
$pageTitle = 'Find Speakers';
require_once 'includes/header.php';

// Get filters
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$format = isset($_GET['format']) ? sanitize($_GET['format']) : '';
$minRate = isset($_GET['min_rate']) ? (int)$_GET['min_rate'] : 0;
$maxRate = isset($_GET['max_rate']) ? (int)$_GET['max_rate'] : 10000;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'featured';

// Build query
$query = "SELECT s.*, c.name as category_name,
          (SELECT image_path FROM speaker_photos WHERE speaker_id = s.id ORDER BY display_order LIMIT 1) as image
          FROM speakers s 
          LEFT JOIN categories c ON s.category_id = c.id 
          WHERE s.status = 'active'";

$params = [];
$types = '';

if ($category) {
    $query .= " AND c.slug = ?";
    $params[] = $category;
    $types .= 's';
}

if ($search) {
    $query .= " AND (s.name LIKE ? OR s.title LIKE ? OR s.bio LIKE ? OR s.expertise LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

if ($location) {
    $query .= " AND s.location LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

// Rate filter (using keynote_rate as base)
if ($minRate > 0 || $maxRate < 10000) {
    $query .= " AND s.keynote_rate BETWEEN ? AND ?";
    $params[] = $minRate;
    $params[] = $maxRate;
    $types .= 'ii';
}

// Sorting
switch ($sort) {
    case 'rating':
        $query .= " ORDER BY s.rating DESC, s.total_reviews DESC";
        break;
    case 'experience':
        $query .= " ORDER BY s.years_experience DESC, s.total_events DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY s.keynote_rate ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY s.keynote_rate DESC";
        break;
    case 'popular':
        $query .= " ORDER BY s.total_events DESC";
        break;
    case 'featured':
    default:
        $query .= " ORDER BY s.featured DESC, s.rating DESC, s.total_events DESC";
}

$speakers = fetchAll($query, $params, $types);
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$eventFormats = fetchAll("SELECT * FROM event_formats ORDER BY name");
?>

<div class="main-content">
    <div class="container">
        <!-- Page Header -->
        <div class="mb-4">
            <h1 class="fw-bold mb-2">
                <?php echo $category ? ucwords(str_replace('-', ' ', $category)) : 'Find Professional Speakers'; ?>
            </h1>
            <p class="text-muted">
                <?php echo count($speakers); ?> speaker<?php echo count($speakers) != 1 ? 's' : ''; ?> available
            </p>
        </div>
        
        <!-- Advanced Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-search me-1"></i> Search
                        </label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Name, expertise, keywords..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-tag me-1"></i> Expertise
                        </label>
                        <select name="category" class="form-select">
                            <option value="">All Expertise</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>" 
                                        <?php echo $category == $cat['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-map-marker-alt me-1"></i> Location
                        </label>
                        <input type="text" 
                               name="location" 
                               class="form-control" 
                               placeholder="City, State, Country"
                               value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-dollar-sign me-1"></i> Budget Range
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="min_rate" class="form-control" placeholder="Min" 
                                   value="<?php echo $minRate > 0 ? $minRate : ''; ?>">
                            <span class="input-group-text">-</span>
                            <input type="number" name="max_rate" class="form-control" placeholder="Max"
                                   value="<?php echo $maxRate < 10000 ? $maxRate : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sort me-1"></i> Sort By
                        </label>
                        <select name="sort" class="form-select">
                            <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>Featured</option>
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="experience" <?php echo $sort == 'experience' ? 'selected' : ''; ?>>Most Experienced</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Booked</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Speakers Grid -->
        <?php if (count($speakers) > 0): ?>
            <div class="row g-4">
                <?php foreach ($speakers as $speaker): ?>
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
                            
                            <?php if ($speaker['featured']): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($speaker['category_name'] ?? 'Speaker'); ?>
                                    </span>
                                    <?php if ($speaker['rating'] > 0): ?>
                                        <div class="text-warning">
                                            <i class="fas fa-star"></i> <?php echo number_format($speaker['rating'], 1); ?>
                                        </div>
                                    <?php endif; ?>
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
                                    <i class="fas fa-briefcase me-1"></i> <?php echo $speaker['years_experience']; ?> years experience<br>
                                    <i class="fas fa-calendar-check me-1"></i> <?php echo $speaker['total_events']; ?> events
                                </p>
                                
                                <div class="mt-auto">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Starting from</small>
                                        <h5 class="text-primary mb-0 fw-bold">
                                            <?php 
                                            $minRate = min(
                                                $speaker['keynote_rate'] ?? 9999,
                                                $speaker['workshop_rate'] ?? 9999,
                                                $speaker['virtual_rate'] ?? 9999
                                            );
                                            echo formatPrice($minRate); 
                                            ?>
                                        </h5>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $speaker['slug']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-info-circle me-1"></i> View Profile
                                        </a>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="quickBooking(<?php echo $speaker['id']; ?>, '<?php echo htmlspecialchars($speaker['name']); ?>')">
                                            <i class="fas fa-calendar-plus me-1"></i> Request Booking
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h3>No Speakers Found</h3>
                <p class="text-muted">Try adjusting your filters or search terms</p>
                <a href="<?php echo SITE_URL; ?>/speakers.php" class="btn btn-primary">
                    View All Speakers
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Booking Modal -->
<div class="modal fade" id="quickBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check me-2"></i> Quick Booking Request
                </h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Select event format to add <strong id="speakerName"></strong> to your booking cart:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="addToCart('keynote')">
                        <i class="fas fa-microphone me-2"></i> Keynote Speech
                    </button>
                    <button class="btn btn-outline-success" onclick="addToCart('workshop')">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Workshop
                    </button>
                    <button class="btn btn-outline-info" onclick="addToCart('virtual')">
                        <i class="fas fa-video me-2"></i> Virtual Event
                    </button>
                    <button class="btn btn-outline-warning" onclick="addToCart('panel')">
                        <i class="fas fa-users me-2"></i> Panel Discussion
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSpeakerId = null;

function quickBooking(speakerId, speakerName) {
    currentSpeakerId = speakerId;
    document.getElementById('speakerName').textContent = speakerName;
    const modal = new mdb.Modal(document.getElementById('quickBookingModal'));
    modal.show();
}

function addToCart(format) {
    if (!currentSpeakerId) return;
    
    fetch('<?php echo SITE_URL; ?>/ajax/add-to-booking-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `speaker_id=${currentSpeakerId}&format=${format}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            const badges = document.querySelectorAll('.cart-badge, .bottom-nav-item .badge');
            badges.forEach(badge => {
                badge.textContent = data.cart_count;
                badge.style.display = 'flex';
            });
            
            // Close modal and show success
            const modal = mdb.Modal.getInstance(document.getElementById('quickBookingModal'));
            modal.hide();
            
            alert('Speaker added to booking cart!');
        } else {
            alert(data.message || 'Failed to add speaker to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
