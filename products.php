<?php
$pageTitle = 'Products';
require_once 'includes/header.php';

// Get filters
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$minRate = isset($_GET['min_rate']) ? (int)$_GET['min_rate'] : 0;
$maxRate = isset($_GET['max_rate']) ? (int)$_GET['max_rate'] : 10000;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'featured';

// Get categories for filter
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

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
    case 'name':
        $query .= " ORDER BY s.name ASC";
        break;
    case 'rate_low':
        $query .= " ORDER BY s.keynote_rate ASC";
        break;
    case 'rate_high':
        $query .= " ORDER BY s.keynote_rate DESC";
        break;
    case 'rating':
        $query .= " ORDER BY s.rating DESC, s.total_reviews DESC";
        break;
    case 'popular':
        $query .= " ORDER BY s.bookings DESC, s.views DESC";
        break;
    default: // featured
        $query .= " ORDER BY s.featured DESC, s.rating DESC, s.total_reviews DESC";
}

$speakers = fetchAll($query, $params, $types);
?>

<div class="main-content">
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section mb-5">
            <div class="container">
                <h1 class="fw-bold mb-3">
                    <i class="fas fa-box-open me-2"></i> Speaker Products
                </h1>
                <p class="lead mb-4">Discover our premium speaker services and booking packages</p>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-lg" 
                                   placeholder="Search speakers, topics, or expertise..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-light btn-lg">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>" 
                                        <?php echo $category == $cat['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" 
                               placeholder="City, State" value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Min Rate ($)</label>
                        <input type="number" name="min_rate" class="form-control" 
                               min="0" step="100" value="<?php echo $minRate > 0 ? $minRate : ''; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Max Rate ($)</label>
                        <input type="number" name="max_rate" class="form-control" 
                               min="0" step="100" value="<?php echo $maxRate < 10000 ? $maxRate : ''; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>Featured</option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="rate_low" <?php echo $sort == 'rate_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="rate_high" <?php echo $sort == 'rate_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                <?php echo count($speakers); ?> Products Found
                <?php if ($search || $category || $location): ?>
                    <small class="text-muted">
                        <?php if ($search): ?>for "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                        <?php if ($category): ?>in <?php echo htmlspecialchars($category); ?><?php endif; ?>
                        <?php if ($location): ?>near <?php echo htmlspecialchars($location); ?><?php endif; ?>
                    </small>
                <?php endif; ?>
            </h5>
            
            <?php if ($search || $category || $location || $minRate > 0 || $maxRate < 10000): ?>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>

        <!-- Products Grid -->
        <?php if (count($speakers) > 0): ?>
            <div class="row g-4">
                <?php foreach ($speakers as $speaker): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card product-card h-100 shadow-sm">
                            <?php if ($speaker['featured']): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="position-relative">
                                <?php if ($speaker['image']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo $speaker['image']; ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($speaker['name']); ?>">
                                <?php else: ?>
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-absolute bottom-0 start-0 m-2">
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($speaker['category_name'] ?: 'General'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-2">
                                    <?php echo htmlspecialchars($speaker['name']); ?>
                                </h5>
                                
                                <p class="text-muted mb-2">
                                    <?php echo htmlspecialchars($speaker['title']); ?>
                                </p>
                                
                                <p class="card-text flex-grow-1">
                                    <?php echo htmlspecialchars(substr($speaker['bio'], 0, 120)); ?>
                                    <?php if (strlen($speaker['bio']) > 120): ?>...<?php endif; ?>
                                </p>
                                
                                <div class="mb-3">
                                    <div class="row g-2 text-sm">
                                        <div class="col-6">
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            <small><?php echo htmlspecialchars($speaker['location']); ?></small>
                                        </div>
                                        <div class="col-6">
                                            <i class="fas fa-calendar text-muted me-1"></i>
                                            <small><?php echo $speaker['years_experience']; ?>+ years</small>
                                        </div>
                                        <div class="col-6">
                                            <i class="fas fa-star text-warning me-1"></i>
                                            <small><?php echo number_format($speaker['rating'], 1); ?> (<?php echo $speaker['total_reviews']; ?>)</small>
                                        </div>
                                        <div class="col-6">
                                            <i class="fas fa-eye text-muted me-1"></i>
                                            <small><?php echo number_format($speaker['views']); ?> views</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="pricing mb-3">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted">Keynote</small>
                                            <div class="fw-bold text-success">$<?php echo number_format($speaker['keynote_rate']); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Virtual</small>
                                            <div class="fw-bold text-info">$<?php echo number_format($speaker['virtual_rate']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="<?php echo SITE_URL; ?>/speaker.php?slug=<?php echo $speaker['slug']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>View Product
                                    </a>
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="addToCart(<?php echo $speaker['id']; ?>)">
                                        <i class="fas fa-calendar-plus me-1"></i>Quick Book
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Load More / Pagination could go here -->
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="card border-0">
                    <div class="card-body py-5">
                        <i class="fas fa-search text-muted mb-3" style="font-size: 4rem;"></i>
                        <h3 class="fw-bold mb-3">No Products Found</h3>
                        <p class="text-muted mb-4">
                            <?php if ($search || $category || $location): ?>
                                Try adjusting your search criteria or browse all available speakers.
                            <?php else: ?>
                                No speaker products are currently available.
                            <?php endif; ?>
                        </p>
                        <?php if ($search || $category || $location): ?>
                            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>View All Products
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function addToCart(speakerId) {
    // Quick add to cart functionality
    fetch('<?php echo SITE_URL; ?>/api/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            speaker_id: speakerId,
            format: 'keynote' // default format
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            updateCartCount();
            // Show success message
            showToast('Speaker added to booking cart!', 'success');
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while adding to cart.', 'error');
    });
}

function updateCartCount() {
    // Update cart badge in navigation
    fetch('<?php echo SITE_URL; ?>/api/cart-count.php')
    .then(response => response.json())
    .then(data => {
        const badges = document.querySelectorAll('.cart-badge, .badge');
        badges.forEach(badge => {
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
    });
}

function showToast(message, type) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
