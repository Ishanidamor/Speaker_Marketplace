<?php
$pageTitle = 'Edit Speaker';
require_once '../config/config.php';
requireAdmin();

$speakerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = ($speakerId > 0);

// Initialize speaker data
$speaker = [
    'id' => 0,
    'name' => '',
    'title' => '',
    'bio' => '',
    'company' => '',
    'email' => '',
    'phone' => '',
    'website' => '',
    'facebook' => '',
    'twitter' => '',
    'linkedin' => '',
    'instagram' => '',
    'youtube' => '',
    'keynote_rate' => '',
    'workshop_rate' => '',
    'virtual_rate' => '',
    'travel_preferences' => '',
    'languages' => '',
    'status' => 'pending',
    'featured' => 0,
    'meta_title' => '',
    'meta_description' => '',
    'slug' => ''
];

$categories = [];
$allCategories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $errors = [];
    
    // Required fields
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $errors[] = 'Speaker name is required';
    }
    
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $errors[] = 'Valid email is required';
    }
    
    // Check if email is already taken by another speaker
    $emailCheck = fetchOne(
        "SELECT id FROM speakers WHERE email = ? AND id != ?", 
        [$email, $speakerId], 
        'si'
    );
    
    if ($emailCheck) {
        $errors[] = 'This email is already in use by another speaker';
    }
    
    // Process rates
    $keynoteRate = (float)str_replace([',', '$'], '', $_POST['keynote_rate'] ?? '0');
    $workshopRate = (float)str_replace([',', '$'], '', $_POST['workshop_rate'] ?? '0');
    $virtualRate = (float)str_replace([',', '$'], '', $_POST['virtual_rate'] ?? '0');
    
    // If no errors, save the speaker
    if (empty($errors)) {
        $slug = createSlug($name);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive', 'pending']) ? $_POST['status'] : 'pending';
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $speakerData = [
            'name' => $name,
            'title' => trim($_POST['title'] ?? ''),
            'bio' => trim($_POST['bio'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'email' => $email,
            'phone' => trim($_POST['phone'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'facebook' => trim($_POST['facebook'] ?? ''),
            'twitter' => trim($_POST['twitter'] ?? ''),
            'linkedin' => trim($_POST['linkedin'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'youtube' => trim($_POST['youtube'] ?? ''),
            'keynote_rate' => $keynoteRate,
            'workshop_rate' => $workshopRate,
            'virtual_rate' => $virtualRate,
            'travel_preferences' => trim($_POST['travel_preferences'] ?? ''),
            'languages' => trim($_POST['languages'] ?? ''),
            'status' => $status,
            'featured' => $featured,
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'slug' => $slug,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle file upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/speakers/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $fileName = 'speaker-' . uniqid() . '.' . $fileExt;
            $targetFile = $uploadDir . $fileName;
            
            // Check if image file is an actual image
            $check = getimagesize($_FILES['photo']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                    // Delete old photo if exists
                    if (!empty($speaker['photo']) && file_exists($uploadDir . $speaker['photo'])) {
                        @unlink($uploadDir . $speaker['photo']);
                    }
                    $speakerData['photo'] = $fileName;
                }
            }
        }
        
        if ($isEdit) {
            // Update existing speaker
            $placeholders = [];
            $values = [];
            $types = '';
            
            foreach ($speakerData as $key => $value) {
                $placeholders[] = "$key = ?";
                $values[] = $value;
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
            }
            
            $values[] = $speakerId;
            $types .= 'i';
            
            $query = "UPDATE speakers SET " . implode(', ', $placeholders) . " WHERE id = ?";
            executeQuery($query, $values, $types);
            
            $message = 'Speaker updated successfully';
        } else {
            // Insert new speaker
            $speakerData['created_at'] = date('Y-m-d H:i:s');
            
            $placeholders = [];
            $values = [];
            $types = '';
            
            foreach ($speakerData as $value) {
                $values[] = $value;
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
            }
            
            $fields = implode(', ', array_keys($speakerData));
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            
            $query = "INSERT INTO speakers ($fields) VALUES ($placeholders)";
            $speakerId = executeQuery($query, $values, $types, true);
            
            $message = 'Speaker added successfully';
            $isEdit = true; // For the redirect
        }
        
        // Handle categories
        if ($isEdit) {
            // Remove existing categories
            executeQuery("DELETE FROM speaker_categories WHERE speaker_id = ?", [$speakerId], 'i');
            
            // Add selected categories
            if (!empty($_POST['categories']) && is_array($_POST['categories'])) {
                $values = [];
                $placeholders = [];
                $types = '';
                
                foreach ($_POST['categories'] as $categoryId) {
                    $categoryId = (int)$categoryId;
                    if ($categoryId > 0) {
                        $values = array_merge($values, [$speakerId, $categoryId]);
                        $placeholders[] = '(?, ?)';
                        $types .= 'ii';
                    }
                }
                
                if (!empty($values)) {
                    $query = "INSERT INTO speaker_categories (speaker_id, category_id) VALUES " . implode(', ', $placeholders);
                    executeQuery($query, $values, $types);
                }
            }
            
            setFlashMessage('success', $message);
            
            // Handle save and continue or save and return
            if (isset($_POST['save_and_continue'])) {
                // Stay on the edit page
                $speaker = array_merge($speaker, $speakerData);
                $speaker['id'] = $speakerId;
                
                // Get selected categories
                $categories = [];
                if (!empty($_POST['categories']) && is_array($_POST['categories'])) {
                    $categories = array_map('intval', $_POST['categories']);
                }
            } else {
                // Redirect to speakers list
                redirect('speakers.php');
            }
        }
    } else {
        // Show errors
        foreach ($errors as $error) {
            setFlashMessage('error', $error);
        }
        
        // Repopulate form data
        $speaker = array_merge($speaker, $_POST);
        if (!empty($_POST['categories']) && is_array($_POST['categories'])) {
            $categories = array_map('intval', $_POST['categories']);
        }
    }
} elseif ($isEdit) {
    // Load speaker data for editing
    $speaker = fetchOne("SELECT * FROM speakers WHERE id = ?", [$speakerId], 'i');
    
    if (!$speaker) {
        setFlashMessage('error', 'Speaker not found');
        redirect('speakers.php');
    }
    
    // Load speaker categories
    $categoryResults = fetchAll(
        "SELECT category_id FROM speaker_categories WHERE speaker_id = ?", 
        [$speakerId], 
        'i'
    );
    
    $categories = array_column($categoryResults, 'category_id');
}

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas <?php echo $isEdit ? 'fa-edit' : 'fa-plus'; ?> me-2"></i>
                <?php echo $isEdit ? 'Edit Speaker' : 'Add New Speaker'; ?>
            </h1>
            <div>
                <a href="speakers.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Speakers
                </a>
                <?php if ($isEdit): ?>
                    <a href="speaker-view.php?id=<?php echo $speakerId; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-1"></i> View
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php displayFlashMessages(); ?>

        <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($speaker['name']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide the speaker's full name.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Professional Title</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($speaker['title']); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="bio" class="form-label">Biography</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="5"><?php 
                                        echo htmlspecialchars($speaker['bio']); 
                                    ?></textarea>
                                    <div class="form-text">
                                        A detailed biography about the speaker's background and expertise.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="company" class="form-label">Company/Organization</label>
                                    <input type="text" class="form-control" id="company" name="company" 
                                           value="<?php echo htmlspecialchars($speaker['company']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="languages" class="form-label">Languages Spoken</label>
                                    <input type="text" class="form-control" id="languages" name="languages" 
                                           value="<?php echo htmlspecialchars($speaker['languages']); ?>"
                                           placeholder="e.g., English, Spanish, French">
                                    <div class="form-text">
                                        Separate multiple languages with commas.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Speaking Engagements</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="keynote_rate" class="form-label">Keynote Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control text-end" id="keynote_rate" name="keynote_rate" 
                                               value="<?php echo number_format($speaker['keynote_rate'], 2); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="workshop_rate" class="form-label">Workshop Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control text-end" id="workshop_rate" name="workshop_rate" 
                                               value="<?php echo number_format($speaker['workshop_rate'], 2); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="virtual_rate" class="form-label">Virtual Rate</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control text-end" id="virtual_rate" name="virtual_rate" 
                                               value="<?php echo number_format($speaker['virtual_rate'], 2); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="travel_preferences" class="form-label">Travel Preferences</label>
                                    <textarea class="form-control" id="travel_preferences" name="travel_preferences" rows="3"><?php 
                                        echo htmlspecialchars($speaker['travel_preferences']); 
                                    ?></textarea>
                                    <div class="form-text">
                                        Any travel preferences, restrictions, or requirements the speaker has.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($speaker['email']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid email address.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($speaker['phone']); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" 
                                           value="<?php echo htmlspecialchars($speaker['website']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Social Media</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="linkedin" class="form-label">LinkedIn</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                        <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                               value="<?php echo htmlspecialchars($speaker['linkedin']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="twitter" class="form-label">Twitter</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                        <input type="text" class="form-control" id="twitter" name="twitter" 
                                               value="<?php echo htmlspecialchars($speaker['twitter']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="facebook" class="form-label">Facebook</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                        <input type="url" class="form-control" id="facebook" name="facebook" 
                                               value="<?php echo htmlspecialchars($speaker['facebook']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="instagram" class="form-label">Instagram</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                        <input type="text" class="form-control" id="instagram" name="instagram" 
                                               value="<?php echo htmlspecialchars($speaker['instagram']); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="youtube" class="form-label">YouTube</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                        <input type="url" class="form-control" id="youtube" name="youtube" 
                                               value="<?php echo htmlspecialchars($speaker['youtube']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Profile Photo</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($speaker['photo'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/speakers/<?php echo htmlspecialchars($speaker['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($speaker['name']); ?>" 
                                         class="img-fluid rounded-circle mb-3" style="width: 200px; height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto" 
                                         style="width: 200px; height: 200px;">
                                        <i class="fas fa-user fa-5x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Upload New Photo</label>
                                    <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                                    <div class="form-text">
                                        Recommended size: 500x500px, Max size: 2MB
                                    </div>
                                </div>
                                
                                <?php if (!empty($speaker['photo'])): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" value="1" id="remove_photo" name="remove_photo">
                                        <label class="form-check-label" for="remove_photo">
                                            Remove current photo
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Categories</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($allCategories)): ?>
                                <div class="mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="categorySearch" placeholder="Search categories...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div style="max-height: 300px; overflow-y: auto;" class="mb-3">
                                    <?php foreach ($allCategories as $category): ?>
                                        <div class="form-check mb-2 category-item">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="categories[]" 
                                                   value="<?php echo $category['id']; ?>"
                                                   id="category_<?php echo $category['id']; ?>"
                                                   <?php echo in_array($category['id'], $categories) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-muted small">
                                    Select all categories that apply to this speaker.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    No categories found. <a href="categories.php">Create categories</a> first.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo $speaker['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $speaker['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="pending" <?php echo $speaker['status'] === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                </select>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="featured" name="featured" value="1"
                                       <?php echo $speaker['featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">Featured Speaker</label>
                                <div class="form-text">
                                    Featured speakers will be highlighted on the website.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="slug" class="form-label">URL Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" 
                                       value="<?php echo htmlspecialchars($speaker['slug']); ?>">
                                <div class="form-text">
                                    Leave blank to auto-generate from the name.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_title" class="form-label">Meta Title</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                       value="<?php echo htmlspecialchars($speaker['meta_title']); ?>">
                                <div class="form-text">
                                    If empty, the speaker's name will be used.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" 
                                          rows="3"><?php echo htmlspecialchars($speaker['meta_description']); ?></textarea>
                                <div class="form-text">
                                    A brief description for search engines (max 160 characters).
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="save" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                        <button type="submit" name="save_and_continue" class="btn btn-outline-primary">
                            <i class="fas fa-sync me-2"></i> Save and Continue Editing
                        </button>
                        <a href="speakers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                        <?php if ($isEdit): ?>
                            <button type="button" class="btn btn-outline-danger mt-3" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash-alt me-2"></i> Delete Speaker
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($isEdit): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="speaker-delete.php" method="post">
                <input type="hidden" name="id" value="<?php echo $speakerId; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the speaker "<strong><?php echo htmlspecialchars($speaker['name']); ?></strong>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The speaker will be marked as deleted and will no longer be visible to users.
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
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<script>
// Form validation
(function () {
    'use strict';
    
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();

// Format currency inputs
function formatCurrency(input) {
    // Remove non-numeric characters
    let value = input.value.replace(/[^0-9.]/g, '');
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Format as currency
    if (value) {
        const num = parseFloat(value);
        if (!isNaN(num)) {
            input.value = num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return;
        }
    }
    
    input.value = value;
}

// Apply currency formatting on blur
document.addEventListener('DOMContentLoaded', function() {
    // Format currency inputs
    const currencyInputs = document.querySelectorAll('input[id$="_rate"]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            formatCurrency(this);
        });
    });
    
    // Category search
    const categorySearch = document.getElementById('categorySearch');
    const clearSearch = document.getElementById('clearSearch');
    const categoryItems = document.querySelectorAll('.category-item');
    
    if (categorySearch) {
        categorySearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            categoryItems.forEach(item => {
                const label = item.querySelector('label').textContent.toLowerCase();
                if (label.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        clearSearch.addEventListener('click', function() {
            categorySearch.value = '';
            categoryItems.forEach(item => {
                item.style.display = 'block';
            });
        });
    }
    
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('blur', function() {
            if (!slugInput.value) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove special chars
                    .replace(/\s+/g, '-')      // Replace spaces with -
                    .replace(/--+/g, '-')       // Replace multiple - with single -
                    .trim();
                slugInput.value = slug;
            }
        });
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
