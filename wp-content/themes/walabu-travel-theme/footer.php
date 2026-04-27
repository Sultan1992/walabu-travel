<?php
if (!defined('ABSPATH')) {
    exit;
}

$footer_columns = array(
    array(
        'title' => __('Company', 'walabu-travel'),
        'items' => array(
            __('Travel Guides', 'walabu-travel'),
            __('Hotels', 'walabu-travel'),
            __('Insurance', 'walabu-travel'),
            __('My Trips', 'walabu-travel'),
            __('Support', 'walabu-travel'),
            __('About Us', 'walabu-travel'),
            __('Careers', 'walabu-travel'),
        ),
    ),
    array(
        'title' => __('Airlines & Deals', 'walabu-travel'),
        'items' => array(
            __('Flight Deals', 'walabu-travel'),
            __('Hotel Deals', 'walabu-travel'),
            __('Vacation Bundles', 'walabu-travel'),
            __('Business Class', 'walabu-travel'),
            __('Family Travel', 'walabu-travel'),
            __('Flexible Tickets', 'walabu-travel'),
        ),
    ),
    array(
        'title' => __('Popular Regions', 'walabu-travel'),
        'items' => array(
            __('Africa', 'walabu-travel'),
            __('Asia', 'walabu-travel'),
            __('Europe', 'walabu-travel'),
            __('Middle East', 'walabu-travel'),
            __('Caribbean', 'walabu-travel'),
            __('North America', 'walabu-travel'),
        ),
    ),
    array(
        'title' => __('Top Destinations', 'walabu-travel'),
        'items' => array(
            __('Ethiopia', 'walabu-travel'),
            __('Kenya', 'walabu-travel'),
            __('Dubai', 'walabu-travel'),
            __('Saudi Arabia', 'walabu-travel'),
        ),
    ),
    array(
        'title' => __('More Destinations', 'walabu-travel'),
        'items' => array(
            __('Spain', 'walabu-travel'),
            __('Lebanon', 'walabu-travel'),
            __('Canada', 'walabu-travel'),
            __('United States', 'walabu-travel'),
            __('Australia', 'walabu-travel'),
            __('Brazil', 'walabu-travel'),
        ),
    ),
    array(
        'title' => __('Traveler Favorites', 'walabu-travel'),
        'items' => array(
            __('Ethiopia', 'walabu-travel'),
            __('Kenya', 'walabu-travel'),
            __('Egypt', 'walabu-travel'),
            __('Tanzania', 'walabu-travel'),
            __('Uganda', 'walabu-travel'),
            __('Rwanda', 'walabu-travel'),
            __('South Africa', 'walabu-travel'),
            __('Ghana', 'walabu-travel'),
        ),
    ),
);
?>
    <footer class="site-footer">
        <section class="site-footer__signup">
            <div class="walabu-container site-footer__signup-inner">
                <div class="site-footer__signup-copy">
                    <div class="site-footer__signup-icon" aria-hidden="true">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <rect x="4.5" y="8.5" width="39" height="29" rx="6.5" stroke="currentColor" stroke-width="3"/>
                            <path d="M8 14l14.1 10.3a3.3 3.3 0 0 0 3.9 0L40 14" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17 7.5h14" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M24 5v7" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="site-footer__signup-title"><?php esc_html_e('Subscribe to our travel newsletter', 'walabu-travel'); ?></h2>
                        <p class="site-footer__signup-text"><?php esc_html_e('Fresh deals and route updates in your inbox.', 'walabu-travel'); ?></p>
                    </div>
                </div>

                <form class="site-footer__signup-form" action="#" method="post">
                    <div class="site-footer__signup-row">
                        <label class="screen-reader-text" for="footer-newsletter-email"><?php esc_html_e('Email address', 'walabu-travel'); ?></label>
                        <input id="footer-newsletter-email" type="email" name="newsletter_email" placeholder="<?php esc_attr_e('Enter your email address', 'walabu-travel'); ?>">
                        <button type="submit"><?php esc_html_e('Sign up', 'walabu-travel'); ?></button>
                    </div>
                    <div class="site-footer__signup-links">
                        <a href="<?php echo esc_url(walabu_travel_get_privacy_url()); ?>"><?php esc_html_e('Privacy Policy', 'walabu-travel'); ?></a>
                        <a href="<?php echo esc_url(walabu_travel_get_terms_url()); ?>"><?php esc_html_e('Terms & Conditions', 'walabu-travel'); ?></a>
                    </div>
                </form>
            </div>
        </section>

        <section class="site-footer__main">
            <div class="walabu-container">
                <div class="site-footer__top">
                    <div class="site-footer__logo-block">
                        <div class="site-footer__logo"><?php echo walabu_travel_get_brand_logo_markup('footer'); ?></div>
                    </div>
                    <div class="site-footer__social" aria-label="<?php esc_attr_e('Social links', 'walabu-travel'); ?>">
                        <a href="#" aria-label="<?php esc_attr_e('Facebook', 'walabu-travel'); ?>">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5H17V4.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.4V11H7.7v3h2.7v8h3.1Z"/></svg>
                        </a>
                        <a href="#" aria-label="<?php esc_attr_e('Instagram', 'walabu-travel'); ?>">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>
                        </a>
                        <a href="#" aria-label="<?php esc_attr_e('YouTube', 'walabu-travel'); ?>">
                            <svg width="24" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.5 12 4.5 12 4.5s-7 0-8.9.6A3 3 0 0 0 1 7.2 31.5 31.5 0 0 0 .5 12c0 1.6.2 3.2.5 4.8A3 3 0 0 0 3.1 19c1.9.5 8.9.5 8.9.5s7 0 8.9-.6a3 3 0 0 0 2.1-2.1c.3-1.6.5-3.2.5-4.8s-.2-3.2-.5-4.8ZM9.5 15.5v-7l6 3.5-6 3.5Z"/></svg>
                        </a>
                        <a href="#" aria-label="<?php esc_attr_e('LinkedIn', 'walabu-travel'); ?>">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5A2.48 2.48 0 1 0 5 8.46 2.48 2.48 0 0 0 4.98 3.5ZM3 9h4v12H3V9Zm7 0h3.8v1.7h.1c.5-.9 1.8-2.1 3.8-2.1 4 0 4.8 2.6 4.8 6V21h-4v-5.6c0-1.3 0-3.1-1.9-3.1s-2.2 1.5-2.2 3V21h-4V9Z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="site-footer__divider"></div>

                <div class="site-footer__grid">
                    <?php foreach ($footer_columns as $column) : ?>
                        <div class="site-footer__column">
                            <h3 class="site-footer__column-title"><?php echo esc_html($column['title']); ?></h3>
                            <ul class="site-footer__links">
                                <?php foreach ($column['items'] as $item) : ?>
                                    <li><a href="#"><?php echo esc_html($item); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="site-footer__divider"></div>

                <div class="site-footer__bottom">
                    <p class="site-footer__copyright"><?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'walabu-travel'); ?></p>
                    <div class="site-footer__legal">
                        <a href="<?php echo esc_url(walabu_travel_get_privacy_url()); ?>"><?php esc_html_e('Privacy Policy', 'walabu-travel'); ?></a>
                        <a href="#"><?php esc_html_e('Cookie Preferences', 'walabu-travel'); ?></a>
                        <a href="<?php echo esc_url(walabu_travel_get_terms_url()); ?>"><?php esc_html_e('Terms & Conditions', 'walabu-travel'); ?></a>
                    </div>
                </div>
            </div>
        </section>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
