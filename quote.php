<?php 
$page = 'quote';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get available services for the dropdown
$services = $db->fetchAll("SELECT DISTINCT title FROM services WHERE is_active = 1 ORDER BY title");

// Check for flash messages
$flashMessage = getFlashMessage();
?>

<?php require_once 'includes/navigation.php'; ?>

    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">Request a Quote</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Request Quote</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

    <section class="w3l-quote-request py-5">
        <div class="container py-lg-5">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="quote-form">
                        <div class="text-center mb-5">
                            <h3 class="hny-title mb-3">Get Your Custom Quote</h3>
                            <p class="lead">Tell us about your project and we'll provide you with a detailed, customized quote within 24-48 hours. All quotes are free and come with no obligations.</p>
                        </div>

                        <?php if ($flashMessage): ?>
                            <div class="alert alert-<?php echo $flashMessage['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                                <?php echo htmlspecialchars($flashMessage['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-lg">
                            <div class="card-body p-5">
                                <form action="process_quote_request.php" method="POST" id="quoteForm" enctype="multipart/form-data">
                                    <!-- Personal Information -->
                                    <div class="section-header mb-4">
                                        <h5 class="text-primary">
                                            <i class="fa fa-user me-2"></i>Personal Information
                                        </h5>
                                        <hr>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="company" class="form-label">Company Name</label>
                                            <input type="text" class="form-control" id="company" name="company">
                                        </div>
                                    </div>

                                    <!-- Project Information -->
                                    <div class="section-header mb-4 mt-4">
                                        <h5 class="text-primary">
                                            <i class="fa fa-project-diagram me-2"></i>Project Information
                                        </h5>
                                        <hr>
                                    </div>

                                    <div class="mb-3">
                                        <label for="project_title" class="form-label">Project Title *</label>
                                        <input type="text" class="form-control" id="project_title" name="project_title" 
                                               placeholder="e.g., E-commerce Website Development" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="project_description" class="form-label">Project Description *</label>
                                        <textarea class="form-control" id="project_description" name="project_description" rows="5" 
                                                  placeholder="Please provide detailed information about your project requirements, goals, target audience, features needed, etc." required></textarea>
                                        <small class="form-text text-muted">The more details you provide, the more accurate your quote will be.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="services" class="form-label">Services Required *</label>
                                        <div class="services-checkboxes">
                                            <div class="row">
                                                <?php foreach ($services as $index => $service): ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="services[]" value="<?php echo htmlspecialchars($service['title']); ?>"
                                                               id="service<?php echo $index; ?>">
                                                        <label class="form-check-label" for="service<?php echo $index; ?>">
                                                            <?php echo htmlspecialchars($service['title']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="services[]" value="Custom Solution" id="serviceCustom">
                                                        <label class="form-check-label" for="serviceCustom">
                                                            Custom Solution
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Specifications -->
                                    <div class="section-header mb-4 mt-4">
                                        <h5 class="text-primary">
                                            <i class="fa fa-cogs me-2"></i>Project Specifications
                                        </h5>
                                        <hr>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="budget_range" class="form-label">Budget Range *</label>
                                            <select class="form-select" id="budget_range" name="budget_range" required>
                                                <option value="">Select budget range...</option>
                                                <option value="Under $1,000">Under $1,000</option>
                                                <option value="$1,000 - $5,000">$1,000 - $5,000</option>
                                                <option value="$5,000 - $10,000">$5,000 - $10,000</option>
                                                <option value="$10,000 - $25,000">$10,000 - $25,000</option>
                                                <option value="$25,000 - $50,000">$25,000 - $50,000</option>
                                                <option value="Over $50,000">Over $50,000</option>
                                                <option value="To be discussed">To be discussed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="timeline" class="form-label">Preferred Timeline *</label>
                                            <select class="form-select" id="timeline" name="timeline" required>
                                                <option value="">Select timeline...</option>
                                                <option value="ASAP">ASAP (Rush job)</option>
                                                <option value="1-2 weeks">1-2 weeks</option>
                                                <option value="3-4 weeks">3-4 weeks</option>
                                                <option value="1-2 months">1-2 months</option>
                                                <option value="3-6 months">3-6 months</option>
                                                <option value="6+ months">6+ months</option>
                                                <option value="Flexible">Flexible</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- File Attachments -->
                                    <div class="mb-4">
                                        <label for="attachments" class="form-label">Project Files (Optional)</label>
                                        <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                               multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar">
                                        <small class="form-text text-muted">
                                            Upload any relevant files (designs, documents, images, etc.). Max 10MB per file.
                                            Supported formats: PDF, DOC, DOCX, JPG, PNG, ZIP, RAR
                                        </small>
                                    </div>

                                    <!-- Terms and Submit -->
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="terms" required>
                                            <label class="form-check-label" for="terms">
                                                I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> 
                                                and <a href="ppolicy.php" target="_blank">Privacy Policy</a> *
                                            </label>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="fa fa-paper-plane"></i> Submit Quote Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Information Cards -->
                        <div class="row mt-5">
                            <div class="col-md-4 mb-3">
                                <div class="info-card text-center p-4 bg-light rounded">
                                    <i class="fa fa-clock fa-2x text-primary mb-3"></i>
                                    <h5>Quick Turnaround</h5>
                                    <p>Receive your detailed quote within 24-48 hours</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="info-card text-center p-4 bg-light rounded">
                                    <i class="fa fa-calculator fa-2x text-primary mb-3"></i>
                                    <h5>Detailed Breakdown</h5>
                                    <p>Transparent pricing with itemized cost breakdown</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="info-card text-center p-4 bg-light rounded">
                                    <i class="fa fa-handshake fa-2x text-primary mb-3"></i>
                                    <h5>No Obligations</h5>
                                    <p>Free quotes with no pressure or commitments</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="w3l-quote-faq bg-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h4 class="text-center mb-4">Frequently Asked Questions</h4>
                    <div class="accordion" id="quoteFaqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse1" aria-expanded="false">
                                    How long does it take to receive a quote?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#quoteFaqAccordion">
                                <div class="accordion-body">
                                    We typically provide detailed quotes within 24-48 hours. For complex projects, 
                                    we may need additional time to ensure accuracy.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse2" aria-expanded="false">
                                    Are your quotes binding?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#quoteFaqAccordion">
                                <div class="accordion-body">
                                    Our quotes are valid for 30 days from the issue date. Prices may change after 
                                    this period due to scope changes or market conditions.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse3" aria-expanded="false">
                                    Can I modify my project requirements after receiving a quote?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#quoteFaqAccordion">
                                <div class="accordion-body">
                                    Yes, you can request modifications. We'll provide a revised quote reflecting 
                                    the changes to ensure transparency in pricing.
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
    .quote-form .card {
        border: none;
        border-radius: 15px;
    }

    .section-header h5 {
        color: #007bff;
        font-weight: 600;
    }

    .section-header hr {
        border-color: #007bff;
        opacity: 0.3;
    }

    .info-card {
        transition: transform 0.3s ease;
        border-radius: 10px;
    }

    .info-card:hover {
        transform: translateY(-5px);
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        transition: border-color 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .btn-primary {
        border-radius: 25px;
        padding: 12px 30px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .services-checkboxes {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
    }

    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    #attachments {
        border-style: dashed;
    }
    </style>

    <script>
    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('quoteForm');
        
        // File upload validation
        const fileInput = document.getElementById('attachments');
        fileInput.addEventListener('change', function() {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
            
            for (let file of this.files) {
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large. Maximum file size is 10MB.`);
                    this.value = '';
                    return;
                }
                
                const extension = file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(extension)) {
                    alert(`File "${file.name}" has an unsupported format. Please use: PDF, DOC, DOCX, JPG, PNG, ZIP, RAR`);
                    this.value = '';
                    return;
                }
            }
        });

        // Services validation
        form.addEventListener('submit', function(e) {
            const services = document.querySelectorAll('input[name="services[]"]:checked');
            if (services.length === 0) {
                e.preventDefault();
                alert('Please select at least one service.');
                return;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            // Re-enable button after 5 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    });
    </script>

</body>
</html>
