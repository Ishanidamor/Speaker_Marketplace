<?php
require_once 'config/config.php';
requireSpeakerLogin();

$currentSpeaker = getCurrentSpeaker();

// Handle booking request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_item_id'])) {
        $action = $_POST['action'];
        $bookingItemId = (int)$_POST['booking_item_id'];
        $speakerNotes = isset($_POST['speaker_notes']) ? sanitize($_POST['speaker_notes']) : '';
        
        if ($action === 'accept' || $action === 'reject') {
            $status = $action === 'accept' ? 'accepted' : 'rejected';
            
            try {
                // Update booking item status
                executeQuery(
                    "UPDATE booking_items SET speaker_status = ?, speaker_notes = ? WHERE id = ? AND speaker_id = ?",
                    [$status, $speakerNotes, $bookingItemId, $currentSpeaker['id']],
                    'ssii'
                );
                
                // Get booking and organizer info for notification
                $bookingInfo = fetchOne(
                    "SELECT bi.*, b.organizer_id, b.booking_number, b.event_name, u.name as organizer_name, u.email as organizer_email 
                     FROM booking_items bi 
                     JOIN bookings b ON bi.booking_id = b.id 
                     JOIN users u ON b.organizer_id = u.id 
                     WHERE bi.id = ?",
                    [$bookingItemId], 'i'
                );
                
                if ($bookingInfo) {
                    // Create notification for organizer
                    $notificationTitle = "Speaker " . ucfirst($status) . " Your Request";
                    $notificationMessage = $currentSpeaker['name'] . " has " . $status . " your booking request for " . $bookingInfo['event_name'];
                    if ($speakerNotes) {
                        $notificationMessage .= ". Note: " . $speakerNotes;
                    }
                    
                    executeQuery(
                        "INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)",
                        [$bookingInfo['organizer_id'], 'booking_' . $status, $notificationTitle, $notificationMessage],
                        'isss'
                    );
                }
                
                setFlashMessage('success', 'Booking request ' . $status . ' successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to update booking request. Please try again.');
            }
        }
    }
}

// Get booking requests for this speaker
$bookingRequests = fetchAll(
    "SELECT bi.*, b.booking_number, b.event_name, b.event_date, b.event_time, b.event_type, 
            b.event_location, b.expected_attendees, b.event_description, 
            u.name as organizer_name, u.email as organizer_email, u.organization
     FROM booking_items bi 
     JOIN bookings b ON bi.booking_id = b.id 
     JOIN users u ON b.organizer_id = u.id 
     WHERE bi.speaker_id = ? 
     ORDER BY bi.created_at DESC",
    [$currentSpeaker['id']], 'i'
);

// Get statistics
$stats = [
    'total_requests' => count($bookingRequests),
    'pending_requests' => count(array_filter($bookingRequests, fn($r) => $r['speaker_status'] === 'pending')),
    'accepted_requests' => count(array_filter($bookingRequests, fn($r) => $r['speaker_status'] === 'accepted')),
    'rejected_requests' => count(array_filter($bookingRequests, fn($r) => $r['speaker_status'] === 'rejected'))
];

