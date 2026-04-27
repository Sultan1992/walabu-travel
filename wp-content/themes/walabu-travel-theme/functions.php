<?php
if (!defined('ABSPATH')) {
    exit;
}

function walabu_travel_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 48,
        'width'       => 220,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'walabu-travel'),
    ));
}
add_action('after_setup_theme', 'walabu_travel_setup');

function walabu_travel_assets() {
    $theme = wp_get_theme();
    $version = $theme->get('Version') ?: '1.0.0';

    wp_enqueue_style(
        'walabu-travel-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'walabu-travel-style',
        get_stylesheet_uri(),
        array('walabu-travel-fonts'),
        $version
    );

    wp_enqueue_script(
        'walabu-travel-script',
        get_template_directory_uri() . '/assets/theme.js',
        array(),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'walabu_travel_assets');

function walabu_travel_get_logo_asset_path() {
    return get_template_directory() . '/assets/walabu-logo.svg';
}

function walabu_travel_get_logo_asset_uri() {
    return get_template_directory_uri() . '/assets/walabu-logo.svg';
}

function walabu_travel_has_theme_logo_asset() {
    return file_exists(walabu_travel_get_logo_asset_path());
}

function walabu_travel_get_brand_logo_markup($context = 'header') {
    if (has_custom_logo()) {
        return get_custom_logo();
    }

    if (walabu_travel_has_theme_logo_asset()) {
        return sprintf(
            '<a class="site-brand__asset site-brand__asset--%1$s" href="%2$s" rel="home" aria-label="%3$s"><img src="%4$s" alt="%3$s"></a>',
            esc_attr($context),
            esc_url(home_url('/')),
            esc_attr(get_bloginfo('name')),
            esc_url(walabu_travel_get_logo_asset_uri())
        );
    }

    return sprintf(
        '<a class="site-brand" href="%1$s"><span class="site-brand__mark">W</span><span class="site-brand__text">%2$s</span></a>',
        esc_url(home_url('/')),
        esc_html(get_bloginfo('name'))
    );
}

function walabu_travel_fallback_menu() {
    $items = array(
        array(
            'href'  => walabu_travel_get_support_url(),
            'label' => __('Support', 'walabu-travel'),
        ),
        array(
            'href'        => walabu_travel_get_my_trips_url(),
            'label'       => __('My Trips', 'walabu-travel'),
            'auth_gated'  => true,
        ),
        array(
            'href'  => home_url('/#deals'),
            'label' => __('Deals', 'walabu-travel'),
        ),
    );

    foreach ($items as $item) {
        $attributes = '';

        if (!empty($item['auth_gated'])) {
            $attributes .= ' data-auth-gated-link="my-trips"';
            $attributes .= ' data-auth-redirect="' . esc_attr($item['href']) . '"';
        }

        printf(
            '<a href="%1$s"%3$s>%2$s</a>',
            esc_url($item['href']),
            esc_html($item['label']),
            $attributes
        );
    }
}

function walabu_travel_get_terms_url() {
    return home_url('/terms-and-conditions/');
}

function walabu_travel_get_privacy_url() {
    return home_url('/privacy-policy/');
}

function walabu_travel_get_support_url() {
    return home_url('/support/');
}

function walabu_travel_get_my_trips_url() {
    return home_url('/my-trips/');
}

function walabu_travel_render_flight_search($variant = 'default') {
    if (!shortcode_exists('walabu_flight_search')) {
        return '<div class="booking-widget__notice" role="status"><span class="booking-widget__notice-icon">&#8855;</span><span>' . esc_html__('Activate the Walabu Duffel Booking plugin to display the flight search form here.', 'walabu-travel') . '</span></div>';
    }

    if (function_exists('walabu_duffel_booking_force_enqueue_assets')) {
        walabu_duffel_booking_force_enqueue_assets();
    }

    return do_shortcode('[walabu_flight_search variant="' . esc_attr($variant) . '"]');
}

function walabu_travel_is_terms_page() {
    return get_query_var('walabu_legal_page') === 'terms-and-conditions';
}

function walabu_travel_is_privacy_page() {
    return get_query_var('walabu_legal_page') === 'privacy-policy';
}

function walabu_travel_is_support_page() {
    return get_query_var('walabu_site_page') === 'support';
}

function walabu_travel_is_my_trips_page() {
    return get_query_var('walabu_site_page') === 'my-trips';
}

function walabu_travel_is_legal_page() {
    return walabu_travel_is_terms_page() || walabu_travel_is_privacy_page();
}

function walabu_travel_query_vars($vars) {
    $vars[] = 'walabu_legal_page';
    $vars[] = 'walabu_site_page';

    return $vars;
}
add_filter('query_vars', 'walabu_travel_query_vars');

function walabu_travel_add_rewrite_rules() {
    add_rewrite_rule(
        '^terms-and-conditions/?$',
        'index.php?walabu_legal_page=terms-and-conditions',
        'top'
    );

    add_rewrite_rule(
        '^privacy-policy/?$',
        'index.php?walabu_legal_page=privacy-policy',
        'top'
    );

    add_rewrite_rule(
        '^support/?$',
        'index.php?walabu_site_page=support',
        'top'
    );

    add_rewrite_rule(
        '^my-trips/?$',
        'index.php?walabu_site_page=my-trips',
        'top'
    );
}
add_action('init', 'walabu_travel_add_rewrite_rules');

function walabu_travel_maybe_flush_rewrite_rules() {
    $rewrite_version = '4';

    if (get_option('walabu_travel_rewrite_version') === $rewrite_version) {
        return;
    }

    walabu_travel_add_rewrite_rules();
    flush_rewrite_rules(false);
    update_option('walabu_travel_rewrite_version', $rewrite_version);
}
add_action('after_switch_theme', 'walabu_travel_maybe_flush_rewrite_rules');
add_action('init', 'walabu_travel_maybe_flush_rewrite_rules', 20);

function walabu_travel_template_include($template) {
    if (walabu_travel_is_terms_page()) {
        $terms_template = get_template_directory() . '/template-terms-and-conditions.php';

        if (file_exists($terms_template)) {
            return $terms_template;
        }
    }

    if (walabu_travel_is_privacy_page()) {
        $privacy_template = get_template_directory() . '/template-privacy-policy.php';

        if (file_exists($privacy_template)) {
            return $privacy_template;
        }
    }

    if (walabu_travel_is_support_page()) {
        $support_template = get_template_directory() . '/template-support.php';

        if (file_exists($support_template)) {
            return $support_template;
        }
    }

    if (walabu_travel_is_my_trips_page()) {
        $my_trips_template = get_template_directory() . '/template-my-trips.php';

        if (file_exists($my_trips_template)) {
            return $my_trips_template;
        }
    }

    return $template;
}
add_filter('template_include', 'walabu_travel_template_include');

function walabu_travel_terms_document_title($title) {
    if (walabu_travel_is_terms_page()) {
        return sprintf(
            '%s - %s',
            __('Terms & Conditions', 'walabu-travel'),
            get_bloginfo('name')
        );
    }

    if (walabu_travel_is_privacy_page()) {
        return sprintf(
            '%s - %s',
            __('Privacy Policy', 'walabu-travel'),
            get_bloginfo('name')
        );
    }

    if (walabu_travel_is_support_page()) {
        return sprintf(
            '%s - %s',
            __('Support', 'walabu-travel'),
            get_bloginfo('name')
        );
    }

    if (walabu_travel_is_my_trips_page()) {
        return sprintf(
            '%s - %s',
            __('My Trips', 'walabu-travel'),
            get_bloginfo('name')
        );
    }

    return $title;
}
add_filter('pre_get_document_title', 'walabu_travel_terms_document_title');

function walabu_travel_nav_menu_link_attributes($atts) {
    if (empty($atts['href'])) {
        return $atts;
    }

    if (untrailingslashit($atts['href']) !== untrailingslashit(walabu_travel_get_my_trips_url())) {
        return $atts;
    }

    $atts['data-auth-gated-link'] = 'my-trips';
    $atts['data-auth-redirect']   = walabu_travel_get_my_trips_url();

    return $atts;
}
add_filter('nav_menu_link_attributes', 'walabu_travel_nav_menu_link_attributes');

function walabu_travel_body_classes($classes) {
    if (walabu_travel_is_legal_page()) {
        $classes[] = 'walabu-legal-page';
    }

    if (walabu_travel_is_support_page()) {
        $classes[] = 'walabu-support-page';
    }

    if (walabu_travel_is_my_trips_page()) {
        $classes[] = 'walabu-my-trips-page';
    }

    return $classes;
}
add_filter('body_class', 'walabu_travel_body_classes');
