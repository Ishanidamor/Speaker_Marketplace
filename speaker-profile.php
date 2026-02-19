<?php
require_once 'config/config.php';
requireSpeakerLogin();

$currentSpeaker = getCurrentSpeaker();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $title = sanitize($_POST['title']);
    $bio = sanitize($_POST['bio']);
    $location = sanitize($_POST['location']);
    $keynoteRate = (float)$_POST['keynote_rate'];
    $workshopRate = (float)$_POST['workshop_rate'];
    $virtualRate = (float)$_POST['virtual_rate'];
    
    if (empty($name) || empty($title)) {
        $error = 'Name and title are required';
    } else {
        try {
            executeQuery(
                "UPDATE speakers SET name = ?, title = ?, bio = ?, location = ?, keynote_rate = ?, workshop_rate = ?, virtual_rate = ? WHERE id = ?",
                [$name, $title, $bio, $location, $keynoteRate, $workshopRate, $virtualRate, $currentSpeaker['id']],
                'ssssdddi'
            );
            
            $success = 'Profile updated successfully!';
            $currentSpeaker = getCurrentSpeaker(); // Refresh data
        } catch (Exception $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

$pageTitle = 'Speaker Profile';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        Edit Speaker Profile
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-bold">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($currentSpeaker['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label fw-bold">Professional Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($currentSpeaker['title']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label fw-bold">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($currentSpeaker['bio']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label fw-bold">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($currentSpeaker['location']); ?>">
                        </div>
                        
                        <h5 class="fw-bold mb-3 text-primary">Speaking Rates (USD)</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="keynote_rate" class="form-label fw-bold">Keynote Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="keynote_rate" name="keynote_rate" 
                                           value="<?php echo $currentSpeaker['keynote_rate']; ?>" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="workshop_rate" class="form-label fw-bold">Workshop Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="workshop_rate" name="workshop_rate" 
                                           value="<?php echo $currentSpeaker['workshop_rate']; ?>" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="virtual_rate" class="form-label fw-bold">Virtual Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="virtual_rate" name="virtual_rate" 
                                           value="<?php echo $currentSpeaker['virtual_rate']; ?>" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Update Profile
                            </button>
                            <a href="<?php echo SITE_URL; ?>/speaker-dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
