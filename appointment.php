<?php 
$page = 'appointment';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get available services for the dropdown
$services = $db->fetchAll("SELECT DISTINCT name FROM services WHERE is_active = 1 ORDER BY name");

// Check for flash messages
$flashMessage = getFlashMessage();
?>

<?php require_once 'includes/navigation.php'; ?>

    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">Book an Appointment</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Book Appointment</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

    <section class="w3l-appointment py-5">
        <div class="container py-lg-5">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="appointment-form">
                        <div class="text-center mb-5">
                            <h3 class="hny-title mb-3">Schedule a Consultation</h3>
                            <p class="lead">Ready to discuss your project? Book a free consultation with our experts. We'll understand your requirements and provide tailored solutions.</p>
                        </div>

                        <?php if ($flashMessage): ?>
                            <div class="alert alert-<?php echo $flashMessage['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                                <?php echo htmlspecialchars($flashMessage['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-lg">
                            <div class="card-body p-5">
                                <form action="process_appointment.php" method="POST" id="appointmentForm">
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
                                            <label for="service_type" class="form-label">Service Interested In *</label>
                                            <select class="form-select" id="service_type" name="service_type" required>
                                                <option value="">Select a service...</option>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?php echo htmlspecialchars($service['name']); ?>">
                                                        <?php echo htmlspecialchars($service['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="General Consultation">General Consultation</option>
                                                <option value="Custom Solution">Custom Solution</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="preferred_date" class="form-label">Preferred Date *</label>
                                            <input type="date" class="form-control" id="preferred_date" name="preferred_date" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="preferred_time" class="form-label">Preferred Time *</label>
                                            <select class="form-select" id="preferred_time" name="preferred_time" required>
                                                <option value="">Select time...</option>
                                                <option value="09:00">09:00 AM</option>
                                                <option value="10:00">10:00 AM</option>
                                                <option value="11:00">11:00 AM</option>
                                                <option value="12:00">12:00 PM</option>
                                                <option value="14:00">02:00 PM</option>
                                                <option value="15:00">03:00 PM</option>
                                                <option value="16:00">04:00 PM</option>
                                                <option value="17:00">05:00 PM</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="alternative_date" class="form-label">Alternative Date (Optional)</label>
                                            <input type="date" class="form-control" id="alternative_date" name="alternative_date" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="alternative_time" class="form-label">Alternative Time (Optional)</label>
                                            <select class="form-select" id="alternative_time" name="alternative_time">
                                                <option value="">Select time...</option>
                                                <option value="09:00">09:00 AM</option>
                                                <option value="10:00">10:00 AM</option>
                                                <option value="11:00">11:00 AM</option>
                                                <option value="12:00">12:00 PM</option>
                                                <option value="14:00">02:00 PM</option>
                                                <option value="15:00">03:00 PM</option>
                                                <option value="16:00">04:00 PM</option>
                                                <option value="17:00">05:00 PM</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="message" class="form-label">Tell us about your project</label>
                                        <textarea class="form-control" id="message" name="message" rows="4" 
                                                  placeholder="Briefly describe your project requirements, budget range, timeline, etc."></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="terms" required>
                                            <label class="form-check-label" for="terms">
                                                I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="ppolicy.php" target="_blank">Privacy Policy</a>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="fa fa-calendar-check"></i> Book Appointment
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
                                    <h5>Quick Response</h5>
                                    <p>We'll confirm your appointment within 24 hours</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="info-card text-center p-4 bg-light rounded">
                                    <i class="fa fa-video fa-2x text-primary mb-3"></i>
                                    <h5>Flexible Meeting</h5>
                                    <p>In-person, video call, or phone consultation available</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="info-card text-center p-4 bg-light rounded">
                                    <i class="fa fa-gift fa-2x text-primary mb-3"></i>
                                    <h5>Free Consultation</h5>
                                    <p>Initial consultation is completely free with no obligations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Alternative -->
    <section class="w3l-contact-alternative bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h4>Prefer to talk directly?</h4>
                    <p class="mb-0">You can also call us directly or send an email. We're always happy to discuss your project requirements.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="tel:+260974297313" class="btn btn-outline-primary me-2 mb-2">
                        <i class="fa fa-phone"></i> Call Now
                    </a>
                    <a href="mailto:info@envisagezm.com" class="btn btn-outline-success mb-2">
                        <i class="fa fa-envelope"></i> Email Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="w3l-footer-22-main">
        <?php require_once 'includes/footer.php'; ?>
    </section>

    <style>
    .appointment-form .card {
        border: none;
        border-radius: 15px;
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

    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }
    </style>

    <script>
    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('appointmentForm');
        const preferredDate = document.getElementById('preferred_date');
        const alternativeDate = document.getElementById('alternative_date');

        // Disable weekends for appointment booking
        preferredDate.addEventListener('input', function() {
            const selectedDate = new Date(this.value);
            const dayOfWeek = selectedDate.getDay();
            
            if (dayOfWeek === 0 || dayOfWeek === 6) { // Sunday = 0, Saturday = 6
                alert('We are not available on weekends. Please select a weekday.');
                this.value = '';
            }
        });

        alternativeDate.addEventListener('input', function() {
            const selectedDate = new Date(this.value);
            const dayOfWeek = selectedDate.getDay();
            
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                alert('We are not available on weekends. Please select a weekday.');
                this.value = '';
            }
        });

        // Form submission handling
        form.addEventListener('submit', function(e) {
            const preferredDateValue = preferredDate.value;
            const preferredTimeValue = document.getElementById('preferred_time').value;
            
            if (!preferredDateValue || !preferredTimeValue) {
                e.preventDefault();
                alert('Please select both preferred date and time.');
                return;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Booking...';
            submitBtn.disabled = true;

            // Re-enable button after 3 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    });
    </script>

</body>
</html>
