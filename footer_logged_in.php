<?php
if (!isset($settings)) {
    require_once __DIR__ . '/db_connect.php';
    require_once __DIR__ . '/settings.php';
    $settings = new SiteSettings();
}
$footer_site_name = $settings->get('site_name', 'LensCraft');
$footer_contact_email = $settings->get('contact_email', '');
$footer_contact_phone = $settings->get('contact_phone', '');
?>
<!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <h3 class="logo" style="color: white; margin-bottom: 20px;"><?php echo htmlspecialchars($footer_site_name); ?></h3>
                <p>Connecting photographers worldwide through the art of visual storytelling.</p>
                <div class="footer-contact" style="margin-top: 15px; font-size: 14px;">
                    <?php if ($footer_contact_email): ?>
                        <p><i class="fas fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($footer_contact_email); ?>" style="color: #999;"><?php echo htmlspecialchars($footer_contact_email); ?></a></p>
                    <?php endif; ?>
                    <?php if ($footer_contact_phone): ?>
                        <p><i class="fas fa-phone"></i> <a href="tel:<?php echo htmlspecialchars($footer_contact_phone); ?>" style="color: #999;"><?php echo htmlspecialchars($footer_contact_phone); ?></a></p>
                    <?php endif; ?>
                </div>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($footer_site_name); ?> Photography Community. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>
</body>
</html>