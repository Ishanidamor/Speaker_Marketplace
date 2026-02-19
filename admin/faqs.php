<?php
$pageTitle = 'FAQs';
require_once '../config/config.php';
requireAdmin();

// Check if faqs table exists, create if not
$tableExists = fetchOne("SHOW TABLES LIKE 'faqs'");
if (!$tableExists) {
    executeQuery("
        CREATE TABLE IF NOT EXISTS faqs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            question VARCHAR(255) NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(100) NOT NULL,
            sort_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ", [], '');
    
    // Insert some default FAQs
    $defaultFaqs = [
        [
            'question' => 'How do I book a speaker?',
            'answer' => 'To book a speaker, browse our directory, view speaker profiles, and use the booking form to submit your request.',
            'category' => 'Booking',
            'sort_order' => 1
        ],
        [
            'question' => 'What payment methods do you accept?',
            'answer' => 'We accept all major credit cards, PayPal, and bank transfers for speaker bookings.',
            'category' => 'Payments',
            'sort_order' => 1
        ]
    ];
    
    foreach ($defaultFaqs as $faq) {
        executeQuery(
            "INSERT INTO faqs (question, answer, category, sort_order) VALUES (?, ?, ?, ?)",
            [$faq['question'], $faq['answer'], $faq['category'], $faq['sort_order']],
            'sssi'
        );
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_faq'])) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $category = trim($_POST['category']);
        $sortOrder = (int)$_POST['sort_order'];
        $status = $_POST['status'];
        
        try {
            executeQuery(
                "INSERT INTO faqs (question, answer, category, sort_order, status) 
                 VALUES (?, ?, ?, ?, ?)",
                [$question, $answer, $category, $sortOrder, $status],
                'sssis'
            );
            setFlashMessage('success', 'FAQ added successfully');
            redirect('faqs.php');
        } catch (Exception $e) {
            setFlashMessage('error', 'Error adding FAQ: ' . $e->getMessage());
        }
    } elseif (isset($_POST['update_faq'])) {
        $id = (int)$_POST['faq_id'];
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $category = trim($_POST['category']);
        $sortOrder = (int)$_POST['sort_order'];
        $status = $_POST['status'];
        
        try {
            executeQuery(
                "UPDATE faqs 
                 SET question = ?, answer = ?, category = ?, sort_order = ?, status = ?
                 WHERE id = ?",
                [$question, $answer, $category, $sortOrder, $status, $id],
                'sssisi'
            );
            setFlashMessage('success', 'FAQ updated successfully');
            redirect('faqs.php');
        } catch (Exception $e) {
            setFlashMessage('error', 'Error updating FAQ: ' . $e->getMessage());
        }
    } elseif (isset($_POST['delete_faq'])) {
        $id = (int)$_POST['faq_id'];
        try {
            executeQuery("DELETE FROM faqs WHERE id = ?", [$id], 'i');
            setFlashMessage('success', 'FAQ deleted successfully');
            redirect('faqs.php');
        } catch (Exception $e) {
            setFlashMessage('error', 'Error deleting FAQ: ' . $e->getMessage());
        }
    } elseif (isset($_POST['update_order'])) {
        $orders = $_POST['order'];
        try {
            foreach ($orders as $id => $order) {
                executeQuery(
                    "UPDATE faqs SET sort_order = ? WHERE id = ?",
                    [(int)$order, (int)$id],
                    'ii'
                );
            }
            setFlashMessage('success', 'FAQ order updated successfully');
            redirect('faqs.php');
        } catch (Exception $e) {
            setFlashMessage('error', 'Error updating FAQ order: ' . $e->getMessage());
        }
    }
}

// Get all FAQs grouped by category
$categories = [];
$faqs = fetchAll("SELECT * FROM faqs ORDER BY category, sort_order, question", [], '');

// Group FAQs by category
foreach ($faqs as $faq) {
    if (!isset($categories[$faq['category']])) {
        $categories[$faq['category']] = [];
    }
    $categories[$faq['category']][] = $faq;
}

// Get unique categories for the filter dropdown
$allCategories = array_unique(array_column($faqs, 'category'));
sort($allCategories);

require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">
                <i class="fas fa-question-circle me-2"></i> Frequently Asked Questions
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFaqModal">
                <i class="fas fa-plus me-1"></i> Add New FAQ
            </button>
        </div>

        <?php echo getFlashMessage(); ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <?php if (empty($faqs)): ?>
                    <div class="text-center p-5">
                        <div class="text-muted">No FAQs found</div>
                        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addFaqModal">
                            <i class="fas fa-plus me-1"></i> Add Your First FAQ
                        </button>
                    </div>
                <?php else: ?>
                    <div class="accordion" id="faqAccordion">
                        <?php $i = 0; foreach ($categories as $category => $categoryFaqs): ?>
                            <div class="accordion-item border-0 border-bottom">
                                <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                    <button class="accordion-button bg-light fw-bold" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>" 
                                            aria-expanded="true" aria-controls="collapse<?php echo $i; ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                        <span class="badge bg-primary ms-2"><?php echo count($categoryFaqs); ?></span>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse show" 
                                     aria-labelledby="heading<?php echo $i; ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body p-0">
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($categoryFaqs as $faq): ?>
                                                <div class="list-group-item border-0 py-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="me-3">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($faq['question']); ?></h6>
                                                            <div class="text-muted small mb-2">
                                                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                                            </div>
                                                            <div>
                                                                <span class="badge bg-<?php echo $faq['status'] === 'active' ? 'success' : 'secondary'; ?> me-2">
                                                                    <?php echo ucfirst($faq['status']); ?>
                                                                </span>
                                                                <span class="text-muted small">
                                                                    <i class="fas fa-sort-numeric-down me-1"></i> 
                                                                    Sort Order: <?php echo $faq['sort_order']; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <button class="dropdown-item" type="button" 
                                                                            onclick="editFaq(<?php echo htmlspecialchars(json_encode($faq)); ?>)">
                                                                        <i class="fas fa-edit me-2"></i> Edit
                                                                    </button>
                                                                </li>
                                                                <li>
                                                                    <button class="dropdown-item text-danger" type="button" 
                                                                            onclick="deleteFaq(<?php echo $faq['id']; ?>, '<?php echo addslashes($faq['question']); ?>')">
                                                                        <i class="fas fa-trash me-2"></i> Delete
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $i++; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit FAQ Modal -->
<div class="modal fade" id="faqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="faqForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="faq_id" id="faqId">
                    <input type="hidden" name="add_faq" value="1">
                    
                    <div class="mb-3">
                        <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="question" name="question" required 
                               placeholder="e.g., How do I book a speaker?">
                    </div>
                    
                    <div class="mb-3">
                        <label for="answer" class="form-label">Answer <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="answer" name="answer" rows="4" required 
                                  placeholder="Provide a clear and concise answer"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category" name="category" required 
                                   list="categoryList" placeholder="e.g., Booking, Payments, etc.">
                            <datalist id="categoryList">
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="0" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save FAQ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="POST">
                <input type="hidden" name="faq_id" id="deleteFaqId">
                <input type="hidden" name="delete_faq" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Delete FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the FAQ: <strong id="faqQuestion"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete FAQ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sort Order Form (hidden) -->
<form id="sortForm" method="POST" style="display: none;">
    <input type="hidden" name="update_order" value="1">
    <div id="orderFields"></div>
</form>

<script>
// Edit FAQ
function editFaq(faq) {
    document.getElementById('modalTitle').textContent = 'Edit FAQ';
    document.getElementById('faqId').value = faq.id;
    document.getElementById('question').value = faq.question;
    document.getElementById('answer').value = faq.answer;
    document.getElementById('category').value = faq.category;
    document.getElementById('sort_order').value = faq.sort_order;
    document.getElementById('status').value = faq.status;
    
    // Update form action
    const form = document.getElementById('faqForm');
    form.querySelector('input[name="add_faq"]').name = 'update_faq';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('faqModal'));
    modal.show();
}

