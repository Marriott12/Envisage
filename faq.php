<?php 
$page = 'faq';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get FAQs grouped by category
$faqs_by_category = [];
$faqs = $db->fetchAll("SELECT * FROM faqs WHERE is_active = 1 ORDER BY category, sort_order ASC");

foreach ($faqs as $faq) {
    $faqs_by_category[$faq['category']][] = $faq;
}

$categories = array_keys($faqs_by_category);
?>

<?php require_once 'includes/navigation.php'; ?>

    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">Frequently Asked Questions</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">FAQ</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

    <section class="w3l-faq py-5">
        <div class="container py-lg-5">
            <div class="row">
                <div class="col-lg-8">
                    <div class="faq-content">
                        <div class="intro-text mb-5">
                            <h3>How can we help you?</h3>
                            <p class="lead">Here are answers to the most commonly asked questions about our services. If you can't find what you're looking for, feel free to <a href="contact.php">contact us</a> directly.</p>
                        </div>

                        <!-- Search Bar -->
                        <div class="search-bar mb-4">
                            <div class="input-group">
                                <input type="text" id="faqSearch" class="form-control" placeholder="Search FAQs...">
                                <span class="input-group-text">
                                    <i class="fa fa-search"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Category Tabs -->
                        <?php if (!empty($categories)): ?>
                        <ul class="nav nav-tabs mb-4" id="faqTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                    All Questions
                                </button>
                            </li>
                            <?php foreach ($categories as $index => $category): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>-tab" 
                                        data-bs-toggle="tab" data-bs-target="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                                        type="button" role="tab">
                                    <?php echo htmlspecialchars($category); ?>
                                </button>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- FAQ Content -->
                        <div class="tab-content" id="faqTabContent">
                            <!-- All FAQs -->
                            <div class="tab-pane fade show active" id="all" role="tabpanel">
                                <div class="accordion" id="allFaqAccordion">
                                    <?php 
                                    $counter = 0;
                                    foreach ($faqs_by_category as $category => $category_faqs): 
                                    ?>
                                        <h5 class="category-header mt-4 mb-3">
                                            <i class="fa fa-folder-open me-2"></i><?php echo htmlspecialchars($category); ?>
                                        </h5>
                                        <?php foreach ($category_faqs as $faq): $counter++; ?>
                                        <div class="accordion-item faq-item">
                                            <h2 class="accordion-header" id="heading<?php echo $counter; ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?php echo $counter; ?>" aria-expanded="false">
                                                    <?php echo htmlspecialchars($faq['question']); ?>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $counter; ?>" class="accordion-collapse collapse" 
                                                 data-bs-parent="#allFaqAccordion">
                                                <div class="accordion-body">
                                                    <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Category-specific FAQs -->
                            <?php foreach ($faqs_by_category as $category => $category_faqs): ?>
                            <div class="tab-pane fade" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>" role="tabpanel">
                                <div class="accordion" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>Accordion">
                                    <?php foreach ($category_faqs as $index => $faq): ?>
                                    <div class="accordion-item faq-item">
                                        <h2 class="accordion-header" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>Heading<?php echo $index; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>Collapse<?php echo $index; ?>" aria-expanded="false">
                                                <?php echo htmlspecialchars($faq['question']); ?>
                                            </button>
                                        </h2>
                                        <div id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>Collapse<?php echo $index; ?>" 
                                             class="accordion-collapse collapse" 
                                             data-bs-parent="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>Accordion">
                                            <div class="accordion-body">
                                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h4>No FAQs Available</h4>
                                <p>We're working on adding frequently asked questions. Please <a href="contact.php">contact us</a> directly for any questions you may have.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="sidebar">
                        <!-- Quick Contact -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fa fa-headphones me-2"></i>Need More Help?
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <p>Can't find the answer you're looking for? Our team is here to help!</p>
                                <div class="contact-methods mb-3">
                                    <a href="tel:+260974297313" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                        <i class="fa fa-phone"></i> Call Us
                                    </a>
                                    <a href="mailto:info@envisagezm.com" class="btn btn-outline-success btn-sm me-2 mb-2">
                                        <i class="fa fa-envelope"></i> Email
                                    </a>
                                    <a href="contact.php" class="btn btn-primary btn-sm mb-2">
                                        <i class="fa fa-comment"></i> Contact Form
                                    </a>
                                </div>
                                <small class="text-muted">Response within 24 hours</small>
                            </div>
                        </div>

                        <!-- Popular Questions -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fa fa-star me-2"></i>Popular Questions
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $popular_faqs = array_slice($faqs, 0, 3);
                                foreach ($popular_faqs as $faq): 
                                ?>
                                <div class="popular-faq mb-3">
                                    <h6><a href="#" class="text-decoration-none popular-faq-link" data-question="<?php echo htmlspecialchars($faq['question']); ?>">
                                        <?php echo htmlspecialchars($faq['question']); ?>
                                    </a></h6>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fa fa-folder me-2"></i>Categories
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <a href="#" class="list-group-item list-group-item-action border-0 ps-0" data-bs-toggle="tab" data-bs-target="#all">
                                        <i class="fa fa-list me-2"></i>All Questions
                                        <span class="badge bg-primary float-end"><?php echo count($faqs); ?></span>
                                    </a>
                                    <?php foreach ($faqs_by_category as $category => $category_faqs): ?>
                                    <a href="#" class="list-group-item list-group-item-action border-0 ps-0" 
                                       data-bs-toggle="tab" data-bs-target="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                                        <i class="fa fa-folder-o me-2"></i><?php echo htmlspecialchars($category); ?>
                                        <span class="badge bg-secondary float-end"><?php echo count($category_faqs); ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="w3l-footer-22-main">
        <?php require_once 'includes/footer.php'; ?>
    </section>

    <style>
    .category-header {
        color: #007bff;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
    }
    
    .faq-item {
        margin-bottom: 10px;
    }
    
    .accordion-button:not(.collapsed) {
        background-color: #e7f3ff;
        color: #0d6efd;
    }
    
    .popular-faq-link:hover {
        color: #007bff !important;
    }
    
    .search-highlight {
        background-color: yellow;
        font-weight: bold;
    }
    
    .contact-methods .btn {
        white-space: nowrap;
    }
    </style>

    <script>
        // Search functionality
        document.getElementById('faqSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(function(item) {
                const question = item.querySelector('.accordion-button').textContent.toLowerCase();
                const answer = item.querySelector('.accordion-body').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Popular FAQ click handler
        document.querySelectorAll('.popular-faq-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const question = this.getAttribute('data-question');
                
                // Search for the question and expand it
                document.querySelectorAll('.accordion-button').forEach(function(button) {
                    if (button.textContent.trim() === question) {
                        button.click();
                        button.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            });
        });
    </script>

</body>
</html>
