<?php 
$page = 'blog';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Pagination settings
$posts_per_page = 6;
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page_num - 1) * $posts_per_page;

// Get total posts count
$total_posts = $db->fetch("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")['count'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get blog posts
$posts = $db->fetchAll("
    SELECT * FROM blog_posts 
    WHERE status = 'published' 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
", [$posts_per_page, $offset]);

// Get recent posts for sidebar
$recent_posts = $db->fetchAll("
    SELECT title, slug, created_at, featured_image 
    FROM blog_posts 
    WHERE status = 'published' 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>

<?php require_once 'includes/navigation.php'; ?>

    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">Blog</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Blog</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

    <section class="w3l-blog py-5">
        <div class="container py-lg-5">
            <div class="row">
                <!-- Blog Posts -->
                <div class="col-lg-8">
                    <div class="row">
                        <?php if (empty($posts)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h4>No Blog Posts Yet</h4>
                                    <p>We're working on creating amazing content for you. Please check back soon!</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <?php if ($post['featured_image']): ?>
                                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                 style="height: 200px; object-fit: cover;">
                                        <?php endif; ?>
                                        
                                        <div class="card-body d-flex flex-column">
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fa fa-calendar"></i> 
                                                    <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                                </small>
                                            </div>
                                            
                                            <h5 class="card-title">
                                                <a href="blog-post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </h5>
                                            
                                            <p class="card-text flex-grow-1">
                                                <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)) . '...'; ?>
                                            </p>
                                            
                                            <div class="mt-auto">
                                                <a href="blog-post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    Read More <i class="fa fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Blog pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page_num > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page_num - 1; ?>">
                                            <i class="fa fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page_num ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page_num < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page_num + 1; ?>">
                                            Next <i class="fa fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="sidebar">
                        <!-- Recent Posts -->
                        <?php if (!empty($recent_posts)): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Posts</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recent_posts as $recent): ?>
                                        <div class="d-flex mb-3">
                                            <?php if ($recent['featured_image']): ?>
                                                <img src="<?php echo htmlspecialchars($recent['featured_image']); ?>" 
                                                     class="me-3" 
                                                     alt="<?php echo htmlspecialchars($recent['title']); ?>"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="blog-post.php?slug=<?php echo htmlspecialchars($recent['slug']); ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($recent['title']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($recent['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Newsletter Signup -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Subscribe to Our Newsletter</h5>
                            </div>
                            <div class="card-body">
                                <p>Stay updated with our latest blog posts and technology insights.</p>
                                <form action="includes/newsletter.php" method="POST">
                                    <div class="input-group">
                                        <input type="email" name="email" class="form-control" 
                                               placeholder="Your email address" required>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fa fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Get In Touch</h5>
                            </div>
                            <div class="card-body">
                                <p>Have a project in mind? Let's discuss how we can help bring your ideas to life.</p>
                                <a href="contact.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-envelope"></i> Contact Us
                                </a>
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

</body>
</html>
