<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get analytics data
$analytics = [
    'total_contacts' => $db->fetch("SELECT COUNT(*) as count FROM contact_submissions")['count'],
    'total_newsletter' => $db->fetch("SELECT COUNT(*) as count FROM newsletter_subscribers")['count'],
    'total_services' => $db->fetch("SELECT COUNT(*) as count FROM services WHERE is_active = 1")['count'],
    'total_portfolio' => $db->fetch("SELECT COUNT(*) as count FROM portfolio WHERE is_active = 1")['count'],
    'total_blog_posts' => $db->fetch("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")['count'],
    'total_testimonials' => $db->fetch("SELECT COUNT(*) as count FROM testimonials WHERE is_active = 1")['count']
];

// Get monthly data for charts
$monthly_contacts = $db->fetchAll("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM contact_submissions 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

$monthly_newsletter = $db->fetchAll("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM newsletter_subscribers 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

// Recent activities
$recent_contacts = $db->fetchAll("
    SELECT name, email, subject, created_at 
    FROM contact_submissions 
    ORDER BY created_at DESC 
    LIMIT 10
");

$recent_subscribers = $db->fetchAll("
    SELECT email, created_at 
    FROM newsletter_subscribers 
    ORDER BY created_at DESC 
    LIMIT 10
");

$pageTitle = "Analytics Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white p-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <h1 class="h3 mb-4">
                        <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
                    </h1>

                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card border-0 shadow-sm h-100 bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-2x mb-2"></i>
                                    <h3><?php echo number_format($analytics['total_contacts']); ?></h3>
                                    <p class="mb-0">Total Contacts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card border-0 shadow-sm h-100 bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-newspaper fa-2x mb-2"></i>
                                    <h3><?php echo number_format($analytics['total_newsletter']); ?></h3>
                                    <p class="mb-0">Newsletter Subs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card border-0 shadow-sm h-100 bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-cogs fa-2x mb-2"></i>
                                    <h3><?php echo number_format($analytics['total_services']); ?></h3>
                                    <p class="mb-0">Services</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card border-0 shadow-sm h-100 bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-briefcase fa-2x mb-2"></i>
                                    <h3><?php echo number_format($analytics['total_portfolio']); ?></h3>
                                    <p class="mb-0">Portfolio Items</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card border-0 shadow-sm h-100 bg-danger text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-blog fa-2x mb-2"></i>
                                    <h3><?php echo number_format($analytics['total_blog_posts']); ?></h3>
                                    <p class="mb-0">Blog Posts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card border-0 shadow-sm h-100 bg-secondary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-star fa-2x mb-2"></i>
                                    <h3><?php echo number_format($analytics['total_testimonials']); ?></h3>
                                    <p class="mb-0">Testimonials</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row mb-4">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Monthly Contacts
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="contactsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Newsletter Growth
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="newsletterChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>Recent Contacts
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Subject</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_contacts as $contact): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($contact['subject'], 0, 30)); ?>...</td>
                                                    <td><?php echo date('M j', strtotime($contact['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="contacts.php" class="btn btn-sm btn-primary">View All Contacts</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users me-2"></i>Recent Subscribers
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Email</th>
                                                    <th>Subscribed</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_subscribers as $subscriber): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($subscriber['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="#" class="btn btn-sm btn-success">Manage Subscribers</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Contacts Chart
        const contactsCtx = document.getElementById('contactsChart').getContext('2d');
        const contactsChart = new Chart(contactsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_contacts, 'month')); ?>,
                datasets: [{
                    label: 'Contacts',
                    data: <?php echo json_encode(array_column($monthly_contacts, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Newsletter Chart
        const newsletterCtx = document.getElementById('newsletterChart').getContext('2d');
        const newsletterChart = new Chart(newsletterCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($monthly_newsletter, 'month')); ?>,
                datasets: [{
                    label: 'New Subscribers',
                    data: <?php echo json_encode(array_column($monthly_newsletter, 'count')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
