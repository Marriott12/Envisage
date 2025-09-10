<?php 
$page = 'about';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get dynamic content for about page
$teamMembers = $db->fetchAll("SELECT * FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
$testimonials = $db->fetchAll("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3");
?>

<?php require_once 'includes/navigation.php'; ?>
<!--//top-header-content-->


    <section class="w3l-inner-page-main">
      <div class="breadcrumb-infhny">
        <div class="container">
          <nav aria-label="breadcrumb">
            <h2 class="hny-title text-center">About Us</h2>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="http://envisagezm.com/">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
          </nav>
        </div>
      </div>
    </section>



<section class="w3l-wecome-content-6">
	<!-- /content-6-section -->
	  <div class="ab-content-6-mian py-5">
			 <div class="container py-lg-5">
					<div class="welcome-grids row">
							<div class="col-lg-6 mb-lg-0 mb-5">
								<h3 class="hny-title">Hello Buddy..!
									<br>Welcome to Envisage Technology Zambia<span class="dot-1">.</span></h3>
								<p class="my-4">Envisage Technology Zambia was founded in 2019 and is located in Lusaka, Zambia. The company specialises in Application Development, Application Maintenance, Website Development, Business Branding, Digital Marketing, Graphic Designing etc.</p>	
							</div>
							<div class="col-lg-3 col-md-4 col-6 welcome-image">
								<img src="assets/images/ab1.jpg" class="img-fluid" alt="" />
							</div>	
							<div class="col-lg-3 col-md-4 col-6 welcome-image">
									<img src="assets/images/ab2.jpg" class="img-fluid" alt="" />
							</div>
						</div>	
				 
				 </div>
			 </div>
	 </section>
   <!-- //content-6-section -->

  
<section class="w3l-specification-6">
	<!-- /specification-6-->
	  <div class="specification-6-mian">
			 <div class="container-fluid">
					<div class="align-counter-6-inf-cols row">
						<div class="counter-6-inf-left2 col-lg-6">
						
						</div>
						<div class="counter-6-inf-right counter-6-inf-right2 col-lg-6">
								<h3 class="hny-title">Design & Development</h3>
								<p class="pr-lg-5">We design & develop systems that suit your needs as per your specifications
								<div class="calltoaction-text-info mt-lg-5 mt-4">
										<div class="column-1">
											<a href="#feature"><span class="fa fa-snowflake-o" aria-hidden="true"></span> Data Analytics</a>
											<a href="#feature"><span class="fa fa-snowflake-o" aria-hidden="true"></span> Technical SEO</a>
											<a href="#feature"><span class="fa fa-snowflake-o" aria-hidden="true"></span> Digital Marketing</a>
										</div>
										<div class="column-1">
											<a href="#feature"><span class="fa fa-snowflake-o" aria-hidden="true"></span> Report Analysis</a>
											<a href="#feature"><span class="fa fa-snowflake-o" aria-hidden="true"></span> Data Development</a>
									   </div>
									</div>
					</div>
				 </div>
			 </div>
	 </section>
   <!-- //specification-6-->
  

<section class="w3l-content-12-main">
	<!-- /content-6-section -->
	<div class="content-12 text-left py-5">
		<div class="container py-lg-5">
			<div class="content-info-tabs">
				<input id="tab1" type="radio" name="tabs" checked>
				<label class="tabtle" for="tab1">Digital Media</label>
				<input id="tab2" type="radio" name="tabs">
				<label class="tabtle" for="tab2">Mobile Apps</label>
				<input id="tab3" type="radio" name="tabs">
				<label class="tabtle" for="tab3">Branding</label>
				<section id="content1" class="tab-content">
					<div class="row content12 align-items-center">
						<div class="col-lg-6 column">
							<h6 class="content-heading">Customized digital media solutions</h6>
							<p class="content-para">We provide solutions that are customized based on your specific needs to meet you target audience and make sure you improve your profit rates.</p>
						</div>
						<div class="col-lg-6 column">
							<img src="assets/images/tab1.jpg" class="img-fluid" alt="">
						</div>

					</div>
				</section>
				<section id="content2" class="tab-content">
					<div class="row content12 align-items-center">
						<div class="col-lg-6 column">
							<h6 class="content-heading">Responsive web design experts</h6>
							<p class="content-para">Our designs are 100% responsive to be accessed from any handset to ensure you meet your audience regardless of the type of handset they're using.</p>
						</div>
						<div class="col-lg-6 column">
							<img src="assets/images/tab2.jpg" class="img-fluid" alt="">
						</div>
					</div>
				</section>
				<section id="content3" class="tab-content">
					<div class="row content12 align-items-center">
						<div class="col-lg-6 column">
							<h6 class="content-heading">Custom application development</h6>
							<p class="content-para">We develop systems as per your specifications to ensure customer satisfaction.</p>
						</div>
						<div class="col-lg-6 column">
							<img src="assets/images/tab3.jpg" class="img-fluid" alt="">
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</section>
<!-- //content-6-section -->


<section class="w3l-content-5">
	<!-- /content-6-section -->
	  <div class="content-5-main">
			 <div class="container">
					 <div class="content-info-in row">
							 <div class="content-gd col-lg-6">
									 <h3 class="hny-title two">
										We exist to create 
things people love <span class="dot-1">.</span></h3>
							 </div>
							 <div class="content-gd col-lg-6">
									<p>What you can dream of we are here to bring it to reality</p>
									
							 </div>
							
					 </div>
				 
				 </div>
			 </div>
	 </section>
   <!-- //content-6-section -->

  
	<!--/team-sec-->
	<section class="w3l-team-main">
		<div class="team py-5">
			<div class="container py-lg-5">
					<h3 class="hny-title text-center">
							Skilled Team <span class="dot-1">.</span></h3>
					<div class="row team-row mt-5">
							<div class="col-lg-4 col-md-6 team-wrap">
								<div class="team-member text-center">
									<div class="team-img">
										<img src="assets/images/Ma.jpg" alt="">
										<div class="overlay-team">
											<div class="team-details text-center">
												<div class="socials mt-20">
													<a href="https://web.facebook.com/marriott.mariostar" target="blank">
														<span class="fa fa-facebook-f"></span>
													</a>
													<a href="https://twitter.com/Mariostar22" target="blank">
														<span class="fa fa-twitter"></span>
													</a>
													<a href="https://myaccount.google.com/?utm_source=OGB&tab=kk1&utm_medium=app" target="blank">
														<span class="fa fa-google-plus"></span>
													</a>
													<a href="https://www.linkedin.com/in/marriottgiftmumba/" target="blank">
														<span class="fa fa-instagram"></span>
													</a>
													<a href="https://in.pinterest.com/marriottmumba/" target="blank">
														<span class="fa fa-pinterest"></span>
													</a>
												</div>
											</div>
										</div>
									</div>
									<h6 class="team-title"><a href="Marriott CV.pdf" target="blank">Marriott Gift Mumba</a></h6>
									<p>CEO</p>
								</div>
							</div>
							<!-- end team member -->
				
							<div class="col-lg-4 col-md-6 team-wrap mt-sm-0 mt-5">
								<div class="team-member text-center">
									<div class="team-img">
										<img src="assets/images/eybo.jpg" alt="">
										<div class="overlay-team">
											<div class="team-details text-center">
													<div class="socials mt-20">
															<a href="https://web.facebook.com/abel.bwalya" target="blank">
																<span class="fa fa-facebook-f"></span>
															</a>
														</div>
											</div>
										</div>
									</div>
									<h6 class="team-title">Abel Bwalya</h6>
									<p>COO</p>
								</div>
							</div>
							<!-- end team member -->
				
							<div class="col-lg-4 col-md-6 team-wrap mt-md-0 mt-5">
								<div class="team-member last text-center">
									<div class="team-img">
										<img src="assets/images/user.png" alt="">
										<div class="overlay-team">
											<div class="team-details text-center">
													<div class="socials mt-20">
															<a href="#">
																<span class="fa fa-facebook-f"></span>
															</a>
															<a href="#">
																<span class="fa fa-twitter"></span>
															</a>
															<a href="#">
																<span class="fa fa-google-plus"></span>
															</a>
															<a href="#">
																<span class="fa fa-instagram"></span>
															</a>
														</div>
											</div>
										</div>
									</div>
									<h6 class="team-title">Gerald</h6>
									<p>Software engineer </p>
								</div>
							</div>
							<!-- end team member -->
				</div>
			</div>
	</section>
	<!--//team-sec-->
<section class="w3l-footer-22-main">
    <!-- footer-22 -->
    <?php require_once 'includes/footer.php'; ?>