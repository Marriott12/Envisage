<?php 
$page = 'contact';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Check for flash messages
$flashMessage = getFlashMessage();
?>

<?php require_once 'includes/navigation.php'; ?>
    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">Contact</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>

	<!-- /contact-form -->
	<section class="w3l-contact-main">
		<div class="contact-infhny py-5">
			<div class="container">
				<div class="contact-grids row py-lg-5">
					<div class="contact-left col-lg-6">
							<img src="assets/images/contact-sec.jpg" alt="" class="img-fluid">
					</div>
					<div class="contact-right col-lg-6 pl-lg-4">
							<h3>Contact</h3>
						<h4>Everything Starts With A Hello!</h4>
						<p>We’re here to answer any questions you may have and create an effective solution for your instructional needs.</p>
						<?php if ($flashMessage): ?>
							<div class="alert alert-<?php echo $flashMessage['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
								<?php echo htmlspecialchars($flashMessage['message']); ?>
								<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							</div>
						<?php endif; ?>
						
						<form action="process_contact.php" method="post" class="signin-form mt-lg-5 mt-4">
							<div class="input-grids">
								<input type="text" name="name" placeholder="Full name" class="contact-input" required="" />
								<input type="email" name="email" placeholder="Your email" class="contact-input" required />
								<input type="text" name="subject" placeholder="Subject" class="contact-input" required="" />
								<input type="number" name="phone" placeholder="Phone number" class="contact-input" />
							</div>
							<div  class="form-input">
								<textarea name="message" placeholder="Type your message here" required=""></textarea>
							</div>
							<button class="btn submit">Send Message</button>
						</form>
					</div>

				</div>
			</div>
		</div>
		<div class="map-hny">
	    	<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3845.6648746101046!2d28.376462814271303!3d-15.44862861865959!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19408dc955486859%3A0xc3a0b8cf0a12779c!2sBauleni%20Market!5e0!3m2!1sen!2szm!4v1585222876598!5m2!1sen!2szm" width="600" height="450" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
	   </div>
	</section>
	<!-- //contact-form -->


<section class="w3l-footer-22-main">
    <!-- footer-22 -->
    <?php require_once 'includes/footer.php'; ?>