$pageTitle = 'Speaker Dashboard';
require_once 'includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-microphone-alt me-2"></i>
                        Speaker Panel
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#dashboard" class="list-group-item list-group-item-action active" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="#requests" class="list-group-item list-group-item-action" onclick="showSection('requests')">
                            <i class="fas fa-calendar-check me-2"></i> Booking Requests
                        </a>
                        <a href="<?php echo SITE_URL; ?>/speaker-profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-edit me-2"></i> Edit Profile
                        </a>
                        <a href="<?php echo SITE_URL; ?>/speaker-logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-gradient-primary text-white">
                            <div class="card-body">
                                <h2 class="mb-2">Welcome back, <?php echo htmlspecialchars($currentSpeaker['name']); ?>!</h2>
                                <p class="mb-0"><?php echo htmlspecialchars($currentSpeaker['title']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <h4 class="fw-bold"><?php echo $stats['total_requests']; ?></h4>
                                <p class="text-muted mb-0">Total Requests</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h4 class="fw-bold"><?php echo $stats['pending_requests']; ?></h4>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h4 class="fw-bold"><?php echo $stats['accepted_requests']; ?></h4>
                                <p class="text-muted mb-0">Accepted</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h4 class="fw-bold"><?php echo $stats['rejected_requests']; ?></h4>
                                <p class="text-muted mb-0">Rejected</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Requests Section -->
            <div id="requests-section" style="display: none;">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="fas fa-calendar-check me-2"></i>Booking Requests</h3>
                        <p class="text-muted">Manage your incoming booking requests from event organizers.</p>
                    </div>
                </div>
            </div>
            
            <!-- Booking Requests (shown in both sections) -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        <span class="dashboard-title">Recent Booking Requests</span>
                        <span class="requests-title" style="display: none;">All Booking Requests</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bookingRequests)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No booking requests yet</h5>
                            <p class="text-muted">When organizers book you for events, requests will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Organizer</th>
                                        <th>Date & Time</th>
                                        <th>Format</th>
                                        <th>Rate</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookingRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['event_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['booking_number']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['organizer_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['organization'] ?? $request['organizer_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($request['event_date'])); ?><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($request['event_time'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst($request['format']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo formatPrice($request['rate']); ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'accepted' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass[$request['speaker_status']]; ?>">
                                                    <?php echo ucfirst($request['speaker_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($request['speaker_status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success me-1" 
                                                            onclick="showActionModal(<?php echo $request['id']; ?>, 'accept', '<?php echo htmlspecialchars($request['event_name']); ?>')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="showActionModal(<?php echo $request['id']; ?>, 'reject', '<?php echo htmlspecialchars($request['event_name']); ?>')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="showDetailsModal(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle"></h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="booking_item_id" id="actionBookingItemId">
                    <input type="hidden" name="action" id="actionType">
                    
                    <p id="actionMessage"></p>
                    
                    <div class="mb-3">
                        <label for="speaker_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="speaker_notes" id="speaker_notes" rows="3" 
                                  placeholder="Add any notes for the organizer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="actionButton"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Request Details</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
function showSection(section) {
    // Hide all sections
    document.getElementById('dashboard-section').style.display = 'none';
    document.getElementById('requests-section').style.display = 'none';
    
    // Hide/show titles
    document.querySelector('.dashboard-title').style.display = 'none';
    document.querySelector('.requests-title').style.display = 'none';
    
    // Remove active class from all nav items
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section and update nav
    if (section === 'dashboard') {
        document.getElementById('dashboard-section').style.display = 'block';
        document.querySelector('.dashboard-title').style.display = 'inline';
        document.querySelector('a[href="#dashboard"]').classList.add('active');
    } else if (section === 'requests') {
        document.getElementById('requests-section').style.display = 'block';
        document.querySelector('.requests-title').style.display = 'inline';
        document.querySelector('a[href="#requests"]').classList.add('active');
    }
}

// Handle URL hash on page load
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1);
    if (hash === 'requests') {
        showSection('requests');
    } else {
        showSection('dashboard');
    }
});

function showActionModal(bookingItemId, action, eventName) {
    document.getElementById('actionBookingItemId').value = bookingItemId;
    document.getElementById('actionType').value = action;
    
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('actionModalTitle');
    const message = document.getElementById('actionMessage');
    const button = document.getElementById('actionButton');
    
    if (action === 'accept') {
        title.textContent = 'Accept Booking Request';
        message.textContent = `Are you sure you want to accept the booking request for "${eventName}"?`;
        button.textContent = 'Accept Request';
        button.className = 'btn btn-success';
    } else {
        title.textContent = 'Reject Booking Request';
        message.textContent = `Are you sure you want to reject the booking request for "${eventName}"?`;
        button.textContent = 'Reject Request';
        button.className = 'btn btn-danger';
    }
    
    new mdb.Modal(modal).show();
}

function showDetailsModal(request) {
    const content = document.getElementById('detailsContent');
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Event Information</h6>
                <p><strong>Event:</strong> ${request.event_name}</p>
                <p><strong>Date:</strong> ${new Date(request.event_date).toLocaleDateString()}</p>
                <p><strong>Time:</strong> ${request.event_time}</p>
                <p><strong>Type:</strong> ${request.event_type}</p>
                <p><strong>Location:</strong> ${request.event_location || 'Not specified'}</p>
                <p><strong>Expected Attendees:</strong> ${request.expected_attendees || 'Not specified'}</p>
            </div>
            <div class="col-md-6">
                <h6>Organizer Information</h6>
                <p><strong>Name:</strong> ${request.organizer_name}</p>
                <p><strong>Email:</strong> ${request.organizer_email}</p>
                <p><strong>Organization:</strong> ${request.organization || 'Not specified'}</p>
                <p><strong>Format:</strong> ${request.format}</p>
                <p><strong>Rate:</strong> $${parseFloat(request.rate).toFixed(2)}</p>
            </div>
        </div>
        ${request.event_description ? `<div class="mt-3"><h6>Event Description</h6><p>${request.event_description}</p></div>` : ''}
        ${request.speaker_notes ? `<div class="mt-3"><h6>Your Notes</h6><p>${request.speaker_notes}</p></div>` : ''}
    `;
    
    new mdb.Modal(document.getElementById('detailsModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
