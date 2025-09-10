<?php 
$page = 'services';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get dynamic services content
$services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC");
?>

<?php require_once 'includes/navigation.php'; ?>


	<section class="w3l-inner-page-main">
		<div class="breadcrumb-infhny">
			<div class="container">
				<nav aria-label="breadcrumb">
					<h2 class="hny-title text-center">Services</h2>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item"><a href="http://envisagezm.com/">Home</a></li>
						<li class="breadcrumb-item active" aria-current="page">Services</li>
					</ol>
				</nav>
			</div>
		</div>
	</section>

	<section class="w3l-services-sec-6">
		<div class="services-sec-6-main py-5">
			<!-- /services-->
			<div class="container py-lg-5">
				<div class="row services-second-part">
					<div class="col-lg-6 ser-hny-grids">
						<h3 class="hny-title">What We Do <span class="dot-1">.</span></h3>
						<p>Envisage Technology Zambia specialises in Application Development, Application Maintenance, Website Development, Business Branding, Digital Marketing, Graphic Designing.</p>
						<div class="row sub-hny-grids mt-lg-5 mt-4">
							<div class="col-md-6 subhny-gd mt-md-0 mt-4">
								<h5>Strategy & Research.</h5>
								<p>Learning the market to understand and create a brand that meets the market's need.</p>
							</div>
							<div class="col-md-6 subhny-gd mt-md-0 mt-4">
								<h5>Design & Development.
								</h5>
								<p>Custom designs that are specifically developed to meet and satisfy your needs.</p>
							</div>
						</div>
						<a href="#" class="read-more-btn btn mt-lg-5 mt-4">Read More</a>
					</div>
					<div class="col-lg-6 ser-hny-img mt-lg-0 mt-5">
						<img src="assets/images/bg.jpg" class="img-fluid" alt="">
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- //services-->
	<!-- head -->

	<head>
		<link href="../bundles/css/style-starter.css" rel='stylesheet' type='text/css' /><!-- Style-CSS -->
		<link href="//fonts.googleapis.com/css?family=Poppins:100,300,400,500,500i,600,700&display=swap"
			rel="stylesheet">
	</head>
	<!-- //head -->
	<div id="page">
		<section class="features-4">
			<div class="features4-block py-5">
				<div class="container py-lg-5">
					<h6>We are the best</h6>
					<h3 class="hny-title mb-0 text-center">What We Are Offering <span class="dot-1">.</span></h3>
					<p class="what-para text-center mb-5">Are you looking to create an eye-catching, cutting edge
						website & mobile apps for your business?</p>
					<div class="features4-grids text-center row mt-5">
						<div class="col-lg-4 col-md-6 features4-grid">
							<div class="features4-grid-inn">
								<span class="fa fa-bullhorn icon-fea4" aria-hidden="true"></span>
								<h5><a href="#URL">Digital Campaign</a></h5>
								<p>Utilizing digital media to create a wider outreach for your market</p>

							</div>
						</div>
						<div class="col-lg-4 col-md-6 features4-grid sec-features4-grid">
							<div class="features4-grid-inn">
								<span class="fa fa-umbrella icon-fea4" aria-hidden="true"></span>
								<h5><a href="#URL">Advanced Security</a></h5>
								<p>Protecting your digital data to ensure client's safety</p>

							</div>
						</div>
						<div class="col-lg-4 col-md-6 features4-grid">
							<div class="features4-grid-inn">
								<span class="fa fa-lightbulb-o icon-fea4" aria-hidden="true"></span>
								<h5><a href="#URL">Easy Setup</a></h5>
								<p>Easy to use designs that help you work faster and smoother</p>

							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- features-4 -->
	</div>
	<section class="w3l-services-6">
		<!-- /content-6-section -->
		<div class="services-grids-6 py-5">
			<div class="container py-lg-5">
				<div class="row w3-icon-grid-gap1">
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-codepen" aria-hidden="true"></span>
							<h3>Web design</h3>
							<div class="clearfix"></div>
						</a>
						<p>Responsive Web designs</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-mobile" aria-hidden="true"></span>
							<h3>Mobile Apps</h3>
							<div class="clearfix"></div>
						</a>
						<p>Cross-platform mobile applications</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-hourglass" aria-hidden="true"></span>
							<h3>Animation</h3>
							<div class="clearfix"></div>
						</a>
						<p>Eye catching animations to effectively market your products</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-modx" aria-hidden="true"></span>
							<h3>Graphic Designing</h3>
							<div class="clearfix"></div>
						</a>
						<p>Professional designs for all your marketing needs.</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-bar-chart" aria-hidden="true"></span>
							<h3>Marketing</h3>
							<div class="clearfix"></div>
						</a>
						<p>Effective marketing to ensure increased sales and returning website visits</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-shopping-bag" aria-hidden="true"></span>
							<h3>Development</h3>
							<div class="clearfix"></div>
						</a>
						<p>Easy to use applications for efficient work</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-plug" aria-hidden="true"></span>
							<h3>Powerful template</h3>
							<div class="clearfix"></div>
						</a>
						<p>User friendly interfaces to attract more customers</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-television" aria-hidden="true"></span>
							<h3>Flexible Layouts</h3>
							<div class="clearfix"></div>
						</a>
						<p>Layouts of your dreams made possible</p>
					</div>
					<div class="col-md-4 col-sm-6 w3-icon-grid1">
						<a href="#link">
							<span class="fa fa-lightbulb-o" aria-hidden="true"></span>
							<h3>Fast processing</h3>
							<div class="clearfix"></div>
						</a>
						<p>Time is always limited that is why our applications are remarkably fast</p>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- //content-6-section -->
	<section class="w3l-services-2">
		<!-- /content-6-section -->
		<div class="services-2-mian py-5">
			<div class="container py-lg-5">
				<h6>Services</h6>
				<h3 class="hny-title mb-0 text-center">We provide solutions <br>to our clients<span
						class="dot-1">.</span></h3>
				<div class="welcome-grids row mt-5">
					<div class="col-md-6 welcome-image">
						<div class="boxhny13">
							<a href="#URL">
								<img src="assets/images/bg1.jpg" class="img-fluid" alt="" />
								<div class="boxhny-content">
									<h3 class="title">Unlimited Power</h3>
								</div>
							</a>
						</div>
						<h4><a href="#URL">Unlimited Power</a></h4>

					</div>
					<div class="col-md-6 welcome-image">
						<div class="boxhny13">
							<a href="#URL">
								<img src="assets/images/bg2.jpg" class="img-fluid" alt="" />
								<div class="boxhny-content">
									<h3 class="title">Regular Updates</h3>
								</div>
							</a>
						</div>
						<h4><a href="#URL">Effortless updates</a></h4>


					</div>
					<div class="col-md-6 welcome-image">
						<div class="boxhny13">
							<a href="#URL">
								<img src="assets/images/bg3.jpg" class="img-fluid" alt="" />
								<div class="boxhny-content">
									<h3 class="title">Creative Minds</h3>
								</div>
							</a>
						</div>
						<h4><a href="#URL">Creative minds</a></h4>


					</div>
					<div class="col-md-6 welcome-image">
						<div class="boxhny13">
							<a href="#URL">
								<img src="assets/images/bg4.jpg" class="img-fluid" alt="" />
								<div class="boxhny-content">
									<h3 class="title">Strong Empathy</h3>
								</div>
							</a>
						</div>
						<h4><a href="#URL">Strong empathy</a></h4>

					</div>
				</div>

			</div>
		</div>
	</section>
	<!-- //content-6-section -->
	<section class="w3l-footer-22-main">
		<!-- footer-22 -->
		<?php require_once 'includes/footer.php'; ?>