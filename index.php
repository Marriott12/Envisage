<?php 
$page = 'index';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get dynamic content for homepage
$services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
$portfolioItems = $db->fetchAll("SELECT * FROM portfolio WHERE is_active = 1 AND is_featured = 1 ORDER BY sort_order ASC LIMIT 6");
$testimonials = $db->fetchAll("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3");
$teamMembers = $db->fetchAll("SELECT * FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3");
?>


<?php require_once 'includes/navigation.php'; ?>

<!--w3l-banner-slider-main-->
<section class="w3l-banner-slider-main">
	<div class="bannerhny-content">
		<!--/banner-slider-->
		<div class="content-baner-inf">
			<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
				<ol class="carousel-indicators">
					<li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
					<li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
					<li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
					<li data-target="#carouselExampleIndicators" data-slide-to="3"></li>
				</ol>
				<div class="carousel-inner">
					<div class="carousel-item active">
						<div class="container">
							<div class="carousel-caption">
								
									<h3>Creative <span class="b-ck">Agency </span></h3>
									<p>We will turn your problems into your advantages</p>
									<a href="about.php" class="banner-btn btn">Read More</a>
							</div>
						</div>
					</div>
					<div class="carousel-item item2">
						<div class="container">
							<div class="carousel-caption">
								<h3>Brand <span class="b-ck"> Identity </span></h3>
								<p>We are experts in design, development & implementation</p>
								<a href="about.php" class="banner-btn btn">Read More</a>
							</div>
						</div>
					</div>
					<div class="carousel-item item3">
						<div class="container">
							<div class="carousel-caption">
								<h3>Grow <span class="b-ck">Business</span></h3>
								<p>We will turn your problems into your advantages</p>
								<a href="about.php" class="banner-btn btn">Read More</a>
							</div>
						</div>
					</div>
					<div class="carousel-item item4">
						<div class="container">
							<div class="carousel-caption">
									<h3>Digital <span class="b-ck">Solutions</span></h3>
									<p>We are experts in design, development & implementation</p>
									<a href="about.php" class="banner-btn btn">Read More</a>
							</div>
						</div>
					</div>
				</div>
				<a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
					<span class="carousel-control-prev-icon" aria-hidden="true"></span>
					<span class="sr-only">Previous</span>
				</a>
				<a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
					<span class="carousel-control-next-icon" aria-hidden="true"></span>
					<span class="sr-only">Next</span>
				</a>
			</div>
		</div>
		<!--//banner-slider-->
	</div>
	<!--/featured-grids -->
    <!--//featured-grids -->
</section>
<!-- //w3l-banner-slider-main -->

<section class="w3l-content-6">
	<!-- /content-6-section -->
	  <div class="content-6-mian py-5">
			 <div class="container py-lg-5">
					 <div class="content-info-in row">
							 <div class="content-gd col-lg-4">
									 <h3 class="hny-title">
										We exist to create 
things people love <span class="dot-1">.</span></h3>
							 </div>
							 <div class="content-gd col-lg-4">
									 <p>The world of technology can be fast-paced and scary. That's why our goal is to provide an experience that is tailored to your company's needs. No matter the budget, we pride ourselves on providing professional customer service. We guarantee you will be satisfied with our work. Our IT Services are top notch.</p>
							 </div>
							 <div class="content-gd col-lg-4">
									 <p>Envisage’s digital business strategy services helps you transform your business by leveraging digital technologies. We guide you through the maze of device proliferation and show you how to leverage data for a more efficient workforce, faster time-to-market and richer customer experience. With the help of our digital strategy framework, organizations can make sense of digital disruption and outpace competition. </p>
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
						<div class="counter-6-inf-left col-lg-6">
							<h3 class="hny-title two">Leading the way in digital marketing<span class="dot-1">.</span></h3>
						
						</div>
						<div class="counter-6-inf-right col-lg-6">
								<div class="specification">
										<div class="specification-icon">
												<span class="fa fa-pencil-square-o"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Design & Developing</a></h6>
											<p>A Design is a Brand's Ambassador<br/>
											This is the first step where a customer takes notice of you. Everything else follows. Customers will be motivated to the next level of action if the design and communication match their needs, are targeted to their requirements. At Envisage, therefore, we place utmost need on design. To ensure you have your unique design suiting your specific needs to ensure returning visitors</p>
										</div>
								
								</div>
								<div class="specification">
										<div class="specification-icon">
												<span class="fa fa-television"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Fully Responsive</a></h6>
											<p>Responsive Web Design makes your web page look good on all devices (desktops, tablets, and phones). We design websites for all screen sizes.</p>
										</div>
								
								</div>
								<div class="specification">
										<div class="specification-icon">
												<span class="fa fa-podcast"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Fast Support</a></h6>
											<p>As Envisage we provide you with fast support to ensure you have everything you need to do your business and succeed.</p>
										</div>
								</div>
								<div class="specification last-one">
										<div class="specification-icon">
												<span class="fa fa-lightbulb-o"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Our innovation</a></h6>
											<p>Our excellent business solutions developed on CMS Driven Websites, technologies like PHP, Wordpress and various others have enabled us to provide all our clients with effective websites, web applications and software to support the growth of their business online.</p>
										</div>
								</div>
						</div>
					</div>
				 </div>
			 </div>
	 </section>
   <!-- //specification-6-->
  

