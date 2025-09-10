<?php
// Get site settings for dynamic footer data
if (!isset($siteSettings)) {
    $siteSettings = [];
    $settings = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
    foreach($settings as $setting) {
        $siteSettings[$setting['setting_key']] = $setting['setting_value'];
    }
}

if (!function_exists('getSetting')) {
    function getSetting($key, $default = '') {
        global $siteSettings;
        return isset($siteSettings[$key]) ? $siteSettings[$key] : $default;
    }
}
?>

<!-- footer-22 -->
<div class="footer-hny py-5">
    <div class="container py-lg-4"> 
        <div class="sub-columns row">
            <div class="sub-one-left col-lg-4 col-md-6">
                <h6>About</h6>
                <p><?php echo getSetting('site_description', SITE_DESCRIPTION); ?></p>
            </div>
            
            <div class="sub-two-right col-lg-4 col-md-6 my-md-0 my-5">
                <h6>Quick links</h6>
                <div class="footer-hny-ul">
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>about.php">About</a></li>
                        <li><a href="<?php echo SITE_URL; ?>services.php">Services</a></li>
                        <li><a href="<?php echo SITE_URL; ?>contact.php">Contact</a></li>
                    </ul>
                    <ul>
                        <li><a href="#">Careers</a></li>
                        <li><a href="<?php echo SITE_URL; ?>ppolicy.php">Privacy Policy</a></li>
                        <li><a href="<?php echo SITE_URL; ?>terms.php">Terms and Conditions</a></li>
                        <li><a href="<?php echo SITE_URL; ?>contact.php">Support</a></li>
                    </ul>
                </div>
            </div>

            <div class="sub-one-left col-lg-4 col-md-6 mt-lg-0 mt-md-5">
                <h6>Subscribe to our Newsletter</h6>
                <form action="<?php echo SITE_URL; ?>includes/newsletter.php" method="post" class="footer-newsletter">
                    <div class="">
                        <input type="email" name="email" class="form-input" placeholder="Enter your email.." required />
                    </div>
                    <button type="submit" class="btn">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</div>  

<div class="below-section">
    <div class="container">
        <div class="copyright-footer row">
            <div class="columns col-lg-6">
                <ul class="social footer">
                    <li><a href="<?php echo getSetting('facebook_url', FACEBOOK_URL); ?>" target="_blank"><span class="fa fa-facebook"></span></a></li>
                    <li><a href="<?php echo getSetting('linkedin_url', LINKEDIN_URL); ?>" target="_blank"><span class="fa fa-linkedin"></span></a></li>
                    <li><a href="<?php echo getSetting('pinterest_url', PINTEREST_URL); ?>" target="_blank"><span class="fa fa-pinterest"></span></a></li>
                </ul>
            </div>
            <div class="columns col-lg-6 text-lg-right">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name', SITE_NAME); ?>. All rights reserved | Design by <a href="<?php echo SITE_URL; ?>" target="_blank"><?php echo getSetting('site_name', SITE_NAME); ?></a></p>
            </div>
        </div>
    </div>
</div>

<!-- Google Analytics and other tracking scripts -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-159320253-1"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-159320253-1');
</script>

<!-- jQuery and Bootstrap JS -->
<script src="<?php echo SITE_URL; ?>assets/js/jquery-3.3.1.min.js"></script>
<script src="<?php echo SITE_URL; ?>assets/js/bootstrap.min.js"></script>
<script src="<?php echo SITE_URL; ?>assets/js/script.js"></script>

<?php if (isset($additional_scripts)): ?>
    <?php echo $additional_scripts; ?>
<?php endif; ?>

</body>
</html>
