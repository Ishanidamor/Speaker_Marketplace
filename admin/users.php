<?php
$pageTitle = 'Manage Users';
require_once '../config/config.php';
requireAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        // Update user
        $userId = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $status = $_POST['status'];
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        
        // Validate
        if (empty($name) || empty($email)) {
            setFlashMessage('error', 'Name and email are required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Invalid email format');
        } else {
            // Check if email is taken by another user
            $exists = fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?", 
                [$email, $userId], 
                'si'
            );
            
            if ($exists) {
                setFlashMessage('error', 'Email already in use by another account');
            } else {
                // Update user
                executeQuery(
                    "UPDATE users SET name = ?, email = ?, phone = ?, status = ?, is_admin = ?, updated_at = NOW() 
                     WHERE id = ?",
                    [$name, $email, $phone, $status, $isAdmin, $userId],
                    'ssssii'
                );
                
                // Update session if editing own profile
                if ($userId === $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $name;
                    if ($isAdmin) {
                        $_SESSION['is_admin'] = true;
                    } else {
                        unset($_SESSION['is_admin']);
                    }
                }
                
                setFlashMessage('success', 'User updated successfully');
                redirect('users.php');
            }
        }
        
        // Store form data in session to repopulate form
        $_SESSION['form_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => $status,
            'is_admin' => $isAdmin
        ];
        
        redirect('user-edit.php?id=' . $userId);
    }
    elseif (isset($_POST['delete_user'])) {
        // Delete user
        $userId = (int)$_POST['user_id'];
        
        // Prevent deleting own account
        if ($userId === $_SESSION['user_id']) {
            setFlashMessage('error', 'You cannot delete your own account');
            redirect('users.php');
        }
        
        // Check if user has bookings
        $hasBookings = fetchOne(
            "SELECT COUNT(*) as count FROM bookings WHERE organizer_id = ?", 
            [$userId], 
            'i'
        )['count'];
        
        if ($hasBookings > 0) {
            // Soft delete
            executeQuery(
                "UPDATE users SET status = 'deleted', updated_at = NOW() WHERE id = ?", 
                [$userId], 
                'i'
            );
            setFlashMessage('success', 'User account deactivated (has active bookings)');
        } else {
            // Hard delete
            executeQuery("DELETE FROM users WHERE id = ?", [$userId], 'i');
            setFlashMessage('success', 'User deleted successfully');
        }
        
        redirect('users.php');
    }
    elseif (isset($_POST['reset_password'])) {
        // Reset user password
        $userId = (int)$_POST['user_id'];
        $newPassword = 'password123'; // Default password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        executeQuery(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
            [$hashedPassword, $userId],
            'si'
        );
        
        setFlashMessage('success', 'Password reset to: password123');
        redirect('user-edit.php?id=' . $userId);
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM bookings WHERE organizer_id = u.id) as booking_count,
          (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) as review_count
          FROM users u 
          WHERE u.id > 0"; // Exclude user ID 0 if any
          
$countQuery = "SELECT COUNT(*) as total FROM users u WHERE u.id > 0";

$params = [];
$types = '';

// Add search conditions
if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $countQuery .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

// Add role filter
if ($role === 'admin') {
    $query .= " AND u.email IN (SELECT email FROM admins)";
    $countQuery .= " AND u.email IN (SELECT email FROM admins)";
} elseif ($role === 'user') {
    $query .= " AND u.email NOT IN (SELECT email FROM admins)";
    $countQuery .= " AND u.email NOT IN (SELECT email FROM admins)";
}

// Add status filter
if (in_array($status, ['active', 'inactive', 'suspended', 'deleted'])) {
    $query .= " AND u.status = ?";
    $countQuery .= " AND u.status = ?";
    $params[] = $status;
    $types .= 's';
}

// Count total records
$totalRecords = fetchOne($countQuery, $params, $types)['total'];
$totalPages = ceil($totalRecords / $perPage);

// Add sorting and pagination
$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params = array_merge($params, [$perPage, $offset]);
$types .= 'ii';

// Get users
$users = fetchAll($query, $params, $types);