<section class="w3l-content-w-photo-6">
	<!-- /specification-6-->
	  <div class="content-photo-6-mian py-5">
			 <div class="container py-lg-5">
					<div class="align-photo-6-inf-cols row">
						
						<div class="photo-6-inf-right col-lg-7">
								<h3 class="hny-title text-left">Easy steps to having an awesome website<span class="dot-1">.</span></h3>

								<div class="row grids-innf">
								<div class="specification col-md-6">
										<div class="specification-icon">
												<span class="fa fa-briefcase"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Business Strategy</a></h6>
											<p>Get your business to make profits you want plus more with the right brand and approach.</p>
										</div>
								
								</div>
								<div class="specification col-md-6">
										<div class="specification-icon">
												<span class="fa fa-cubes"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Website Development</a></h6>
											<p>Website Design, UI/UX Design, Ecommerce Development, API Integrations, WordPress Development. Get a website specially tailored for your specific needs to meet your target clientele.</p>
										</div>
								
								</div>
								<div class="specification col-md-6">
										<div class="specification-icon">
												<span class="fa fa-line-chart"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Marketing &amp; Reporting</a></h6>
											<p>With the right marketing your business will excel further and you can track all the progress.</p>
										</div>
								
								</div>
								<div class="specification last-one col-md-6">
										<div class="specification-icon">
												<span class="fa fa-mobile"></span>
										</div>
										<div class="specification-info">	
											<h6><a href="#">Mobile App Development</a></h6>
											<p>Be accessible anywhere by anyone with a mobile application. Android or iOS.</p>
										</div>
								</div>
							</div>
						</div>
						<div class="photo-6-inf-left col-lg-5">
								<img src="assets/images/Ma.jpg" alt="Marriott" class="img-fluid">
						</div>
					</div>
				 </div>
			 </div>
	 </section>
   <!-- //specification-6-->
     

<section class="w3l-counter-6">
	<!-- /counter-6-->
	<div class="conuter-66-info py-5">
		<div class="container py-lg-5">
			<div class="counter-grids-info row">
				<div class="counter-gd col-lg-3">
					<label>TRUST US</label>
					<h4>Our Fun Facts</h4>
				</div>
				<div class="counter-gd col-lg-3">
					<h6>50+</h6>
					<p>Campaigns</p>
				</div>
				<div class="counter-gd col-lg-3">
					<h6>100+</h6>
					<p>Global Customer</p>
				</div>
				<div class="counter-gd col-lg-3">
					<h6>200+</h6>
					<p>Completed Projects</p>
				</div>
			</div>
		</div>
	  </div>
</section>
<!-- //counter-6-->

