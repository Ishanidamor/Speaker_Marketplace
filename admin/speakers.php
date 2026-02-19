<?php
$pageTitle = 'Manage Speakers';
require_once '../config/config.php';
requireAdmin();

// Handle speaker actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $speakerId = (int)$_POST['speaker_id'];
        
        switch ($action) {
            case 'delete':
                // Soft delete the speaker
                executeQuery("UPDATE speakers SET status = 'deleted', updated_at = NOW() WHERE id = ?", 
                            [$speakerId], 'i');
                setFlashMessage('success', 'Speaker deleted successfully');
                redirect('speakers.php');
                break;
                
            case 'toggle_status':
                // Toggle status between active and inactive
                $currentStatus = fetchOne("SELECT status FROM speakers WHERE id = ?", 
                                       [$speakerId], 'i')['status'];
                $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
                
                executeQuery("UPDATE speakers SET status = ?, updated_at = NOW() WHERE id = ?", 
                            [$newStatus, $speakerId], 'si');
                
                setFlashMessage('success', 'Speaker status updated');
                redirect('speakers.php');
                break;
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT s.*, 
          GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories
          FROM speakers s
          LEFT JOIN speaker_categories sc ON s.id = sc.speaker_id
          LEFT JOIN categories c ON sc.category_id = c.id
          WHERE s.status != 'deleted'";
          
$countQuery = "SELECT COUNT(DISTINCT s.id) as total 
               FROM speakers s 
               LEFT JOIN speaker_categories sc ON s.id = sc.speaker_id 
               WHERE s.status != 'deleted'";

$params = [];
$types = '';

// Add search conditions
if ($search) {
    $query .= " AND (s.name LIKE ? OR s.bio LIKE ? OR s.company LIKE ?)";
    $countQuery .= " AND (s.name LIKE ? OR s.bio LIKE ? OR s.company LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

// Add category filter
if ($category > 0) {
    $query .= " AND sc.category_id = ?";
    $countQuery .= " AND sc.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

// Add status filter
if (in_array($status, ['active', 'inactive', 'pending'])) {
    $query .= " AND s.status = ?";
    $countQuery .= " AND s.status = ?";
    $params[] = $status;
    $types .= 's';
}

// Group by speaker
$query .= " GROUP BY s.id";

// Count total records
$totalRecords = fetchOne($countQuery, $params, $types)['total'];
$totalPages = ceil($totalRecords / $perPage);

// Add sorting and pagination
$query .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
$params = array_merge($params, [$perPage, $offset]);
$types .= 'ii';

// Get speakers
$speakers = fetchAll($query, $params, $types);

// Get categories for filter
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// Get speaker stats
$stats = fetchOne("
    SELECT 
        COUNT(*) as total_speakers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_speakers,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_speakers,
        (SELECT COUNT(DISTINCT speaker_id) FROM speaker_categories) as categorized_speakers
    FROM speakers 
    WHERE status != 'deleted'
");

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas fa-microphone-alt me-2"></i> Manage Speakers
            </h1>
            <a href="speaker-edit.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Speaker
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #4e73df;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Total Speakers</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_speakers']); ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-microphone-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #1cc88a;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Active Speakers</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['active_speakers']); ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f6c23e;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Pending Review</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['pending_speakers']); ?></h2>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #36b9cc;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Categorized</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['categorized_speakers']); ?></h2>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-tags fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </n

        <!-- Search and Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search speakers..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-end">
                        <a href="speakers.php" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Reset Filters">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Speakers Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Speaker</th>
                                <th>Expertise</th>
                                <th>Rates</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($speakers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">No speakers found</div>
                                        <?php if ($search || $category || $status): ?>
                                            <a href="speakers.php" class="btn btn-sm btn-outline-primary mt-2">
                                                Clear filters
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($speakers as $speaker): 
                                    $statusColors = [
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'pending' => 'warning'
                                    ];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-3">
                                                    <?php if (!empty($speaker['photo'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo htmlspecialchars($speaker['photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($speaker['name']); ?>" 
                                                             class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="avatar-initial bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <?php echo strtoupper(substr($speaker['name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-medium"><?php echo htmlspecialchars($speaker['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($speaker['title'] ?? 'No title'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($speaker['categories'])): ?>
                                                <div class="text-truncate" style="max-width: 200px;" 
                                                     data-bs-toggle="tooltip" data-bs-placement="top" 
                                                     title="<?php echo htmlspecialchars($speaker['categories']); ?>">
                                                    <?php echo htmlspecialchars($speaker['categories']); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No categories</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small>Keynote: <?php echo formatPrice($speaker['keynote_rate'] ?? 0); ?></small>
                                                <small>Workshop: <?php echo formatPrice($speaker['workshop_rate'] ?? 0); ?></small>
                                                <small>Virtual: <?php echo formatPrice($speaker['virtual_rate'] ?? 0); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusColors[$speaker['status']] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($speaker['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo timeAgo($speaker['updated_at']); ?>
                                            <div class="text-muted small">
                                                <?php echo date('M j, Y', strtotime($speaker['updated_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="speaker-view.php?id=<?php echo $speaker['id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="speaker-edit.php?id=<?php echo $speaker['id']; ?>">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-<?php echo $speaker['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                                           href="#" onclick="toggleStatus(<?php echo $speaker['id']; ?>, this)">
                                                            <i class="fas fa-<?php echo $speaker['status'] === 'active' ? 'pause' : 'check'; ?> me-2"></i>
                                                            <?php echo $speaker['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="confirmDelete(<?php echo $speaker['id']; ?>, '<?php echo htmlspecialchars(addslashes($speaker['name'])); ?>')">
                                                            <i class="fas fa-trash-alt me-2"></i>Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            $startPage = max(1, $endPage - 4);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post" id="deleteForm">
                <input type="hidden" name="speaker_id" id="deleteSpeakerId">
                <input type="hidden" name="action" value="delete">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the speaker "<span id="speakerName" class="fw-bold"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will mark the speaker as deleted and they will no longer be visible to users.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Delete Speaker
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Confirm speaker deletion
function confirmDelete(speakerId, speakerName) {
    document.getElementById('deleteSpeakerId').value = speakerId;
    document.getElementById('speakerName').textContent = speakerName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Toggle speaker status
function toggleStatus(speakerId, element) {
    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="speaker_id" value="${speakerId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Auto-submit forms when dropdown changes
document.querySelectorAll('select[onchange*="this.form.submit"]').forEach(select => {
    select.onchange = function() {
        this.form.submit();
    };
});
</script>