// Get user stats
$stats = fetchOne("
    SELECT 
        COUNT(*) as total_users,
        (SELECT COUNT(*) FROM admins) as total_admins,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
        (SELECT COUNT(*) FROM bookings) as total_bookings
    FROM users 
    WHERE status != 'deleted'
");

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas fa-users me-2"></i> Manage Users
            </h1>
            <div>
                <a href="user-new.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #4e73df;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Total Users</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_users']); ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x opacity-50"></i>
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
                                <h6 class="text-uppercase text-muted mb-2">Active Users</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['active_users']); ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-user-check fa-2x opacity-50"></i>
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
                                <h6 class="text-uppercase text-muted mb-2">Administrators</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_admins']); ?></h2>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-user-shield fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #e74a3b;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Total Bookings</h6>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($stats['total_bookings']); ?></h2>
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search users..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select" onchange="this.form.submit()">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Regular Users</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="deleted" <?php echo $status === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-end">
                        <a href="users.php" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Reset Filters">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Bookings</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">No users found</div>
                                        <?php if ($search || $role || $status): ?>
                                            <a href="users.php" class="btn btn-sm btn-outline-primary mt-2">
                                                Clear filters
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                // Get all admin emails in one query for better performance
                                $adminEmails = array_column(fetchAll("SELECT email FROM admins", [], ''), 'email');
                                
                                foreach ($users as $user): 
                                    $statusColors = [
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'pending' => 'warning',
                                        'suspended' => 'danger',
                                        'deleted' => 'dark'
                                    ];
                                    $roleColors = [
                                        'admin' => 'primary',
                                        'user' => 'secondary',
                                        'speaker' => 'info'
                                    ];
                                    
                                    // Check if user is admin by email
                                    $isAdmin = in_array($user['email'], $adminEmails);
                                    $userRole = $isAdmin ? 'admin' : 'user';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-3">
                                                    <div class="avatar-initial bg-<?php echo $roleColors[$userRole]; ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-medium">
                                                        <?php echo htmlspecialchars($user['name']); ?>
                                                        <?php if ($isAdmin): ?>
                                                            <i class="fas fa-crown text-warning ms-1" data-bs-toggle="tooltip" title="Administrator"></i>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" 
                                                 data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($user['email']); ?>">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                            <?php if (!empty($user['phone'])): ?>
                                                <div class="text-muted small">
                                                    <i class="fas fa-phone-alt me-1"></i> 
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $roleColors[$userRole]; ?>">
                                                <?php echo ucfirst($userRole); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $user['booking_count']; ?> bookings
                                            </span>
                                            <?php if ($user['review_count'] > 0): ?>
                                                <div class="text-muted small mt-1">
                                                    <?php echo $user['review_count']; ?> reviews
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusColors[$user['status']] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo timeAgo($user['created_at']); ?>
                                            <div class="text-muted small">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
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
                                                        <a class="dropdown-item" href="user-view.php?id=<?php echo $user['id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>View Profile
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="user-edit.php?id=<?php echo $user['id']; ?>">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <a class="dropdown-item text-info" href="#" 
                                                               onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')">
                                                                <i class="fas fa-key me-2"></i>Reset Password
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" 
                                                               onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')">
                                                                <i class="fas fa-trash-alt me-2"></i>Delete
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
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
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $role ? '&role='.urlencode($role) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
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
                                    <a class="page-link" href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $role ? '&role='.urlencode($role) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $role ? '&role='.urlencode($role) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $role ? '&role='.urlencode($role) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $role ? '&role='.urlencode($role) : ''; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
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
                <input type="hidden" name="user_id" id="deleteUserId">
                <input type="hidden" name="delete_user" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm User Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="userName" class="fw-bold"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. If the user has bookings, their account will be deactivated instead of deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post" id="resetPasswordForm">
                <input type="hidden" name="user_id" id="resetUserId">
                <input type="hidden" name="reset_password" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title">Reset User Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Reset password for user "<span id="resetUserName" class="fw-bold"></span>" to the default password?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        The new password will be: <strong>password123</strong>
                    </div>
                    <p class="text-muted small mb-0">The user will be prompted to change their password upon next login.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-1"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Confirm user deletion
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('userName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Reset password confirmation
function resetPassword(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>