<!-- portfolio -->
<section class="w3-gallery">
	<div class="porfolio-inf py-5">
		<div class="container py-lg-5">
                <h3 class="hny-title text-center">Our Portfolio <span class="dot-1">.</span></h3>
			<ul class="portfolio-categ filter my-md-5 my-4 p-0 text-center">
				<li class="port-filter all active">
					<a href="#">All</a>
				</li>
				<li class="cat-item-1">
					<a href="#" title="Category 1">Logos</a>
				</li>
				<li class="cat-item-2">
					<a href="#" title="Category 2">Web Design</a>
				</li>
				<li class="cat-item-3">
					<a href="#" title="Category 3">Creative</a>
				</li>
				<li class="cat-item-4">
					<a href="#" title="Category 4">Portfolio</a>
				</li>
			</ul>
			<ul class="portfolio-area clearfix p-0 m-0 row">
				<li class="portfolio-item2 content" data-id="id-1" data-type="cat-item-1">
					<span class="image-block">
					
						<a class="image-zoom" href="assets/images/BTV.png" data-gal="prettyPhoto[gallery]">
							<div class="content-overlay"></div>
							<img src="assets/images/BTV.png" class="img-fluid" alt="portfolio-img">
						
							<div class="content-details fadeIn-bottom">
								<h3 class="content-title">Bauleni TV</h3>
								
							</div>
						</a>
					</span>
				</li>
				<li class="portfolio-item2 content" data-id="id-2" data-type="cat-item-1">
					<span class="image-block">
		
						<a class="image-zoom" href="assets/images/Kazaro.png" data-gal="prettyPhoto[gallery]">
							<div class="content-overlay"></div>
							<img src="assets/images/Kazaro.png" class="img-fluid" alt="portfolio-img">
							<div class="content-details fadeIn-bottom">
								<h3 class="content-title">Kazaro Loans</h3>
								
							</div>
						</a>
					</span>
				</li>
				<li class="portfolio-item2 content" data-id="id-8" data-type="cat-item-1">
					<span class="image-block">
		
						<a class="image-zoom" href="assets/images/van.png" data-gal="prettyPhoto[gallery]">
							<div class="content-overlay"></div>
							<img src="assets/images/van.png" class="img-fluid" alt="portfolio-img">
							<div class="content-details fadeIn-bottom">
								<h3 class="content-title">Van Electrical Suppliers & Installer</h3>
								
							</div>
						</a>
					</span>
				</li>
				<li class="portfolio-item2 content" data-id="id-7" data-type="cat-item-2">
					<span class="image-block">
					
						<a class="image-zoom" href="assets/images/Geospatial.png" data-gal="prettyPhoto[gallery]">
							<div class="content-overlay"></div>
							<img src="assets/images/Geospatial.png" class="img-fluid" alt="portfolio-img">
							<div class="content-details fadeIn-bottom">
						<a href="https://www.geospatialengineeringlimited.com/" target="blank">
								<h3 class="content-title">Geospatial Engineering Limited. Lusaka, Zambia</h3></a>
								
							</div>
						</a>
					</span>
				</li>
				<li class="portfolio-item2 content" data-id="id-2" data-type="cat-item-2">
						<span class="image-block">
							<a class="image-zoom" href="assets/images/dbtc.png" data-gal="prettyPhoto[gallery]">
							<div class="content-overlay"></div>
							<img src="assets/images/dbtc.png" class="img-fluid" alt="portfolio-img">
							<div class="content-details fadeIn-bottom">
						<a href="http://www.dbtchwange.co.zw/" target="blank">
								<h3 class="content-title">Don Bosco Technical College. Hwange, Zimbabwe</h3></a>
								
							</div>
							</a>
						</span>
					</li>
				<li class="portfolio-item2 content" data-id="id-2" data-type="cat-item-2">
						<span class="image-block">
							<a class="image-zoom" href="assets/images/busa.png" data-gal="prettyPhoto[gallery]">
							<div class="content-overlay"></div>
							<img src="assets/images/busa.png" class="img-fluid" alt="portfolio-img">
							<div class="content-details fadeIn-bottom">
						<a href="https://bauleniunitedsportsacademy.org/" target="blank">
								<h3 class="content-title">Bauleni United Sports Academy. Lusaka, Zambia</h3></a>
								
							</div>
							</a>
						</span>
					</li>
			</ul>
			<!--end portfolio-area -->
		</div>
		<!-- //gallery container -->
	</div>
</section>


<!-- //portfolio -->
<section class="w3l-progress-6">
	<!-- /counter-6-->
	<div class="progress-66-info py-5">
		<div class="container py-lg-5">
			<div class="counter-grids-info row">

				<div class="counter-gd col-lg-6 offset-lg-6">
						<label>Welcome On Board</label>
						<h4>Join Our user Family</h4>
						<p>Never miss a thing.</p>
					<div class="bars-main-info mt-lg-5 mt-4">
						<div class="progress-info mb-4">
							<h6 class="progress-tittle">Activity Level</h6>
							<div class="progress">
								<div class="progress-bar progress-bar-striped" role="progressbar" style="width: 90%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100">
								</div>
							</div>
						</div>
						<div class="progress-info mb-4">
							<h6 class="progress-tittle">Satisfaction</h6>
							<div class="progress">
								<div class="progress-bar progress-bar-striped" role="progressbar" style="width: 95%" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	  </div>
</section>
<!-- //counter-6-->

<section class="w3l-customers-6">
	<!--/customers -->
	<div class="customers-6-infhny py-5">
		<div class="container py-lg-5">
			<h3 class="hny-title text-center">Our Partners <span class="dot-1">.</span></h3>
			<div class="customer-inner row mt-lg-5 mt-4">
				<div class="customer-gd col-lg-4 col-md-6">
					<div class="card text-left">
					<span class="image-block">
							<div class="content-overlay"></div>
							<img src="assets/images/Hardlight.png" class="img-fluid" alt="portfolio-img">
							<div class="content-details fadeIn-bottom">
						<!--<a href="https://www.geospatialengineeringlimited.com/" target="blank">-->
							<h4 class="content-title">Hardlight Photography Inc. Lusaka, Zambia</h4>
							</div>
						<!--</a>-->
					</span>
					</div>
				</div>
				




			</div>
		</div>
		<!--//customers -->
</section>
<!-- //customers-6-->
<section class="w3l-footer-22-main">
    <!-- footer-22 -->
    <?php require_once 'includes/footer.php'; ?>