// Delete FAQ confirmation
function deleteFaq(id, question) {
    document.getElementById('deleteFaqId').value = id;
    document.getElementById('faqQuestion').textContent = question;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Show add FAQ modal
document.addEventListener('DOMContentLoaded', function() {
    const addFaqBtn = document.querySelector('[data-bs-target="#addFaqModal"]');
    if (addFaqBtn) {
        addFaqBtn.addEventListener('click', function() {
            document.getElementById('modalTitle').textContent = 'Add New FAQ';
            document.getElementById('faqForm').reset();
            document.getElementById('faqId').value = '';
            document.querySelector('input[name="add_faq"]').name = 'add_faq';
            document.getElementById('status').value = 'active';
            document.getElementById('sort_order').value = '0';
        });
    }
    
    // Make FAQ items sortable within categories
    const accordionBodies = document.querySelectorAll('.accordion-body');
    accordionBodies.forEach(body => {
        const listGroup = body.querySelector('.list-group');
        if (listGroup) {
            new Sortable(listGroup, {
                animation: 150,
                handle: '.fa-grip-vertical',
                onEnd: function() {
                    const orderFields = document.getElementById('orderFields');
                    orderFields.innerHTML = '';
                    
                    const items = listGroup.querySelectorAll('.list-group-item');
                    items.forEach((item, index) => {
                        const faqId = item.querySelector('input[name="faq_id"]')?.value;
                        if (faqId) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'order[' + faqId + ']';
                            input.value = index + 1;
                            orderFields.appendChild(input);
                        }
                    });
                    
                    if (orderFields.children.length > 0) {
                        document.getElementById('sortForm').submit();
                    }
                }
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
