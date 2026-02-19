<?php
$pageTitle = 'Support Tickets';
require_once '../config/config.php';
requireAdmin();

// Check if support_tickets table exists, create if not
$tableExists = fetchOne("SHOW TABLES LIKE 'support_tickets'");
if (!$tableExists) {
    // Drop existing tables if they exist to avoid conflicts
    executeQuery("DROP TABLE IF EXISTS support_messages", [], '');
    executeQuery("DROP TABLE IF EXISTS support_tickets", [], '');
    
    // Create support_tickets table
    executeQuery("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", [], '');

    // Create support_messages table
    executeQuery("
        CREATE TABLE IF NOT EXISTS support_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", [], '');
    
    // Add some sample data for testing
    $adminUser = fetchOne("SELECT id FROM users WHERE email = 'admin@example.com' LIMIT 1");
    if ($adminUser) {
        $adminId = $adminUser['id'];
        
        executeQuery(
            "INSERT INTO support_tickets (user_id, subject, message, status, priority) 
             VALUES (?, ?, ?, ?, ?)",
            [
                $adminId,
                'Welcome to Support',
                'Thank you for contacting support. How can we help you today?',
                'open',
                'medium'
            ],
            'issss'
        );
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $status = $_POST['status'];
    
    try {
        executeQuery(
            "UPDATE support_tickets 
             SET status = ?, updated_at = NOW() 
             WHERE id = ?",
            [$status, $ticketId],
            'si'
        );
        setFlashMessage('success', 'Ticket status updated successfully');
    } catch (Exception $e) {
        setFlashMessage('error', 'Error updating ticket: ' . $e->getMessage());
    }
    
    redirect('support.php');
}

// Handle ticket reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $message = trim($_POST['message']);
    $userId = $_SESSION['user_id'] ?? null;
    
    if (empty($message)) {
        setFlashMessage('error', 'Message cannot be empty');
        redirect('support.php?view=' . $ticketId);
    }
    
    if (!$userId) {
        setFlashMessage('error', 'You must be logged in to reply to tickets');
        redirect('login.php');
    }
    
    try {
        // Add reply to support_messages
        executeQuery(
            "INSERT INTO support_messages (ticket_id, user_id, message) VALUES (?, ?, ?)",
            [$ticketId, $userId, $message],
            'iis'
        );
        
        // Update ticket status to in_progress if it was open
        executeQuery(
            "UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND status = 'open'",
            [$ticketId],
            'i'
        );
        
        setFlashMessage('success', 'Reply sent successfully');
    } catch (Exception $e) {
        setFlashMessage('error', 'Error sending reply: ' . $e->getMessage());
    }
    
    redirect('support.php?view=' . $ticketId);
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Build the base query
$query = "SELECT t.*, 
          u.name as user_name, 
          u.email as user_email
          FROM support_tickets t
          JOIN users u ON t.user_id = u.id
          WHERE 1=1";

$params = [];
$types = '';

// Add filters
if (!empty($status)) {
    $query .= " AND t.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($priority)) {
    $query .= " AND t.priority = ?";
    $params[] = $priority;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND (t.subject LIKE ? OR t.message LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// Add sorting
$sort = $_GET['sort'] ?? 't.created_at';
$order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';
$query .= " ORDER BY $sort $order";

// Add pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

// Execute the query
$tickets = fetchAll($query, $params, $types);

// Get ticket details if viewing a specific ticket
$ticket = null;
$messages = [];
$view = isset($_GET['view']) && is_numeric($_GET['view']) ? (int)$_GET['view'] : 0;

if ($view) {
    $ticket = fetchOne("
        SELECT t.*, u.name as user_name, u.email as user_email
        FROM support_tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ", [$view], 'i');
    
    if ($ticket) {
        // Mark messages as read
        executeQuery("
            UPDATE support_messages 
            SET is_read = TRUE 
            WHERE ticket_id = ? AND user_id != ?
        ", [$view, $_SESSION['user_id'] ?? 0], 'ii');
        
        // Get all messages for this ticket
        $messages = fetchAll("
            SELECT m.*, u.name as sender_name, u.email as sender_email, 'user' as sender_type
            FROM support_messages m
            JOIN users u ON m.user_id = u.id
            WHERE m.ticket_id = ?
            ORDER BY m.created_at ASC
        ", [$view], 'i');
    }
}

require_once 'includes/header.php';
?>
<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas fa-headset me-2"></i> Support Tickets
            </h1>
            <?php if (!$view): ?>
                <a href="?status=open" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> New Ticket
                </a>
            <?php else: ?>
                <a href="support.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                </a>
            <?php endif; ?>
        </div>

        <?php echo getFlashMessage(); ?>

        <?php if ($view && $ticket): ?>
            <!-- Ticket View -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php echo htmlspecialchars($ticket['subject']); ?>
                            <span class="badge bg-<?php 
                                echo [
                                    'open' => 'primary',
                                    'in_progress' => 'warning',
                                    'resolved' => 'success',
                                    'closed' => 'secondary'
                                ][$ticket['status']] ?? 'secondary'; 
                            ?> ms-2">
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                            </span>
                            <?php if ($ticket['priority'] === 'high'): ?>
                                <span class="badge bg-danger ms-1">High Priority</span>
                            <?php elseif ($ticket['priority'] === 'medium'): ?>
                                <span class="badge bg-warning text-dark ms-1">Medium Priority</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark ms-1">Low Priority</span>
                            <?php endif; ?>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Ticket Actions</h6></li>
                                <li>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="status" value="<?php 
                                            echo $ticket['status'] === 'open' ? 'in_progress' : 'open'; 
                                        ?>">
                                        <button type="submit" name="update_status" class="dropdown-item">
                                            <i class="fas fa-exchange-alt me-2"></i>
                                            <?php echo $ticket['status'] === 'open' ? 'Mark as In Progress' : 'Reopen Ticket'; ?>
                                        </button>
                                    </form>
                                </li>
                                <?php if ($ticket['status'] !== 'resolved'): ?>
                                    <li>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                            <input type="hidden" name="status" value="resolved">
                                            <button type="submit" name="update_status" class="dropdown-item text-success">
                                                <i class="fas fa-check-circle me-2"></i> Mark as Resolved
                                            </button>
                                        </form>
                                    </li>
                                <?php endif; ?>
                                <?php if ($ticket['status'] !== 'closed'): ?>
                                    <li>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                            <input type="hidden" name="status" value="closed">
                                            <button type="submit" name="update_status" class="dropdown-item text-danger"
                                                    onclick="return confirm('Are you sure you want to close this ticket? This action cannot be undone.')">
                                                <i class="fas fa-times-circle me-2"></i> Close Ticket
                                            </button>
                                        </form>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted small">
                            <i class="far fa-user me-1"></i> 
                            <?php echo htmlspecialchars($ticket['user_name']); ?> 
                            &lt;<?php echo htmlspecialchars($ticket['user_email']); ?>&gt;
                        </span>
                        <span class="text-muted small ms-3">
                            <i class="far fa-calendar-alt me-1"></i>
                            <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ticket-message mb-4 p-3 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Conversation</h6>
                    
                    <div class="ticket-conversation mb-4" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-4">
                                No messages yet. Be the first to reply.
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="mb-3">
                                    <div class="d-flex <?php echo $message['author_type'] === 'admin' ? 'justify-content-end' : ''; ?>">
                                        <div class="message-bubble <?php 
                                            echo $message['author_type'] === 'admin' 
                                                ? 'bg-primary text-white' 
                                                : 'bg-light';
                                            ?>" 
                                            style="max-width: 70%; border-radius: 15px; padding: 10px 15px;">
                                            <div class="message-header small mb-1">
                                                <strong><?php echo htmlspecialchars($message['author_name']); ?></strong>
                                                <span class="text-muted ms-2" style="font-size: 0.8em;">
                                                    <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="message-body">
                                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Reply</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required 
                                      placeholder="Type your reply here..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="reply_ticket" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <style>
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    .badge.bg-success { background-color: #198754 !important; }
    .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .badge.bg-info { background-color: #0dcaf0 !important; }
    .badge.bg-secondary { background-color: #6c757d !important; }
    .badge.bg-danger { background-color: #dc3545 !important; }
</style>
            
            <style>
                .message-bubble {
                    position: relative;
                    border-radius: 15px;
                    padding: 10px 15px;
                    margin-bottom: 5px;
                    word-wrap: break-word;
                }
                .message-bubble:after {
                    content: '';
                    position: absolute;
                    width: 0;
                    height: 0;
                    border: 10px solid transparent;
                }
                .bg-light:after {
                    left: -10px;
                    top: 10px;
                    border-right-color: #f8f9fa;
                    border-left: 0;
                }
                .bg-primary:after {
                    right: -10px;
                    top: 10px;
                    border-left-color: #0d6efd;
                    border-right: 0;
                }
            </style>
            
        <?php else: ?>
            <!-- Tickets List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="btn-group" role="group">
                                <a href="?" class="btn btn-outline-secondary btn-sm <?php echo !$status ? 'active' : ''; ?>">
                                    All
                                </a>
                                <a href="?status=open" class="btn btn-outline-primary btn-sm <?php echo $status === 'open' ? 'active' : ''; ?>">
                                    <i class="fas fa-circle-notch fa-spin me-1"></i> Open
                                </a>
                                <a href="?status=in_progress" class="btn btn-outline-warning btn-sm <?php echo $status === 'in_progress' ? 'active' : ''; ?>">
                                    <i class="fas fa-tools me-1"></i> In Progress
                                </a>
                                <a href="?status=resolved" class="btn btn-outline-success btn-sm <?php echo $status === 'resolved' ? 'active' : ''; ?>">
                                    <i class="fas fa-check-circle me-1"></i> Resolved
                                </a>
                                <a href="?status=closed" class="btn btn-outline-secondary btn-sm <?php echo $status === 'closed' ? 'active' : ''; ?>">
                                    <i class="fas fa-lock me-1"></i> Closed
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2 mt-md-0">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                <select class="form-select" onchange="window.location.href=this.value">
                                    <option value="?priority=">All Priorities</option>
                                    <option value="?priority=high<?php echo $status ? '&status=' . $status : ''; ?>" 
                                            <?php echo $priority === 'high' ? 'selected' : ''; ?>>
                                        High Priority
                                    </option>
                                    <option value="?priority=medium<?php echo $status ? '&status=' . $status : ''; ?>"
                                            <?php echo $priority === 'medium' ? 'selected' : ''; ?>>
                                        Medium Priority
                                    </option>
                                    <option value="?priority=low<?php echo $status ? '&status=' . $status : ''; ?>"
                                            <?php echo $priority === 'low' ? 'selected' : ''; ?>>
                                        Low Priority
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center p-5">
                            <div class="text-muted mb-3">No tickets found</div>
                            <a href="?status=open" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Create New Ticket
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($tickets as $ticket): ?>
                                <a href="?view=<?php echo $ticket['id']; ?>" 
                                   class="list-group-item list-group-item-action p-3 border-0">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="avatar avatar-sm">
                                                <div class="avatar-initial bg-<?php 
                                                    echo [
                                                        'open' => 'primary',
                                                        'in_progress' => 'warning',
                                                        'resolved' => 'success',
                                                        'closed' => 'secondary'
                                                    ][$ticket['status']] ?? 'secondary'; 
                                                ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                style="width: 40px; height: 40px;">
                                                    <?php echo strtoupper(substr($ticket['user_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                                    <?php if ($ticket['priority'] === 'high'): ?>
                                                        <span class="badge bg-danger ms-1">High</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="badge bg-<?php 
                                                    echo [
                                                        'open' => 'primary',
                                                        'in_progress' => 'warning',
                                                        'resolved' => 'success',
                                                        'closed' => 'secondary'
                                                    ][$ticket['status']] ?? 'secondary'; 
                                                ?> ms-2">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($ticket['user_name']); ?> • 
                                                <?php echo date('M j, Y g:i A', strtotime($ticket['updated_at'])); ?>
                                            </div>
                                            <div class="text-truncate mt-1" style="max-width: 600px;">
                                                <?php echo htmlspecialchars(substr($ticket['message'], 0, 150)); ?>
                                                <?php echo strlen($ticket['message']) > 150 ? '...' : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
}

document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('message');
    if (textarea) {
        textarea.addEventListener('input', function() {
            autoResize(this);
        });
    }
    
    // Scroll to bottom of conversation
    const conversation = document.querySelector('.ticket-conversation');
    if (conversation) {
        conversation.scrollTop = conversation.scrollHeight;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
