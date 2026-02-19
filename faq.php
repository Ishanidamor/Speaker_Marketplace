<?php
$pageTitle = 'FAQ';
require_once 'includes/header.php';

$faqs = fetchAll("SELECT * FROM faqs WHERE status = 'active' ORDER BY id");
?>

<div class="main-content">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">
                <i class="fas fa-question-circle me-2"></i> Frequently Asked Questions
            </h1>
            <p class="text-muted">Find answers to common questions about our products and services</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item mb-3 border-0 shadow-sm">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?> fw-bold" 
                                        type="button" 
                                        data-mdb-toggle="collapse" 
                                        data-mdb-target="#collapse<?php echo $index; ?>">
                                    <i class="fas fa-question-circle text-primary me-3"></i>
                                    <?php echo htmlspecialchars($faq['question']); ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" 
                                 class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                 data-mdb-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="card mt-5 text-center p-5" style="background: linear-gradient(135deg, var(--primary-color), #1565c0); color: white;">
                    <h3 class="fw-bold mb-3">Still Have Questions?</h3>
                    <p class="mb-4">Can't find the answer you're looking for? Our support team is here to help!</p>
                    <div>
                        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-light btn-lg">
                            <i class="fas fa-envelope me-2"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
