<?php 
$page = 'blog-post';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get the blog post slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Get the blog post
$post = $db->fetch("
    SELECT * FROM blog_posts 
    WHERE slug = ? AND status = 'published'
", [$slug]);

if (!$post) {
    header('Location: 404.php');
    exit;
}

// Get related posts
$related_posts = $db->fetchAll("
    SELECT title, slug, created_at, featured_image 
    FROM blog_posts 
    WHERE status = 'published' AND id != ? 
    ORDER BY created_at DESC 
    LIMIT 3
", [$post['id']]);
?>

<?php require_once 'includes/navigation.php'; ?>

    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center"><?php echo htmlspecialchars($post['title']); ?></h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
              <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($post['title']); ?></li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

    <section class="w3l-blog-post py-5">
        <div class="container py-lg-5">
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <article class="blog-post">
                        <!-- Featured Image -->
                        <?php if ($post['featured_image']): ?>
                            <div class="post-image mb-4">
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                     class="img-fluid rounded" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </div>
                        <?php endif; ?>

                        <!-- Post Meta -->
                        <div class="post-meta mb-4">
                            <div class="d-flex flex-wrap align-items-center text-muted">
                                <span class="me-3">
                                    <i class="fa fa-calendar"></i> 
                                    <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                </span>
                                <span class="me-3">
                                    <i class="fa fa-clock-o"></i> 
                                    <?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> min read
                                </span>
                                <span>
                                    <i class="fa fa-user"></i> 
                                    Envisage Technology Team
                                </span>
                            </div>
                        </div>

                        <!-- Post Title -->
                        <h1 class="post-title mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>

                        <!-- Post Excerpt -->
                        <?php if ($post['excerpt']): ?>
                            <div class="post-excerpt lead mb-4">
                                <?php echo htmlspecialchars($post['excerpt']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Post Content -->
                        <div class="post-content">
                            <?php echo $post['content']; ?>
                        </div>

                        <!-- Social Share -->
                        <div class="social-share mt-5 pt-4 border-top">
                            <h5>Share this post:</h5>
                            <div class="social-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'blog-post.php?slug=' . $post['slug']); ?>" 
                                   target="_blank" class="btn btn-facebook btn-sm me-2">
                                    <i class="fa fa-facebook"></i> Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'blog-post.php?slug=' . $post['slug']); ?>&text=<?php echo urlencode($post['title']); ?>" 
                                   target="_blank" class="btn btn-twitter btn-sm me-2">
                                    <i class="fa fa-twitter"></i> Twitter
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . 'blog-post.php?slug=' . $post['slug']); ?>" 
                                   target="_blank" class="btn btn-linkedin btn-sm me-2">
                                    <i class="fa fa-linkedin"></i> LinkedIn
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode($post['title']); ?>&body=<?php echo urlencode(SITE_URL . 'blog-post.php?slug=' . $post['slug']); ?>" 
                                   class="btn btn-secondary btn-sm">
                                    <i class="fa fa-envelope"></i> Email
                                </a>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="post-navigation mt-5 pt-4 border-top">
                            <div class="row">
                                <div class="col-6 text-start">
                                    <a href="blog.php" class="btn btn-outline-primary">
                                        <i class="fa fa-arrow-left"></i> Back to Blog
                                    </a>
                                </div>
                                <div class="col-6 text-end">
                                    <a href="contact.php" class="btn btn-primary">
                                        Contact Us <i class="fa fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="sidebar">
                        <!-- Related Posts -->
                        <?php if (!empty($related_posts)): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Related Posts</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($related_posts as $related): ?>
                                        <div class="d-flex mb-3">
                                            <?php if ($related['featured_image']): ?>
                                                <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                                     class="me-3" 
                                                     alt="<?php echo htmlspecialchars($related['title']); ?>"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="blog-post.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($related['title']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($related['created_at'])); ?>
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

                        <!-- Contact CTA -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Need Our Services?</h5>
                            </div>
                            <div class="card-body text-center">
                                <p>Ready to transform your business with technology? Let's discuss your project.</p>
                                <a href="contact.php" class="btn btn-primary">
                                    <i class="fa fa-phone"></i> Get In Touch
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

    <style>
    .btn-facebook { background-color: #3b5998; border-color: #3b5998; color: white; }
    .btn-twitter { background-color: #1da1f2; border-color: #1da1f2; color: white; }
    .btn-linkedin { background-color: #0077b5; border-color: #0077b5; color: white; }
    
    .post-content {
        line-height: 1.8;
        font-size: 1.1rem;
    }
    
    .post-content h2, .post-content h3, .post-content h4 {
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    
    .post-content p {
        margin-bottom: 1.5rem;
    }
    
    .post-content ul, .post-content ol {
        margin-bottom: 1.5rem;
        padding-left: 2rem;
    }
    
    .post-content blockquote {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
        margin: 2rem 0;
        font-style: italic;
        background-color: #f8f9fa;
        padding: 1rem;
    }
    </style>

</body>
</html>
