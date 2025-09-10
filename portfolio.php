<?php 
$page = 'portfolio';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get portfolio categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM portfolio WHERE is_active = 1 ORDER BY category");

// Get all portfolio items
$portfolio_items = $db->fetchAll("
    SELECT * FROM portfolio 
    WHERE is_active = 1 
    ORDER BY is_featured DESC, sort_order ASC
");
?>

<?php require_once 'includes/navigation.php'; ?>

    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">Our Portfolio</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Portfolio</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

    <section class="w3l-portfolio py-5">
        <div class="container py-lg-5">
            <div class="row">
                <div class="col-lg-12">
                    <div class="portfolio-header text-center mb-5">
                        <h3 class="hny-title mb-3">Our Work Speaks for Itself</h3>
                        <p class="lead">Explore our diverse portfolio of successful projects across various industries. Each project represents our commitment to excellence and innovation.</p>
                    </div>

                    <!-- Portfolio Filter -->
                    <div class="portfolio-filter text-center mb-5">
                        <button class="btn btn-outline-primary filter-btn active me-2 mb-2" data-filter="*">
                            All Projects
                        </button>
                        <?php foreach ($categories as $category): ?>
                        <button class="btn btn-outline-primary filter-btn me-2 mb-2" 
                                data-filter=".<?php echo strtolower(str_replace(' ', '-', $category['category'])); ?>">
                            <?php echo htmlspecialchars($category['category']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Portfolio Grid -->
                    <div class="portfolio-grid row" id="portfolioGrid">
                        <?php foreach ($portfolio_items as $item): ?>
                        <div class="col-lg-4 col-md-6 mb-4 portfolio-item <?php echo strtolower(str_replace(' ', '-', $item['category'])); ?>">
                            <div class="card portfolio-card h-100 shadow-sm">
                                <?php if ($item['image_url']): ?>
                                    <div class="portfolio-image-container">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             class="card-img-top portfolio-image" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                                        <div class="portfolio-overlay">
                                            <div class="portfolio-overlay-content">
                                                <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                                <p><?php echo htmlspecialchars($item['category']); ?></p>
                                                <div class="portfolio-links">
                                                    <?php if ($item['project_url']): ?>
                                                        <a href="<?php echo htmlspecialchars($item['project_url']); ?>" 
                                                           target="_blank" class="btn btn-light btn-sm me-2">
                                                            <i class="fa fa-external-link"></i> View Live
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-primary btn-sm" 
                                                            onclick="viewPortfolioDetails(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                        <i class="fa fa-eye"></i> Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($item['is_featured']): ?>
                                            <div class="featured-badge">
                                                <i class="fa fa-star"></i> Featured
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                                    <div class="portfolio-meta">
                                        <small class="text-muted">
                                            <i class="fa fa-tag"></i> <?php echo htmlspecialchars($item['category']); ?>
                                        </small>
                                        <?php if ($item['technologies']): ?>
                                        <div class="technologies mt-2">
                                            <?php 
                                            $techs = explode(',', $item['technologies']);
                                            foreach ($techs as $tech): 
                                            ?>
                                                <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($tech)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($portfolio_items)): ?>
                    <div class="text-center">
                        <div class="alert alert-info">
                            <h4>Portfolio Coming Soon</h4>
                            <p>We're currently updating our portfolio with our latest projects. Please check back soon to see our amazing work!</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="row mt-5">
                <div class="col-lg-12 text-center">
                    <div class="cta-section bg-primary text-white p-5 rounded">
                        <h3>Ready to Start Your Project?</h3>
                        <p class="lead mb-4">Let's work together to bring your vision to life. Contact us today for a free consultation!</p>
                        <a href="contact.php" class="btn btn-light btn-lg">
                            <i class="fa fa-rocket"></i> Start Your Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio Details Modal -->
    <div class="modal fade" id="portfolioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="portfolioModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="portfolioModalImage" src="" class="img-fluid rounded" alt="">
                        </div>
                        <div class="col-md-6">
                            <h6>Project Details</h6>
                            <p id="portfolioModalDescription"></p>
                            
                            <h6>Category</h6>
                            <p id="portfolioModalCategory"></p>
                            
                            <div id="portfolioModalTechnologies"></div>
                            
                            <div id="portfolioModalLinks" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="contact.php" class="btn btn-primary">Get Similar Project</a>
                </div>
            </div>
        </div>
    </div>

    <section class="w3l-footer-22-main">
        <?php require_once 'includes/footer.php'; ?>
    </section>

    <style>
    .portfolio-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .portfolio-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .portfolio-image-container {
        position: relative;
        overflow: hidden;
    }

    .portfolio-image {
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .portfolio-card:hover .portfolio-image {
        transform: scale(1.1);
    }

    .portfolio-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 123, 255, 0.9);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .portfolio-card:hover .portfolio-overlay {
        opacity: 1;
    }

    .portfolio-overlay-content {
        text-align: center;
        padding: 20px;
    }

    .featured-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ffc107;
        color: #000;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }

    .filter-btn {
        transition: all 0.3s ease;
    }

    .filter-btn.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .portfolio-item {
        transition: all 0.3s ease;
    }

    .portfolio-item.filtered-out {
        opacity: 0;
        transform: scale(0.8);
        pointer-events: none;
    }

    .technologies .badge {
        font-size: 0.75em;
    }

    .cta-section {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }
    </style>

    <script>
    // Portfolio filtering
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            const portfolioItems = document.querySelectorAll('.portfolio-item');
            
            portfolioItems.forEach(item => {
                if (filter === '*' || item.classList.contains(filter.substring(1))) {
                    item.classList.remove('filtered-out');
                    item.style.display = 'block';
                } else {
                    item.classList.add('filtered-out');
                    setTimeout(() => {
                        if (item.classList.contains('filtered-out')) {
                            item.style.display = 'none';
                        }
                    }, 300);
                }
            });
        });
    });

    // Portfolio details modal
    function viewPortfolioDetails(item) {
        document.getElementById('portfolioModalTitle').textContent = item.title;
        document.getElementById('portfolioModalImage').src = item.image_url;
        document.getElementById('portfolioModalImage').alt = item.title;
        document.getElementById('portfolioModalDescription').textContent = item.description;
        document.getElementById('portfolioModalCategory').textContent = item.category;
        
        // Technologies
        const techContainer = document.getElementById('portfolioModalTechnologies');
        if (item.technologies) {
            techContainer.innerHTML = '<h6>Technologies Used</h6>';
            const techs = item.technologies.split(',');
            let techHtml = '';
            techs.forEach(tech => {
                techHtml += `<span class="badge bg-primary me-1">${tech.trim()}</span>`;
            });
            techContainer.innerHTML += techHtml;
        } else {
            techContainer.innerHTML = '';
        }
        
        // Links
        const linksContainer = document.getElementById('portfolioModalLinks');
        let linksHtml = '';
        if (item.project_url) {
            linksHtml += `<a href="${item.project_url}" target="_blank" class="btn btn-outline-primary me-2">
                <i class="fa fa-external-link"></i> View Live Project
            </a>`;
        }
        linksContainer.innerHTML = linksHtml;
        
        // Show modal
        new bootstrap.Modal(document.getElementById('portfolioModal')).show();
    }

    // Initialize Bootstrap tooltips if needed
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>

</body>
</html>
