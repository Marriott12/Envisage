<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = sanitizeInput($_POST['status']);
                $admin_notes = sanitizeInput($_POST['admin_notes']);

                if ($id && in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
                    $result = $db->execute(
                        "UPDATE appointments SET status = ?, admin_notes = ? WHERE id = ?",
                        [$status, $admin_notes, $id]
                    );
                    
                    if ($result) {
                        $message = "Appointment status updated successfully!";
                        
                        // Send notification email to client
                        $appointment = $db->fetch("SELECT * FROM appointments WHERE id = ?", [$id]);
                        if ($appointment && $status === 'confirmed') {
                            $subject = "Appointment Confirmed - Envisage Technology";
                            $body = "
                            Dear {$appointment['name']},
                            
                            Your appointment has been confirmed!
                            
                            Service: {$appointment['service_type']}
                            Date: {$appointment['preferred_date']}
                            Time: {$appointment['preferred_time']}
                            
                            We look forward to meeting with you. Please arrive 5 minutes early.
                            
                            Best regards,
                            Envisage Technology Zambia Team
                            ";
                            
                            $headers = "From: info@envisagezm.com\r\n";
                            mail($appointment['email'], $subject, $body, $headers);
                        }
                    } else {
                        $error = "Error updating appointment status.";
                    }
                }
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                if ($id) {
                    $result = $db->execute("DELETE FROM appointments WHERE id = ?", [$id]);
                    if ($result) {
                        $message = "Appointment deleted successfully!";
                    } else {
                        $error = "Error deleting appointment.";
                    }
                }
                break;
        }
    }
}

// Get all appointments
$appointments = $db->fetchAll("
    SELECT * FROM appointments 
    ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1 
            WHEN 'confirmed' THEN 2 
            WHEN 'completed' THEN 3 
            WHEN 'cancelled' THEN 4 
        END,
        preferred_date ASC, preferred_time ASC
");

// Get statistics
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM appointments")['count'],
    'pending' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")['count'],
    'confirmed' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'")['count'],
    'completed' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")['count'],
    'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM appointments WHERE status = 'cancelled'")['count']
];

$pageTitle = "Appointment Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3"><i class="fas fa-calendar-alt me-2"></i><?php echo $pageTitle; ?></h1>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-primary"><?php echo $stats['total']; ?></h4>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-warning"><?php echo $stats['pending']; ?></h4>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-success"><?php echo $stats['confirmed']; ?></h4>
                                    <small>Confirmed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-info"><?php echo $stats['completed']; ?></h4>
                                    <small>Completed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-danger"><?php echo $stats['cancelled']; ?></h4>
                                    <small>Cancelled</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Table -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Service</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>Booking Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo $appointment['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($appointment['name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($appointment['email']); ?><br>
                                                    <?php echo htmlspecialchars($appointment['phone']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['service_type']); ?></td>
                                            <td>
                                                <strong><?php echo date('M j, Y', strtotime($appointment['preferred_date'])); ?></strong><br>
                                                <small><?php echo date('g:i A', strtotime($appointment['preferred_time'])); ?></small>
                                                <?php if ($appointment['alternative_date']): ?>
                                                    <br><small class="text-muted">Alt: <?php echo date('M j', strtotime($appointment['alternative_date'])); ?> 
                                                    <?php echo date('g:i A', strtotime($appointment['alternative_time'])); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'success',
                                                    'completed' => 'info',
                                                    'cancelled' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass[$appointment['status']]; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y g:i A', strtotime($appointment['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewAppointment(<?php echo htmlspecialchars(json_encode($appointment)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="updateAppointment(<?php echo htmlspecialchars(json_encode($appointment)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteAppointment(<?php echo $appointment['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <div class="modal fade" id="viewAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="appointmentDetails">
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Appointment Modal -->
    <div class="modal fade" id="updateAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="updateAppointmentForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="updateAppointmentId">
                        
                        <div class="mb-3">
                            <label for="updateStatus" class="form-label">Status</label>
                            <select class="form-select" name="status" id="updateStatus" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="updateAdminNotes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" name="admin_notes" id="updateAdminNotes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAppointment(appointment) {
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Client Information</h6>
                        <p><strong>Name:</strong> ${appointment.name}</p>
                        <p><strong>Email:</strong> ${appointment.email}</p>
                        <p><strong>Phone:</strong> ${appointment.phone || 'Not provided'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Appointment Information</h6>
                        <p><strong>Service:</strong> ${appointment.service_type}</p>
                        <p><strong>Status:</strong> <span class="badge bg-secondary">${appointment.status}</span></p>
                        <p><strong>Booking Date:</strong> ${new Date(appointment.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6>Preferred Schedule</h6>
                        <p><strong>Date:</strong> ${new Date(appointment.preferred_date).toLocaleDateString()}</p>
                        <p><strong>Time:</strong> ${appointment.preferred_time}</p>
                        ${appointment.alternative_date ? `
                            <h6>Alternative Schedule</h6>
                            <p><strong>Date:</strong> ${new Date(appointment.alternative_date).toLocaleDateString()}</p>
                            <p><strong>Time:</strong> ${appointment.alternative_time}</p>
                        ` : ''}
                    </div>
                </div>
                ${appointment.message ? `
                    <div class="row">
                        <div class="col-12">
                            <h6>Client Message</h6>
                            <p>${appointment.message}</p>
                        </div>
                    </div>
                ` : ''}
                ${appointment.admin_notes ? `
                    <div class="row">
                        <div class="col-12">
                            <h6>Admin Notes</h6>
                            <p>${appointment.admin_notes}</p>
                        </div>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('appointmentDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('viewAppointmentModal')).show();
        }

        function updateAppointment(appointment) {
            document.getElementById('updateAppointmentId').value = appointment.id;
            document.getElementById('updateStatus').value = appointment.status;
            document.getElementById('updateAdminNotes').value = appointment.admin_notes || '';
            
            new bootstrap.Modal(document.getElementById('updateAppointmentModal')).show();
        }

        function deleteAppointment(id) {
            if (confirm('Are you sure you want to delete this appointment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
