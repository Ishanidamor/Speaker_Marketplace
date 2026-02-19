<?php
$pageTitle = 'Manage Bookings';
require_once '../config/config.php';
requireAdmin();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT b.*, u.name as organizer_name, u.email as organizer_email 
          FROM bookings b 
          JOIN users u ON b.organizer_id = u.id 
          WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $query .= " AND (b.booking_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if ($status && in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
    $query .= " AND b.booking_status = ?";
    $params[] = $status;
    $types .= 's';
}

// Count total records
$countQuery = str_replace(
    'b.*, u.name as organizer_name, u.email as organizer_email', 
    'COUNT(*) as total', 
    $query
);
$totalRecords = fetchOne($countQuery, $params, $types)['total'];
$totalPages = ceil($totalRecords / $perPage);

// Add pagination and sorting
$query .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
$params = array_merge($params, [$perPage, $offset]);
$types .= 'ii';

$bookings = fetchAll($query, $params, $types);

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <h1 class="fw-bold mb-4">
            <i class="fas fa-calendar-check me-2"></i> Manage Bookings
        </h1>

        <!-- Search and Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by booking #, name, or email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="booking-export.php" class="btn btn-outline-secondary">
                            <i class="fas fa-file-export me-2"></i>Export
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking #</th>
                                <th>Organizer</th>
                                <th>Event Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">No bookings found</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($booking['organizer_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['organizer_email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $booking['event_date'] ? date('M j, Y', strtotime($booking['event_date'])) : 'N/A'; ?></td>
                                        <td class="fw-bold"><?php echo formatPrice($booking['final_amount']); ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $status = $booking['booking_status'];
                                            ?>
                                            <span class="badge bg-<?php echo $statusColors[$status] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="booking-view.php?id=<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>View Details
                                                        </a>
                                                    </li>
                                                    <?php if ($status === 'pending'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" 
                                                               onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                                <i class="fas fa-check me-2"></i>Confirm
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" 
                                                               onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                                <i class="fas fa-times me-2"></i>Cancel
                                                            </a>
                                                        </li>
                                                    <?php elseif ($status === 'confirmed'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" 
                                                               onclick="updateStatus(<?php echo $booking['id']; ?>, 'completed')">
                                                                <i class="fas fa-check-double me-2"></i>Mark as Completed
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-primary" href="#" 
                                                           onclick="sendReminder(<?php echo $booking['id']; ?>)">
                                                            <i class="fas fa-bell me-2"></i>Send Reminder
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-info" href="#" 
                                                           onclick="showNotes(<?php echo $booking['id']; ?>)">
                                                            <i class="fas fa-sticky-note me-2"></i>Add Note
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
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
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

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="statusForm" method="post" action="update-booking-status.php">
                <input type="hidden" name="booking_id" id="bookingId">
                <input type="hidden" name="status" id="statusValue">
                <div class="modal-header">
                    <h5 class="modal-title">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="statusNote" class="form-label">Add a note (optional):</label>
                        <textarea class="form-control" id="statusNote" name="note" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyUser" name="notify_user" checked>
                        <label class="form-check-label" for="notifyUser">
                            Notify user via email
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="notesForm" method="post" action="save-booking-note.php">
                <input type="hidden" name="booking_id" id="noteBookingId">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note to Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="noteContent" class="form-label">Note:</label>
                        <textarea class="form-control" id="noteContent" name="note" rows="5" required></textarea>
                        <div class="form-text">This note will be visible to all administrators.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reminder Modal -->
<div class="modal fade" id="reminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reminderForm" method="post" action="send-booking-reminder.php">
                <input type="hidden" name="booking_id" id="reminderBookingId">
                <div class="modal-header">
                    <h5 class="modal-title">Send Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reminderType" class="form-label">Reminder Type:</label>
                        <select class="form-select" id="reminderType" name="reminder_type" required>
                            <option value="payment">Payment Reminder</option>
                            <option value="event">Upcoming Event</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    <div class="mb-3" id="customMessageContainer" style="display: none;">
                        <label for="customMessage" class="form-label">Custom Message:</label>
                        <textarea class="form-control" id="customMessage" name="custom_message" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sendCopy" name="send_copy" checked>
                        <label class="form-check-label" for="sendCopy">
                            Send a copy to my email
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Status Update
function updateStatus(bookingId, status) {
    document.getElementById('bookingId').value = bookingId;
    document.getElementById('statusValue').value = status;
    
    // Set modal title based on status
    const statusTitles = {
        'confirmed': 'Confirm Booking',
        'cancelled': 'Cancel Booking',
        'completed': 'Mark as Completed'
    };
    
    document.querySelector('#statusModal .modal-title').textContent = statusTitles[status] || 'Update Status';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// Show Notes
function showNotes(bookingId) {
    document.getElementById('noteBookingId').value = bookingId;
    document.getElementById('noteContent').value = '';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('notesModal'));
    modal.show();
}

// Send Reminder
function sendReminder(bookingId) {
    document.getElementById('reminderBookingId').value = bookingId;
    document.getElementById('customMessage').value = '';
    document.getElementById('reminderType').value = 'payment';
    document.getElementById('customMessageContainer').style.display = 'none';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('reminderModal'));
    modal.show();
}

// Toggle custom message field
const reminderType = document.getElementById('reminderType');
const customMessageContainer = document.getElementById('customMessageContainer');

if (reminderType && customMessageContainer) {
    reminderType.addEventListener('change', function() {
        customMessageContainer.style.display = this.value === 'custom' ? 'block' : 'none';
    });
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>
