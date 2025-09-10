<?php
// Get site settings for dynamic data
$siteSettings = [];
$settings = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
foreach($settings as $setting) {
    $siteSettings[$setting['setting_key']] = $setting['setting_value'];
}

function getSetting($key, $default = '') {
    global $siteSettings;
    return isset($siteSettings[$key]) ? $siteSettings[$key] : $default;
}

function isActivePage($page) {
    $currentPage = getCurrentPage();
    return $currentPage === $page ? 'active' : '';
}
?>

<!--/top-header-content-->
<section class="w3l-top-header-content">
    <div class="hny-top-menu">
        <div class="top-hd">
            <div class="container-fluid">
                <div class="row">
                    <div class="social-top col-lg-6">
                        <li><a href="<?php echo getSetting('facebook_url', FACEBOOK_URL); ?>" target="_blank"><span class="fa fa-facebook"></span></a></li>
                        <li><a href="<?php echo getSetting('linkedin_url', LINKEDIN_URL); ?>" target="_blank"><span class="fa fa-linkedin"></span></a></li>
                        <li><a href="<?php echo getSetting('pinterest_url', PINTEREST_URL); ?>" target="_blank"><span class="fa fa-pinterest"></span></a></li>
                    </div>
                    <div class="accounts col-lg-6">
                        <li class="top_li">
                            <span class="fa fa-mobile"></span>
                            <a href="tel:<?php echo getSetting('contact_phone_1', PHONE_1); ?>"><?php echo getSetting('contact_phone_1', PHONE_1); ?></a>
                            <a href="tel:<?php echo getSetting('contact_phone_2', PHONE_2); ?>">/ <?php echo getSetting('contact_phone_2', PHONE_2); ?></a>
                        </li>
                        <li class="top_li">
                            <span class="fa fa-envelope-o"></span>
                            <a href="mailto:<?php echo getSetting('contact_email', EMAIL); ?>">Need help? Contact us via email</a>
                        </li>
                        <li class="top_li1">
                            <span class="fa fa-map-marker"></span> 
                            <?php echo getSetting('contact_address', ADDRESS); ?>
                        </li>
                    </div>
                </div>
            </div>
        </div>
        
        <!--/nav-->
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                    <label class="lohny">
                        <img src="<?php echo SITE_URL; ?>assets/images/favicon.png" height="42" width="42">
                    </label>
                    <?php echo getSetting('site_name', SITE_NAME); ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" 
                        aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item <?php echo isActivePage('index'); ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>index.php">Home</a>
                        </li>
                        <li class="nav-item <?php echo isActivePage('about'); ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>about.php">About</a>
                        </li>
                        <li class="nav-item <?php echo isActivePage('services'); ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>services.php">Services</a>
                        </li>
                        <li class="nav-item <?php echo isActivePage('portfolio'); ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>portfolio.php">Portfolio</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                More
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>blog.php">
                                    <i class="fa fa-blog me-2"></i>Blog
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>faq.php">
                                    <i class="fa fa-question-circle me-2"></i>FAQ
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>photography/index.php">
                                    <i class="fa fa-camera me-2"></i>Photography
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>terms.php">
                                    <i class="fa fa-file-text me-2"></i>Terms & Conditions
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>ppolicy.php">
                                    <i class="fa fa-shield me-2"></i>Privacy Policy
                                </a></li>
                            </ul>
                        </li>
                        <li class="nav-item <?php echo isActivePage('contact'); ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>contact.php">Contact</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white ms-2" href="<?php echo SITE_URL; ?>appointment.php">
                                <i class="fa fa-calendar"></i> Book Consultation
                            </a>
                        </li>
                        <li class="nav-item">
                            <div class="search-box">
                                <input type="text" placeholder="Search..." class="form-control form-control-sm">
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</section>
