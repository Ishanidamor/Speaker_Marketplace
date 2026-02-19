<?php
$pageTitle = 'Manage Categories';
require_once '../config/config.php';
requireAdmin();

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['name']);
        $slug = generateSlug($name);
        $description = trim($_POST['description']);
        $status = 'active'; // Default status
        
        // Validate
        if (empty($name)) {
            setFlashMessage('error', 'Category name is required');
        } else {
            // Check if category exists
            $exists = fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug], 's');
            
            if ($exists) {
                setFlashMessage('error', 'A category with this name already exists');
            } else {
                // Insert new category
                executeQuery(
                    "INSERT INTO categories (name, slug, description, status, created_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    [$name, $slug, $description, $status],
                    'ssss'
                );
                
                setFlashMessage('success', 'Category added successfully');
                redirect('categories.php');
            }
        }
    } 
    elseif (isset($_POST['update_category'])) {
        // Update existing category
        $id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $slug = generateSlug($name);
        $description = trim($_POST['description']);
        $status = $_POST['status'];
        
        if (empty($name)) {
            setFlashMessage('error', 'Category name is required');
        } else {
            // Check if another category has the same slug
            $exists = fetchOne(
                "SELECT id FROM categories WHERE slug = ? AND id != ?", 
                [$slug, $id], 
                'si'
            );
            
            if ($exists) {
                setFlashMessage('error', 'A category with this name already exists');
            } else {
                // Update category
                executeQuery(
                    "UPDATE categories 
                     SET name = ?, slug = ?, description = ?, status = ? 
                     WHERE id = ?",
                    [$name, $slug, $description, $status, $id],
                    'ssssi'
                );
                
                setFlashMessage('success', 'Category updated successfully');
                redirect('categories.php');
            }
        }
    }
    elseif (isset($_POST['delete_category'])) {
        // Delete category
        $id = (int)$_POST['category_id'];
        
        // Check if category is in use
        $inUse = fetchOne(
            "SELECT COUNT(*) as count FROM speaker_categories WHERE category_id = ?", 
            [$id], 
            'i'
        )['count'];
        
        if ($inUse > 0) {
            setFlashMessage('error', 'Cannot delete category: It is currently in use by speakers');
        } else {
            // Delete category
            executeQuery("DELETE FROM categories WHERE id = ?", [$id], 'i');
            setFlashMessage('success', 'Category deleted successfully');
        }
        
        redirect('categories.php');
    }
}

// Get all categories with counts
$categories = fetchAll(
    "SELECT c.*, 
     (SELECT COUNT(*) FROM speaker_categories WHERE category_id = c.id) as speaker_count,
     c.created_at as last_updated
     FROM categories c
     ORDER BY c.name"
);

// Get category for edit
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCategory = fetchOne("SELECT * FROM categories WHERE id = ?", [$editId], 'i');
    
    if (!$editCategory) {
        setFlashMessage('error', 'Category not found');
        redirect('categories.php');
    }
}

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="row">
            <!-- Add/Edit Category Form -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas <?php echo $editCategory ? 'fa-edit' : 'fa-plus'; ?> me-2"></i>
                            <?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <?php if ($editCategory): ?>
                                <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                    echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; 
                                ?></textarea>
                            </div>
                            
                            <?php if ($editCategory): ?>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $editCategory['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $editCategory['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <?php if ($editCategory): ?>
                                    <button type="submit" name="update_category" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Category
                                    </button>
                                    <a href="categories.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                <?php else: ?>
                                    <button type="submit" name="add_category" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Category
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Category Stats -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-chart-pie me-2"></i>Category Stats
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">Total Categories</div>
                            <div class="fw-bold"><?php echo count($categories); ?></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted">Active Categories</div>
                            <div class="fw-bold">
                                <?php 
                                    $activeCount = array_reduce($categories, function($carry, $item) {
                                        return $carry + ($item['status'] === 'active' ? 1 : 0);
                                    }, 0);
                                    echo $activeCount;
                                ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">Categories in Use</div>
                            <div class="fw-bold">
                                <?php 
                                    $inUseCount = array_reduce($categories, function($carry, $item) {
                                        return $carry + ($item['speaker_count'] > 0 ? 1 : 0);
                                    }, 0);
                                    echo $inUseCount;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Categories List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-tags me-2"></i> All Categories
                            </h5>
                            <div>
                                <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($categories)): ?>
                            <div class="text-center p-5">
                                <div class="text-muted mb-3">No categories found</div>
                                <a href="categories.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Your First Category
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Category</th>
                                            <th>Speakers</th>
                                            <th>Status</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($category['name']); ?></div>
                                                    <?php if (!empty($category['description'])): ?>
                                                        <div class="text-muted small text-truncate" style="max-width: 200px;" 
                                                             data-bs-toggle="tooltip" data-bs-placement="top" 
                                                             title="<?php echo htmlspecialchars($category['description']); ?>">
                                                            <?php echo htmlspecialchars($category['description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $category['speaker_count']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($category['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $lastUpdated = $category['last_updated'] ?? 'now';
                                                    echo timeAgo($lastUpdated); 
                                                    ?>
                                                    <div class="text-muted small">
                                                        <?php 
                                                        if (!empty($lastUpdated) && $lastUpdated !== '0000-00-00 00:00:00') {
                                                            echo date('M j, Y', strtotime($lastUpdated));
                                                        } else {
                                                            echo 'Never';
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?edit=<?php echo $category['id']; ?>#edit-form" 
                                                           class="btn btn-outline-primary"
                                                           data-bs-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger"
                                                                onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')"
                                                                data-bs-toggle="tooltip" title="Delete"
                                                                <?php echo $category['speaker_count'] > 0 ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post" id="deleteForm">
                <input type="hidden" name="category_id" id="deleteCategoryId">
                <input type="hidden" name="delete_category" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="categoryName" class="fw-bold"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Delete Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Confirm category deletion
function confirmDelete(categoryId, categoryName) {
    document.getElementById('deleteCategoryId').value = categoryId;
    document.getElementById('categoryName').textContent = categoryName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Scroll to edit form if editing
<?php if ($editCategory): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('name').focus();
        
        const editForm = document.querySelector('#edit-form');
        if (editForm) {
            editForm.scrollIntoView({ behavior: 'smooth' });
        }
    });
<?php endif; ?>
</script>
