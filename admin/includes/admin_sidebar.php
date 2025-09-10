<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="services.php">
                    <i class="fas fa-cogs"></i> Services
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'active' : ''; ?>" href="portfolio.php">
                    <i class="fas fa-briefcase"></i> Portfolio
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'active' : ''; ?>" href="team.php">
                    <i class="fas fa-users"></i> Team
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'active' : ''; ?>" href="testimonials.php">
                    <i class="fas fa-quote-left"></i> Testimonials
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active' : ''; ?>" href="pages.php">
                    <i class="fas fa-file-alt"></i> Pages
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>" href="contacts.php">
                    <i class="fas fa-envelope"></i> Contact Messages
                    <?php
                    global $db;
                    $unreadCount = $db->fetch("SELECT COUNT(*) as count FROM contact_submissions WHERE is_read = 0")['count'];
                    if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['quote-requests.php', 'quotations.php', 'quote-generator.php']) ? 'active' : ''; ?>" href="quote-requests.php">
                    <i class="fas fa-file-invoice-dollar"></i> Quotations
                    <?php
                    global $db;
                    $pendingQuotes = $db->fetch("SELECT COUNT(*) as count FROM quote_requests WHERE status = 'pending'")['count'];
                    if ($pendingQuotes > 0): ?>
                        <span class="badge bg-warning ms-1"><?php echo $pendingQuotes; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? 'active' : ''; ?>" href="newsletter.php">
                    <i class="fas fa-mail-bulk"></i> Newsletter
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Quick Links</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo SITE_URL; ?>" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="backup.php">
                    <i class="fas fa-download"></i> Backup Data
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 56px; /* Height of navbar */
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    color: #333;
    padding: 0.75rem 1rem;
}

.sidebar .nav-link:hover {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
    font-weight: 500;
}

.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
}

@media (max-width: 767.98px) {
    .sidebar {
        top: 5rem;
    }
}
</style>
