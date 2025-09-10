<div class="sidebar p-3" style="min-height: 100vh;">
    <h5 class="text-center mb-4">
        <i class="fas fa-cog me-2"></i>Admin Panel
    </h5>
    
    <nav class="nav flex-column">
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary rounded' : ''; ?>" 
           href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'bg-primary rounded' : ''; ?>" 
           href="analytics.php">
            <i class="fas fa-chart-line me-2"></i>Analytics
        </a>
        
        <hr class="border-secondary">
        
        <small class="text-muted text-uppercase mb-2">Content Management</small>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'bg-primary rounded' : ''; ?>" 
           href="services.php">
            <i class="fas fa-cogs me-2"></i>Services
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'bg-primary rounded' : ''; ?>" 
           href="portfolio.php">
            <i class="fas fa-briefcase me-2"></i>Portfolio
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'bg-primary rounded' : ''; ?>" 
           href="team.php">
            <i class="fas fa-users me-2"></i>Team
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'testimonials.php' ? 'bg-primary rounded' : ''; ?>" 
           href="testimonials.php">
            <i class="fas fa-star me-2"></i>Testimonials
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'bg-primary rounded' : ''; ?>" 
           href="blog.php">
            <i class="fas fa-blog me-2"></i>Blog Posts
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'faqs.php' ? 'bg-primary rounded' : ''; ?>" 
           href="faqs.php">
            <i class="fas fa-question-circle me-2"></i>FAQs
        </a>
        
        <hr class="border-secondary">
        
        <small class="text-muted text-uppercase mb-2">Communications</small>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'bg-primary rounded' : ''; ?>" 
           href="contacts.php">
            <i class="fas fa-envelope me-2"></i>Contact Messages
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'bg-primary rounded' : ''; ?>" 
           href="appointments.php">
            <i class="fas fa-calendar-alt me-2"></i>Appointments
        </a>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? 'bg-primary rounded' : ''; ?>" 
           href="newsletter.php">
            <i class="fas fa-newspaper me-2"></i>Newsletter
        </a>
        
        <hr class="border-secondary">
        
        <small class="text-muted text-uppercase mb-2">Settings</small>
        
        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-primary rounded' : ''; ?>" 
           href="settings.php">
            <i class="fas fa-cog me-2"></i>Site Settings
        </a>
        
        <hr class="border-secondary">
        
        <a class="nav-link text-white" href="../index.php" target="_blank">
            <i class="fas fa-external-link-alt me-2"></i>View Website
        </a>
        
        <a class="nav-link text-white" href="logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </nav>
</div>

<style>
.nav-link {
    padding: 0.75rem 1rem;
    margin-bottom: 0.25rem;
    transition: all 0.3s ease;
    border-radius: 0.375rem;
}

.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: white !important;
}

.nav-link.bg-primary {
    background-color: #0d6efd !important;
}

.sidebar hr {
    margin: 1rem 0;
}

small.text-muted {
    font-weight: 600;
    letter-spacing: 0.5px;
}
</style>
