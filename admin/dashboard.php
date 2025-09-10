<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get dashboard statistics
$stats = [
    'total_services' => 0,
    'total_team_members' => 0,
    'total_portfolio' => 0,
    'total_testimonials' => 0,
    'unread_contacts' => 0,
    'newsletter_subscribers' => 0
];

// Get statistics with error handling
try {
    $stats['total_services'] = $db->fetch("SELECT COUNT(*) as count FROM services WHERE is_active = 1")['count'];
} catch (Exception $e) {
    $stats['total_services'] = 0;
}

try {
    $stats['total_team_members'] = $db->fetch("SELECT COUNT(*) as count FROM team_members WHERE is_active = 1")['count'];
} catch (Exception $e) {
    $stats['total_team_members'] = 0;
}

try {
    $stats['total_portfolio'] = $db->fetch("SELECT COUNT(*) as count FROM portfolio WHERE is_active = 1")['count'];
} catch (Exception $e) {
    $stats['total_portfolio'] = 0;
}

try {
    $stats['total_testimonials'] = $db->fetch("SELECT COUNT(*) as count FROM testimonials WHERE is_active = 1")['count'];
} catch (Exception $e) {
    $stats['total_testimonials'] = 0;
}

try {
    $stats['unread_contacts'] = $db->fetch("SELECT COUNT(*) as count FROM contact_submissions WHERE is_read = 0")['count'];
} catch (Exception $e) {
    $stats['unread_contacts'] = 0;
}

// Check if newsletter table exists
try {
    $stats['newsletter_subscribers'] = $db->fetch("SELECT COUNT(*) as count FROM newsletter_subscribers WHERE is_active = 1")['count'];
} catch (Exception $e) {
    $stats['newsletter_subscribers'] = 0;
}

// Recent contact submissions
try {
    $recentContacts = $db->fetchAll("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {
    $recentContacts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <?php $flashMessage = getFlashMessage(); ?>
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                        <?php echo $flashMessage['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_services']; ?></h4>
                                        <p class="card-text">Active Services</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-cogs fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_portfolio']; ?></h4>
                                        <p class="card-text">Portfolio Items</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-briefcase fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['total_team_members']; ?></h4>
                                        <p class="card-text">Team Members</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $stats['unread_contacts']; ?></h4>
                                        <p class="card-text">Unread Messages</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-envelope fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="services.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-plus"></i> Add Service
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="portfolio.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-plus"></i> Add Portfolio
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="team.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-plus"></i> Add Team Member
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="settings.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-cogs"></i> Site Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Contact Messages -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5>Recent Contact Messages</h5>
                                <a href="contacts.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentContacts)): ?>
                                    <p class="text-muted">No contact messages yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Subject</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentContacts as $contact): ?>
                                                    <tr class="<?php echo $contact['is_read'] ? '' : 'table-warning'; ?>">
                                                        <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                                        <td><?php echo htmlspecialchars(truncateText($contact['subject'], 30)); ?></td>
                                                        <td><?php echo formatDate($contact['created_at'], 'M j, Y'); ?></td>
                                                        <td>
                                                            <?php if ($contact['is_read']): ?>
                                                                <span class="badge bg-success">Read</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Unread</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>
