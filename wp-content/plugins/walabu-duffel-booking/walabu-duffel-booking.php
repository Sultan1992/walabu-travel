<?php
/**
 * Plugin Name: Walabu Duffel Booking
 * Description: Simple flight search form for Walabu Travel using the Duffel API.
 * Version: 2.0.0
 * Author: Walabu Travel
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WALABU_DUFFEL_BOOKING_API_VERSION')) {
    define('WALABU_DUFFEL_BOOKING_API_VERSION', 'v2');
}

if (!defined('WALABU_DUFFEL_BOOKING_OPTION_KEY')) {
    define('WALABU_DUFFEL_BOOKING_OPTION_KEY', 'walabu_duffel_booking_access_token');
}

if (!defined('WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY')) {
    define('WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY', 'walabu_duffel_booking_private_fares_json');
}

if (!defined('WALABU_DUFFEL_BOOKING_PAGE_SLUG')) {
    define('WALABU_DUFFEL_BOOKING_PAGE_SLUG', 'flight-booking');
}

if (!defined('WALABU_DUFFEL_BOOKING_RESULTS_LIMIT')) {
    define('WALABU_DUFFEL_BOOKING_RESULTS_LIMIT', 10);
}

if (!defined('WALABU_DUFFEL_BOOKING_MAX_CONNECTIONS')) {
    define('WALABU_DUFFEL_BOOKING_MAX_CONNECTIONS', 2);
}

if (!defined('WALABU_DUFFEL_BOOKING_SUPPLIER_TIMEOUT')) {
    define('WALABU_DUFFEL_BOOKING_SUPPLIER_TIMEOUT', 60000);
}

function walabu_duffel_booking_allowed_cabin_classes() {
    return array(
        'economy'          => __('Economy', 'walabu-travel'),
        'premium_economy'  => __('Premium Economy', 'walabu-travel'),
        'business'         => __('Business', 'walabu-travel'),
        'first'            => __('First', 'walabu-travel'),
    );
}

function walabu_duffel_booking_allowed_max_connections() {
    return array(
        '' => __('Any number of stops', 'walabu-travel'),
        0 => __('Nonstop only', 'walabu-travel'),
        1 => __('Up to 1 stop', 'walabu-travel'),
        2 => __('Up to 2 stops', 'walabu-travel'),
    );
}

function walabu_duffel_booking_get_access_token() {
    if (defined('WALABU_DUFFEL_BOOKING_ACCESS_TOKEN') && '' !== trim((string) WALABU_DUFFEL_BOOKING_ACCESS_TOKEN)) {
        return trim((string) WALABU_DUFFEL_BOOKING_ACCESS_TOKEN);
    }

    return trim((string) get_option(WALABU_DUFFEL_BOOKING_OPTION_KEY, ''));
}

function walabu_duffel_booking_get_private_fares_config() {
    $raw_value = defined('WALABU_DUFFEL_BOOKING_PRIVATE_FARES')
        ? WALABU_DUFFEL_BOOKING_PRIVATE_FARES
        : get_option(WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY, '');

    if (is_array($raw_value)) {
        $decoded = $raw_value;
    } else {
        $raw_value = trim((string) $raw_value);

        if ('' === $raw_value) {
            return array();
        }

        $decoded = json_decode($raw_value, true);
    }

    if (!is_array($decoded)) {
        return array();
    }

    $sanitized = array();

    foreach ($decoded as $airline_code => $entries) {
        $airline_code = strtoupper(substr(preg_replace('/[^A-Z]/', '', (string) $airline_code), 0, 2));

        if ('' === $airline_code || !is_array($entries)) {
            continue;
        }

        $sanitized_entries = array();

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $clean_entry = array();

            foreach (array('corporate_code', 'tour_code', 'tracking_reference') as $key) {
                if (!empty($entry[$key]) && is_string($entry[$key])) {
                    $clean_entry[$key] = sanitize_text_field($entry[$key]);
                }
            }

            if (!empty($clean_entry)) {
                $sanitized_entries[] = $clean_entry;
            }
        }

        if (!empty($sanitized_entries)) {
            $sanitized[$airline_code] = $sanitized_entries;
        }
    }

    return $sanitized;
}

function walabu_duffel_booking_sanitize_private_fares_json($value) {
    $value = trim((string) $value);

    if ('' === $value) {
        return '';
    }

    $decoded = json_decode($value, true);

    if (!is_array($decoded)) {
        add_settings_error(
            'walabu_duffel_booking_settings',
            'walabu_duffel_booking_private_fares_json',
            __('Private fares JSON is invalid. Save a valid JSON object keyed by airline IATA code.', 'walabu-travel'),
            'error'
        );

        return get_option(WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY, '');
    }

    return wp_json_encode(walabu_duffel_booking_get_private_fares_config_from_array($decoded), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function walabu_duffel_booking_get_private_fares_config_from_array($decoded) {
    $temporary = $decoded;
    return is_array($temporary) ? walabu_duffel_booking_get_private_fares_config_from_raw($temporary) : array();
}

function walabu_duffel_booking_get_private_fares_config_from_raw($raw_value) {
    if (!is_array($raw_value)) {
        return array();
    }

    $sanitized = array();

    foreach ($raw_value as $airline_code => $entries) {
        $airline_code = strtoupper(substr(preg_replace('/[^A-Z]/', '', (string) $airline_code), 0, 2));

        if ('' === $airline_code || !is_array($entries)) {
            continue;
        }

        $sanitized_entries = array();

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $clean_entry = array();

            foreach (array('corporate_code', 'tour_code', 'tracking_reference') as $key) {
                if (!empty($entry[$key]) && is_string($entry[$key])) {
                    $clean_entry[$key] = sanitize_text_field($entry[$key]);
                }
            }

            if (!empty($clean_entry)) {
                $sanitized_entries[] = $clean_entry;
            }
        }

        if (!empty($sanitized_entries)) {
            $sanitized[$airline_code] = $sanitized_entries;
        }
    }

    return $sanitized;
}

function walabu_duffel_booking_mask_token($token) {
    $token = (string) $token;
    $length = strlen($token);

    if ($length <= 8) {
        return str_repeat('*', $length);
    }

    return substr($token, 0, 6) . str_repeat('*', max(4, $length - 10)) . substr($token, -4);
}

function walabu_duffel_booking_get_api_headers($access_token) {
    return array(
        'Authorization'  => 'Bearer ' . $access_token,
        'Duffel-Version' => WALABU_DUFFEL_BOOKING_API_VERSION,
        'Accept'         => 'application/json',
        'Content-Type'   => 'application/json',
    );
}

function walabu_duffel_booking_get_asset_version($relative_path) {
    $file_path = plugin_dir_path(__FILE__) . ltrim((string) $relative_path, '/');

    if (file_exists($file_path)) {
        return (string) filemtime($file_path);
    }

    return '2.0.0';
}

function walabu_duffel_booking_extract_error_message($body) {
    if (!is_array($body) || empty($body['errors']) || !is_array($body['errors'])) {
        return '';
    }

    foreach ($body['errors'] as $error) {
        if (!is_array($error)) {
            continue;
        }

        foreach (array('detail', 'message', 'title') as $key) {
            if (!empty($error[$key]) && is_string($error[$key])) {
                return $error[$key];
            }
        }
    }

    return '';
}

function walabu_duffel_booking_get_admin_notice_transient_key() {
    return 'walabu_duffel_booking_admin_notice_' . get_current_user_id();
}

function walabu_duffel_booking_create_booking_page() {
    $existing_page = get_page_by_path(WALABU_DUFFEL_BOOKING_PAGE_SLUG, OBJECT, 'page');

    if ($existing_page instanceof WP_Post) {
        update_option('walabu_duffel_booking_page_id', (int) $existing_page->ID);
        return;
    }

    $page_id = wp_insert_post(
        array(
            'post_title'   => 'Flight Booking',
            'post_name'    => WALABU_DUFFEL_BOOKING_PAGE_SLUG,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_content' => '[walabu_flight_search]',
        ),
        true
    );

    if (!is_wp_error($page_id)) {
        update_option('walabu_duffel_booking_page_id', (int) $page_id);
    }
}

register_activation_hook(__FILE__, 'walabu_duffel_booking_create_booking_page');

function walabu_duffel_booking_ensure_booking_page() {
    $page_id = absint(get_option('walabu_duffel_booking_page_id', 0));

    if ($page_id > 0 && 'page' === get_post_type($page_id) && 'trash' !== get_post_status($page_id)) {
        return;
    }

    walabu_duffel_booking_create_booking_page();
}

add_action('init', 'walabu_duffel_booking_ensure_booking_page');

function walabu_duffel_booking_register_settings() {
    register_setting(
        'walabu_duffel_booking_settings',
        WALABU_DUFFEL_BOOKING_OPTION_KEY,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );

    register_setting(
        'walabu_duffel_booking_settings',
        WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'walabu_duffel_booking_sanitize_private_fares_json',
            'default'           => '',
        )
    );
}

add_action('admin_init', 'walabu_duffel_booking_register_settings');

function walabu_duffel_booking_add_settings_page() {
    add_options_page(
        __('Walabu Duffel Booking', 'walabu-travel'),
        __('Walabu Duffel Booking', 'walabu-travel'),
        'manage_options',
        'walabu-duffel-booking',
        'walabu_duffel_booking_render_settings_page'
    );
}

add_action('admin_menu', 'walabu_duffel_booking_add_settings_page');

function walabu_duffel_booking_test_connection() {
    $access_token = walabu_duffel_booking_get_access_token();

    if ('' === $access_token) {
        return array(
            'type'    => 'error',
            'message' => __('Missing access token. Add your Duffel test API key in the plugin settings or define WALABU_DUFFEL_BOOKING_ACCESS_TOKEN in wp-config.php.', 'walabu-travel'),
        );
    }

    $response = wp_remote_get(
        'https://api.duffel.com/air/airlines?limit=1',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization'  => 'Bearer ' . $access_token,
                'Duffel-Version' => WALABU_DUFFEL_BOOKING_API_VERSION,
                'Accept'         => 'application/json',
            ),
        )
    );

    if (is_wp_error($response)) {
        return array(
            'type'    => 'error',
            'message' => sprintf(
                __('Duffel connection failed: %s', 'walabu-travel'),
                $response->get_error_message()
            ),
        );
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $request_id = wp_remote_retrieve_header($response, 'x-request-id');

    if ($status_code >= 200 && $status_code < 300) {
        $message = sprintf(
            __('Duffel connection succeeded with HTTP %d.', 'walabu-travel'),
            $status_code
        );

        if (!empty($body['data'][0]['name']) && is_string($body['data'][0]['name'])) {
            $message .= ' ' . sprintf(
                __('Sample airline: %s.', 'walabu-travel'),
                $body['data'][0]['name']
            );
        }

        if (is_string($request_id) && '' !== $request_id) {
            $message .= ' ' . sprintf(
                __('Request ID: %s', 'walabu-travel'),
                $request_id
            );
        }

        return array(
            'type'    => 'success',
            'message' => $message,
        );
    }

    $error_message = walabu_duffel_booking_extract_error_message($body);

    if ('' === $error_message) {
        $error_message = __('Duffel returned an unexpected error response.', 'walabu-travel');
    }

    $message = sprintf(
        __('Duffel connection failed with HTTP %d: %s', 'walabu-travel'),
        $status_code,
        $error_message
    );

    if (is_string($request_id) && '' !== $request_id) {
        $message .= ' ' . sprintf(
            __('Request ID: %s', 'walabu-travel'),
            $request_id
        );
    }

    return array(
        'type'    => 'error',
        'message' => $message,
    );
}

function walabu_duffel_booking_handle_test_connection() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'walabu-travel'));
    }

    check_admin_referer('walabu_duffel_booking_test_connection');

    set_transient(
        walabu_duffel_booking_get_admin_notice_transient_key(),
        walabu_duffel_booking_test_connection(),
        MINUTE_IN_SECONDS
    );

    wp_safe_redirect(admin_url('options-general.php?page=walabu-duffel-booking'));
    exit;
}

add_action('admin_post_walabu_duffel_booking_test_connection', 'walabu_duffel_booking_handle_test_connection');

function walabu_duffel_booking_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $access_token = walabu_duffel_booking_get_access_token();
    $notice = get_transient(walabu_duffel_booking_get_admin_notice_transient_key());

    if (false !== $notice) {
        delete_transient(walabu_duffel_booking_get_admin_notice_transient_key());
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Walabu Duffel Booking', 'walabu-travel'); ?></h1>
        <p><?php esc_html_e('Store your Duffel test API key here or override it with WALABU_DUFFEL_BOOKING_ACCESS_TOKEN in wp-config.php.', 'walabu-travel'); ?></p>

        <?php if (!empty($notice['message'])) : ?>
            <div class="notice notice-<?php echo 'success' === ($notice['type'] ?? '') ? 'success' : 'error'; ?> is-dismissible">
                <p><?php echo esc_html($notice['message']); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
            <?php settings_fields('walabu_duffel_booking_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="walabu-duffel-booking-access-token"><?php esc_html_e('Duffel test API key', 'walabu-travel'); ?></label>
                        </th>
                        <td>
                            <input
                                id="walabu-duffel-booking-access-token"
                                name="<?php echo esc_attr(WALABU_DUFFEL_BOOKING_OPTION_KEY); ?>"
                                type="password"
                                class="regular-text"
                                value="<?php echo esc_attr(get_option(WALABU_DUFFEL_BOOKING_OPTION_KEY, '')); ?>"
                                autocomplete="off"
                            >
                            <p class="description">
                                <?php esc_html_e('Use the test-mode key from your Duffel account.', 'walabu-travel'); ?>
                            </p>
                            <?php if ('' !== $access_token) : ?>
                                <p>
                                    <strong><?php esc_html_e('Loaded token:', 'walabu-travel'); ?></strong>
                                    <code><?php echo esc_html(walabu_duffel_booking_mask_token($access_token)); ?></code>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="walabu-duffel-booking-private-fares"><?php esc_html_e('Corporate private fares JSON', 'walabu-travel'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="walabu-duffel-booking-private-fares"
                                name="<?php echo esc_attr(WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY); ?>"
                                rows="8"
                                class="large-text code"
                            ><?php echo esc_textarea((string) get_option(WALABU_DUFFEL_BOOKING_PRIVATE_FARES_OPTION_KEY, '')); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Optional. Use airline IATA codes as keys. Example: {"BA":[{"corporate_code":"5623"}],"UA":[{"corporate_code":"1234","tour_code":"578DFL"}]}', 'walabu-travel'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Shortcode', 'walabu-travel'); ?></th>
                        <td><code>[walabu_flight_search]</code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto-created page', 'walabu-travel'); ?></th>
                        <td>
                            <a href="<?php echo esc_url(home_url('/' . WALABU_DUFFEL_BOOKING_PAGE_SLUG . '/')); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(home_url('/' . WALABU_DUFFEL_BOOKING_PAGE_SLUG . '/')); ?>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Save API Key', 'walabu-travel')); ?>
        </form>

        <hr>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="walabu_duffel_booking_test_connection">
            <?php wp_nonce_field('walabu_duffel_booking_test_connection'); ?>
            <?php submit_button(__('Test Duffel Connection', 'walabu-travel'), 'secondary'); ?>
        </form>
    </div>
    <?php
}

function walabu_duffel_booking_should_enqueue_assets() {
    if (is_front_page()) {
        return true;
    }

    if (!is_singular()) {
        return false;
    }

    $page_id = absint(get_option('walabu_duffel_booking_page_id', 0));

    if ($page_id > 0 && is_page($page_id)) {
        return true;
    }

    global $post;

    return $post instanceof WP_Post && has_shortcode($post->post_content, 'walabu_flight_search');
}

function walabu_duffel_booking_do_enqueue_assets() {
    $style_dependencies = array();

    if (wp_style_is('walabu-travel-style', 'registered') || wp_style_is('walabu-travel-style', 'enqueued')) {
        $style_dependencies[] = 'walabu-travel-style';
    }

    wp_enqueue_style(
        'walabu-duffel-booking',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        $style_dependencies,
        walabu_duffel_booking_get_asset_version('assets/css/style.css')
    );

    wp_enqueue_script(
        'walabu-duffel-booking',
        plugin_dir_url(__FILE__) . 'assets/js/app.js',
        array(),
        walabu_duffel_booking_get_asset_version('assets/js/app.js'),
        true
    );

    wp_localize_script(
        'walabu-duffel-booking',
        'walabuDuffelBooking',
        array(
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'suggestionsNonce'  => wp_create_nonce('walabu_duffel_place_suggestions'),
            'suggestionsAction' => 'walabu_duffel_place_suggestions',
            'nearbyNonce'       => wp_create_nonce('walabu_duffel_nearby_airports'),
            'nearbyAction'      => 'walabu_duffel_nearby_airports',
            'minChars'          => 2,
            'nearbyRadius'      => 100000,
            'strings'           => array(
                'noResults' => __('No matching airports or cities found.', 'walabu-travel'),
                'searching' => __('Searching places...', 'walabu-travel'),
                'searchHint' => __('Type at least 2 letters to search.', 'walabu-travel'),
                'nearbyHint' => __('Select a place first.', 'walabu-travel'),
                'searchingNearby' => __('Searching nearby airports...', 'walabu-travel'),
            ),
        )
    );
}

function walabu_duffel_booking_force_enqueue_assets() {
    walabu_duffel_booking_do_enqueue_assets();
}

function walabu_duffel_booking_enqueue_assets() {
    if (!walabu_duffel_booking_should_enqueue_assets()) {
        return;
    }

    walabu_duffel_booking_do_enqueue_assets();
}

add_action('wp_enqueue_scripts', 'walabu_duffel_booking_enqueue_assets', 20);

function walabu_duffel_booking_default_form_values() {
    return array(
        'origin'         => '',
        'origin_label'   => '',
        'destination'    => '',
        'destination_label' => '',
        'trip_type'      => 'one_way',
        'departure_date' => wp_date('Y-m-d', strtotime('+14 days')),
        'return_date'    => wp_date('Y-m-d', strtotime('+30 days')),
        'passengers'     => 1,
        'cabin_class'    => 'economy',
        'max_connections' => '',
        'fare_type'      => '',
    );
}

function walabu_duffel_booking_sanitize_iata_code($value) {
    $value = strtoupper(trim((string) $value));
    $value = preg_replace('/[^A-Z]/', '', $value);

    return substr($value, 0, 3);
}

function walabu_duffel_booking_sanitize_fare_type($value) {
    return strtolower(preg_replace('/[^a-z0-9_]/', '', trim((string) $value)));
}

function walabu_duffel_booking_get_form_state() {
    $defaults = walabu_duffel_booking_default_form_values();
    $values = $defaults;
    $errors = array();
    $request_data = array();

    if (!empty($_GET['walabu_flight_search_submit'])) {
        $request_data = $_GET;
    } elseif ('POST' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '') && !empty($_POST['walabu_flight_search_submit'])) {
        $request_data = $_POST;
    }

    if (empty($request_data)) {
        return array(
            'submitted' => false,
            'values'    => $values,
            'errors'    => $errors,
        );
    }

    $origin_raw = wp_unslash($request_data['origin'] ?? '');
    $origin_label_raw = wp_unslash($request_data['origin_label'] ?? '');
    $destination_raw = wp_unslash($request_data['destination'] ?? '');
    $destination_label_raw = wp_unslash($request_data['destination_label'] ?? '');

    $values = array(
        'origin'         => walabu_duffel_booking_sanitize_iata_code($origin_raw),
        'origin_label'   => sanitize_text_field($origin_label_raw),
        'destination'    => walabu_duffel_booking_sanitize_iata_code($destination_raw),
        'destination_label' => sanitize_text_field($destination_label_raw),
        'trip_type'      => sanitize_key(wp_unslash($request_data['trip_type'] ?? $defaults['trip_type'])),
        'departure_date' => sanitize_text_field(wp_unslash($request_data['departure_date'] ?? '')),
        'return_date'    => sanitize_text_field(wp_unslash($request_data['return_date'] ?? '')),
        'passengers'     => max(1, min(9, absint($request_data['passengers'] ?? 1))),
        'cabin_class'    => sanitize_key(wp_unslash($request_data['cabin_class'] ?? $defaults['cabin_class'])),
        'max_connections' => '' === trim((string) ($request_data['max_connections'] ?? ''))
            ? ''
            : absint($request_data['max_connections'] ?? $defaults['max_connections']),
        'fare_type'      => walabu_duffel_booking_sanitize_fare_type(wp_unslash($request_data['fare_type'] ?? '')),
    );

    $today = wp_date('Y-m-d');
    $allowed_cabin_classes = walabu_duffel_booking_allowed_cabin_classes();
    $allowed_trip_types = array('one_way', 'round_trip', 'multi_city');
    $allowed_max_connections = walabu_duffel_booking_allowed_max_connections();

    if (!in_array($values['trip_type'], $allowed_trip_types, true)) {
        $values['trip_type'] = $defaults['trip_type'];
    }

    if (3 !== strlen($values['origin'])) {
        $errors[] = __('Select a valid origin place from the dropdown.', 'walabu-travel');
    }

    if (3 !== strlen($values['destination'])) {
        $errors[] = __('Select a valid destination place from the dropdown.', 'walabu-travel');
    }

    if ($values['origin'] === $values['destination'] && '' !== $values['origin']) {
        $errors[] = __('Origin and destination must be different airports.', 'walabu-travel');
    }

    if (empty($values['departure_date'])) {
        $errors[] = __('Choose a departure date.', 'walabu-travel');
    } elseif ($values['departure_date'] < $today) {
        $errors[] = __('Departure date must be today or later.', 'walabu-travel');
    }

    if ('one_way' !== $values['trip_type']) {
        if (empty($values['return_date'])) {
            $errors[] = __('Choose a return date for round trip or multi-city searches.', 'walabu-travel');
        } elseif ($values['return_date'] < $values['departure_date']) {
            $errors[] = __('Return date must be on or after the departure date.', 'walabu-travel');
        }
    } else {
        $values['return_date'] = '';
    }

    if (!isset($allowed_cabin_classes[$values['cabin_class']])) {
        $values['cabin_class'] = $defaults['cabin_class'];
    }

    if ('' !== $values['max_connections'] && !array_key_exists($values['max_connections'], $allowed_max_connections)) {
        $values['max_connections'] = $defaults['max_connections'];
    }

    return array(
        'submitted' => true,
        'values'    => $values,
        'errors'    => $errors,
    );
}

function walabu_duffel_booking_build_offer_request_payload($values) {
    $passengers = array();
    $private_fares = walabu_duffel_booking_get_private_fares_config();

    for ($i = 0; $i < (int) $values['passengers']; $i++) {
        if (!empty($values['fare_type'])) {
            $passengers[] = array(
                'fare_type' => $values['fare_type'],
            );
        } else {
            $passengers[] = array(
                'type' => 'adult',
            );
        }
    }

    $slices = array(
        array(
            'origin'         => $values['origin'],
            'destination'    => $values['destination'],
            'departure_date' => $values['departure_date'],
        ),
    );

    if ('one_way' !== ($values['trip_type'] ?? 'one_way') && !empty($values['return_date'])) {
        $slices[] = array(
            'origin'         => $values['destination'],
            'destination'    => $values['origin'],
            'departure_date' => $values['return_date'],
        );
    }

    $payload = array(
        'data' => array(
            'slices'          => $slices,
            'passengers'      => $passengers,
            'cabin_class'     => $values['cabin_class'],
        ),
    );

    if ('' !== (string) $values['max_connections']) {
        $payload['data']['max_connections'] = (int) $values['max_connections'];
    }

    if (!empty($private_fares)) {
        $payload['data']['private_fares'] = $private_fares;
    }

    return $payload;
}

function walabu_duffel_booking_parse_datetime($value) {
    $value = (string) $value;

    if ('' === $value) {
        return null;
    }

    $utc_timezone = new DateTimeZone('UTC');
    $formats = array(
        DATE_ATOM,
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:s',
    );

    foreach ($formats as $format) {
        $date = false;

        if (false !== strpos($format, 'P')) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
        } else {
            $date = DateTimeImmutable::createFromFormat($format, $value, $utc_timezone);
        }

        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
    }

    try {
        return new DateTimeImmutable($value, $utc_timezone);
    } catch (Exception $exception) {
        return null;
    }
}

function walabu_duffel_booking_parse_datetime_in_timezone($value, $time_zone = '') {
    $value = (string) $value;
    $time_zone = trim((string) $time_zone);

    if ('' === $value) {
        return null;
    }

    if ('' === $time_zone) {
        return walabu_duffel_booking_parse_datetime($value);
    }

    try {
        $timezone = new DateTimeZone($time_zone);
    } catch (Exception $exception) {
        return walabu_duffel_booking_parse_datetime($value);
    }

    $formats = array(
        DATE_ATOM,
        'Y-m-d\TH:i:s.uP',
        'Y-m-d\TH:i:sP',
    );

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);

        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
    }

    $naive_formats = array(
        'Y-m-d\TH:i:s.u',
        'Y-m-d\TH:i:s',
    );

    foreach ($naive_formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);

        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
    }

    try {
        return new DateTimeImmutable($value, $timezone);
    } catch (Exception $exception) {
        return walabu_duffel_booking_parse_datetime($value);
    }
}

function walabu_duffel_booking_format_datetime($value) {
    $date = walabu_duffel_booking_parse_datetime($value);

    if (!$date instanceof DateTimeImmutable) {
        return __('Unavailable', 'walabu-travel');
    }

    return $date->format('M j, Y g:i A');
}

function walabu_duffel_booking_format_datetime_local($value, $time_zone = '') {
    $date = walabu_duffel_booking_parse_datetime_in_timezone($value, $time_zone);

    if (!$date instanceof DateTimeImmutable) {
        return __('Unavailable', 'walabu-travel');
    }

    $time_zone = trim((string) $time_zone);

    if ('' !== $time_zone) {
        try {
            $date = $date->setTimezone(new DateTimeZone($time_zone));
        } catch (Exception $exception) {
            // Fall back to the parsed timestamp if the timezone is invalid.
        }
    }

    return $date->format('M j, Y g:i A');
}

function walabu_duffel_booking_get_segment_airport_timezone($segment, $endpoint) {
    if (!is_array($segment)) {
        return '';
    }

    $airport = is_array($segment[$endpoint] ?? null) ? $segment[$endpoint] : array();

    if (!empty($airport['time_zone']) && is_string($airport['time_zone'])) {
        return trim($airport['time_zone']);
    }

    return '';
}

function walabu_duffel_booking_format_duration_from_datetimes($start, $end) {
    $start_date = walabu_duffel_booking_parse_datetime($start);
    $end_date = walabu_duffel_booking_parse_datetime($end);

    if (!$start_date instanceof DateTimeImmutable || !$end_date instanceof DateTimeImmutable || $end_date < $start_date) {
        return __('Unavailable', 'walabu-travel');
    }

    $interval = $start_date->diff($end_date);
    $hours = ($interval->days * 24) + $interval->h;
    $minutes = $interval->i;

    return sprintf(
        _x('%1$dh %2$dm', 'flight duration', 'walabu-travel'),
        $hours,
        $minutes
    );
}

function walabu_duffel_booking_format_duration_from_datetimes_local($start, $start_time_zone, $end, $end_time_zone) {
    $start_date = walabu_duffel_booking_parse_datetime_in_timezone($start, $start_time_zone);
    $end_date = walabu_duffel_booking_parse_datetime_in_timezone($end, $end_time_zone);

    if (!$start_date instanceof DateTimeImmutable || !$end_date instanceof DateTimeImmutable || $end_date < $start_date) {
        return __('Unavailable', 'walabu-travel');
    }

    $interval = $start_date->diff($end_date);
    $hours = ($interval->days * 24) + $interval->h;
    $minutes = $interval->i;

    return sprintf(
        _x('%1$dh %2$dm', 'flight duration', 'walabu-travel'),
        $hours,
        $minutes
    );
}

function walabu_duffel_booking_format_day_offset_local($start, $start_time_zone, $end, $end_time_zone) {
    $start_date = walabu_duffel_booking_parse_datetime_in_timezone($start, $start_time_zone);
    $end_date = walabu_duffel_booking_parse_datetime_in_timezone($end, $end_time_zone);

    if (!$start_date instanceof DateTimeImmutable || !$end_date instanceof DateTimeImmutable || $end_date <= $start_date) {
        return '';
    }

    $day_difference = (int) $start_date->setTime(0, 0)->diff($end_date->setTime(0, 0))->format('%a');

    if ($day_difference <= 0) {
        return '';
    }

    return '+' . $day_difference;
}

function walabu_duffel_booking_format_iso_duration($value) {
    $value = (string) $value;

    if (!preg_match('/^P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?$/', $value, $matches)) {
        return '';
    }

    $days = isset($matches[1]) ? (int) $matches[1] : 0;
    $hours = isset($matches[2]) ? (int) $matches[2] : 0;
    $minutes = isset($matches[3]) ? (int) $matches[3] : 0;
    $hours += ($days * 24);

    return sprintf(
        _x('%1$dh %2$dm', 'flight duration', 'walabu-travel'),
        $hours,
        $minutes
    );
}

function walabu_duffel_booking_format_stops($segments) {
    $segments = is_array($segments) ? $segments : array();
    $connections = max(0, count($segments) - 1);
    $touchdown_stops = 0;

    foreach ($segments as $segment) {
        if (!empty($segment['stops']) && is_array($segment['stops'])) {
            $touchdown_stops += count($segment['stops']);
        }
    }

    if (0 === $connections && 0 === $touchdown_stops) {
        return __('Nonstop', 'walabu-travel');
    }

    $parts = array();

    if ($connections > 0) {
        $parts[] = sprintf(
            _n('%d connection', '%d connections', $connections, 'walabu-travel'),
            $connections
        );
    }

    if ($touchdown_stops > 0) {
        $parts[] = sprintf(
            _n('%d stop', '%d stops', $touchdown_stops, 'walabu-travel'),
            $touchdown_stops
        );
    }

    return implode(' + ', $parts);
}

function walabu_duffel_booking_get_offer_airline_name($offer, $segments) {
    $carrier_names = array();

    foreach ($segments as $segment) {
        if (!empty($segment['operating_carrier']['name']) && is_string($segment['operating_carrier']['name'])) {
            $carrier_names[] = trim($segment['operating_carrier']['name']);
        }

        if (!empty($segment['marketing_carrier']['name']) && is_string($segment['marketing_carrier']['name'])) {
            $carrier_names[] = trim($segment['marketing_carrier']['name']);
        }
    }

    $carrier_names = array_values(array_unique(array_filter($carrier_names)));

    if (!empty($carrier_names)) {
        return implode(' / ', $carrier_names);
    }

    if (!empty($offer['owner']['name']) && is_string($offer['owner']['name'])) {
        return trim($offer['owner']['name']);
    }

    return __('Unknown airline', 'walabu-travel');
}

function walabu_duffel_booking_get_offer_carrier_logo_url($segments) {
    $logo_urls = array();

    foreach ((array) $segments as $segment) {
        foreach (array('operating_carrier', 'marketing_carrier') as $carrier_key) {
            if (empty($segment[$carrier_key]) || !is_array($segment[$carrier_key])) {
                continue;
            }

            foreach (array('logo_symbol_url', 'logo_lockup_url') as $logo_key) {
                if (!empty($segment[$carrier_key][$logo_key]) && is_string($segment[$carrier_key][$logo_key])) {
                    $logo_urls[] = trim($segment[$carrier_key][$logo_key]);
                    break 2;
                }
            }
        }
    }

    $logo_urls = array_values(array_unique(array_filter($logo_urls)));

    return !empty($logo_urls) ? $logo_urls[0] : '';
}

function walabu_duffel_booking_format_condition_summary($condition, $kind = 'change') {
    $unknown_text = 'refund' === $kind
        ? __('Refund conditions unavailable', 'walabu-travel')
        : __('Change conditions unavailable', 'walabu-travel');

    if (!is_array($condition)) {
        return $unknown_text;
    }

    $allowed = $condition['allowed'] ?? null;
    $penalty_amount = $condition['penalty_amount'] ?? null;
    $penalty_currency = trim((string) ($condition['penalty_currency'] ?? ''));

    if (false === $allowed) {
        return 'refund' === $kind
            ? __('Refund not allowed', 'walabu-travel')
            : __('Changes not allowed', 'walabu-travel');
    }

    if (true !== $allowed) {
        return $unknown_text;
    }

    if (null === $penalty_amount || '' === (string) $penalty_amount) {
        return 'refund' === $kind
            ? __('Refund allowed, penalty unknown', 'walabu-travel')
            : __('Changes allowed, penalty unknown', 'walabu-travel');
    }

    if ((float) $penalty_amount <= 0) {
        return 'refund' === $kind
            ? __('Refund allowed free of charge', 'walabu-travel')
            : __('Changes allowed free of charge', 'walabu-travel');
    }

    $penalty_display = trim($penalty_currency . ' ' . $penalty_amount);

    return 'refund' === $kind
        ? sprintf(__('Refund allowed with %s penalty', 'walabu-travel'), $penalty_display)
        : sprintf(__('Changes allowed with %s penalty', 'walabu-travel'), $penalty_display);
}

function walabu_duffel_booking_get_slice_change_summary($slices) {
    if (!is_array($slices) || count($slices) < 2) {
        return '';
    }

    $changeable_count = 0;
    $non_changeable_count = 0;

    foreach ($slices as $slice) {
        $condition = $slice['conditions']['change_before_departure'] ?? null;

        if (is_array($condition) && true === ($condition['allowed'] ?? null)) {
            $changeable_count++;
        } elseif (is_array($condition) && false === ($condition['allowed'] ?? null)) {
            $non_changeable_count++;
        }
    }

    if ($changeable_count > 0 && $non_changeable_count > 0) {
        return __('Some slices can be changed separately even though the whole trip cannot be changed together.', 'walabu-travel');
    }

    return '';
}

function walabu_duffel_booking_parse_iso_duration_minutes($value) {
    $value = (string) $value;

    if (!preg_match('/^P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?$/', $value, $matches)) {
        return null;
    }

    $days = isset($matches[1]) ? (int) $matches[1] : 0;
    $hours = isset($matches[2]) ? (int) $matches[2] : 0;
    $minutes = isset($matches[3]) ? (int) $matches[3] : 0;

    return ($days * 24 * 60) + ($hours * 60) + $minutes;
}

function walabu_duffel_booking_format_time_compact($value) {
    $date = walabu_duffel_booking_parse_datetime($value);

    if (!$date instanceof DateTimeImmutable) {
        return __('Unavailable', 'walabu-travel');
    }

    return $date->format('g:iA');
}

function walabu_duffel_booking_format_time_compact_local($value, $time_zone = '') {
    $date = walabu_duffel_booking_parse_datetime_in_timezone($value, $time_zone);

    if (!$date instanceof DateTimeImmutable) {
        return __('Unavailable', 'walabu-travel');
    }

    $time_zone = trim((string) $time_zone);

    if ('' !== $time_zone) {
        try {
            $date = $date->setTimezone(new DateTimeZone($time_zone));
        } catch (Exception $exception) {
            // Fall back to the parsed timestamp if the timezone is invalid.
        }
    }

    return $date->format('g:iA');
}

function walabu_duffel_booking_format_timezone_abbreviation($time_zone = '', $value = '') {
    $time_zone = trim((string) $time_zone);

    if ('' === $time_zone) {
        return '';
    }

    try {
        $timezone = new DateTimeZone($time_zone);
    } catch (Exception $exception) {
        return '';
    }

    $date = walabu_duffel_booking_parse_datetime_in_timezone($value, $time_zone);

    if (!$date instanceof DateTimeImmutable) {
        $date = new DateTimeImmutable('now', $timezone);
    }

    return $date->setTimezone($timezone)->format('T');
}

function walabu_duffel_booking_format_day_offset($start, $end) {
    $start_date = walabu_duffel_booking_parse_datetime($start);
    $end_date = walabu_duffel_booking_parse_datetime($end);

    if (!$start_date instanceof DateTimeImmutable || !$end_date instanceof DateTimeImmutable || $end_date <= $start_date) {
        return '';
    }

    $day_difference = (int) $start_date->setTime(0, 0)->diff($end_date->setTime(0, 0))->format('%a');

    if ($day_difference <= 0) {
        return '';
    }

    return '+' . $day_difference;
}

function walabu_duffel_booking_format_money($currency, $amount) {
    $currency = trim((string) $currency);

    if (null === $amount || '' === (string) $amount) {
        return trim($currency);
    }

    return trim($currency . ' ' . number_format((float) $amount, 2, '.', ','));
}

function walabu_duffel_booking_get_segment_airport_code($segment, $endpoint) {
    if (!is_array($segment)) {
        return '';
    }

    $airport = is_array($segment[$endpoint] ?? null) ? $segment[$endpoint] : array();

    if (!empty($airport['iata_code']) && is_string($airport['iata_code'])) {
        return strtoupper(trim($airport['iata_code']));
    }

    if (!empty($airport['iata_city_code']) && is_string($airport['iata_city_code'])) {
        return strtoupper(trim($airport['iata_city_code']));
    }

    return '';
}

function walabu_duffel_booking_get_airline_key($name) {
    $name = sanitize_title((string) $name);

    if ('' === $name) {
        return '';
    }

    return $name . '-' . substr(md5($name), 0, 6);
}

function walabu_duffel_booking_get_layover_summary($segments) {
    $segments = is_array($segments) ? array_values($segments) : array();
    $parts = array();

    for ($index = 0; $index < count($segments) - 1; $index++) {
        $current_segment = $segments[$index];
        $next_segment = $segments[$index + 1];
        $airport_code = walabu_duffel_booking_get_segment_airport_code($current_segment, 'destination');
        $duration = walabu_duffel_booking_format_duration_from_datetimes(
            $current_segment['arriving_at'] ?? '',
            $next_segment['departing_at'] ?? ''
        );

        if ('Unavailable' === $duration) {
            $duration = '';
        }

        if ('' !== $duration && '' !== $airport_code) {
            $parts[] = sprintf(__('%1$s in %2$s', 'walabu-travel'), $duration, $airport_code);
        } elseif ('' !== $airport_code) {
            $parts[] = $airport_code;
        }
    }

    foreach ($segments as $segment) {
        if (empty($segment['stops']) || !is_array($segment['stops'])) {
            continue;
        }

        foreach ($segment['stops'] as $stop) {
            $airport_code = '';

            if (!empty($stop['airport']['iata_code']) && is_string($stop['airport']['iata_code'])) {
                $airport_code = strtoupper(trim($stop['airport']['iata_code']));
            }

            $duration_minutes = walabu_duffel_booking_parse_iso_duration_minutes($stop['duration'] ?? '');
            $duration = null !== $duration_minutes
                ? sprintf(
                    _x('%1$dh %2$dm', 'flight duration', 'walabu-travel'),
                    floor($duration_minutes / 60),
                    $duration_minutes % 60
                )
                : '';

            if ('' !== $duration && '' !== $airport_code) {
                $parts[] = sprintf(__('%1$s stop in %2$s', 'walabu-travel'), $duration, $airport_code);
            } elseif ('' !== $airport_code) {
                $parts[] = sprintf(__('Stop in %s', 'walabu-travel'), $airport_code);
            }
        }
    }

    return implode(' • ', array_filter($parts));
}

function walabu_duffel_booking_get_offer_flexibility_score($offer, $slice_change_note) {
    $score = 0;
    $conditions = is_array($offer['conditions'] ?? null) ? $offer['conditions'] : array();

    foreach (array('change_before_departure', 'refund_before_departure') as $key) {
        $condition = is_array($conditions[$key] ?? null) ? $conditions[$key] : null;

        if (!is_array($condition)) {
            $score += 1;
            continue;
        }

        if (true !== ($condition['allowed'] ?? null)) {
            continue;
        }

        $score += 4;

        if (null === ($condition['penalty_amount'] ?? null) || '' === (string) $condition['penalty_amount']) {
            $score += 1;
            continue;
        }

        if ((float) $condition['penalty_amount'] <= 0) {
            $score += 2;
        }
    }

    if ('' !== $slice_change_note) {
        $score += 1;
    }

    return $score;
}

function walabu_duffel_booking_get_slice_summary($slice) {
    $segments = is_array($slice['segments'] ?? null) ? array_values($slice['segments']) : array();
    $first_segment = !empty($segments) ? reset($segments) : array();
    $last_segment = !empty($segments) ? end($segments) : array();
    $origin_time_zone = walabu_duffel_booking_get_segment_airport_timezone($first_segment, 'origin');
    $destination_time_zone = walabu_duffel_booking_get_segment_airport_timezone($last_segment, 'destination');
    $carrier_names = array();

    foreach ($segments as $segment) {
        if (!empty($segment['operating_carrier']['name']) && is_string($segment['operating_carrier']['name'])) {
            $carrier_names[] = trim($segment['operating_carrier']['name']);
        }

        if (!empty($segment['marketing_carrier']['name']) && is_string($segment['marketing_carrier']['name'])) {
            $carrier_names[] = trim($segment['marketing_carrier']['name']);
        }
    }

    $carrier_names = array_values(array_unique(array_filter($carrier_names)));
    $duration_minutes = walabu_duffel_booking_parse_iso_duration_minutes($slice['duration'] ?? '');
    $stop_count = max(0, count($segments) - 1);

    foreach ($segments as $segment) {
        if (!empty($segment['stops']) && is_array($segment['stops'])) {
            $stop_count += count($segment['stops']);
        }
    }

    return array(
        'departure_time' => walabu_duffel_booking_format_time_compact_local(
            $first_segment['departing_at'] ?? '',
            $origin_time_zone
        ),
        'arrival_time'   => walabu_duffel_booking_format_time_compact_local(
            $last_segment['arriving_at'] ?? '',
            $destination_time_zone
        ),
        'arrival_offset' => walabu_duffel_booking_format_day_offset_local(
            $first_segment['departing_at'] ?? '',
            $origin_time_zone,
            $last_segment['arriving_at'] ?? '',
            $destination_time_zone
        ),
        'departure_full' => walabu_duffel_booking_format_datetime_local(
            $first_segment['departing_at'] ?? '',
            $origin_time_zone
        ),
        'arrival_full'   => walabu_duffel_booking_format_datetime_local(
            $last_segment['arriving_at'] ?? '',
            $destination_time_zone
        ),
        'departure_timezone' => walabu_duffel_booking_format_timezone_abbreviation(
            walabu_duffel_booking_get_segment_airport_timezone($first_segment, 'origin'),
            $first_segment['departing_at'] ?? ''
        ),
        'arrival_timezone' => walabu_duffel_booking_format_timezone_abbreviation(
            walabu_duffel_booking_get_segment_airport_timezone($last_segment, 'destination'),
            $last_segment['arriving_at'] ?? ''
        ),
        'origin_code'    => walabu_duffel_booking_get_segment_airport_code($first_segment, 'origin'),
        'destination_code' => walabu_duffel_booking_get_segment_airport_code($last_segment, 'destination'),
        'origin_time_zone' => $origin_time_zone,
        'destination_time_zone' => $destination_time_zone,
        'carriers'       => $carrier_names,
        'carriers_text'  => !empty($carrier_names) ? implode(', ', $carrier_names) : __('Unknown airline', 'walabu-travel'),
        'carrier_logo_url' => walabu_duffel_booking_get_offer_carrier_logo_url($segments),
        'duration'       => walabu_duffel_booking_format_iso_duration($slice['duration'] ?? '') ?: walabu_duffel_booking_format_duration_from_datetimes_local(
            $first_segment['departing_at'] ?? '',
            $origin_time_zone,
            $last_segment['arriving_at'] ?? '',
            $destination_time_zone
        ),
        'duration_minutes' => null !== $duration_minutes ? $duration_minutes : 0,
        'stops'          => walabu_duffel_booking_format_stops($segments),
        'stop_count'     => $stop_count,
        'layovers'       => walabu_duffel_booking_get_layover_summary($segments),
    );
}

function walabu_duffel_booking_map_offers($offers) {
    $mapped_offers = array();

    foreach ((array) $offers as $offer) {
        $slices = is_array($offer['slices'] ?? null) ? $offer['slices'] : array();
        $primary_slice = $slices[0] ?? array();
        $primary_segments = is_array($primary_slice['segments'] ?? null) ? $primary_slice['segments'] : array();
        $primary_first_segment = !empty($primary_segments) ? reset($primary_segments) : array();
        $primary_last_segment = !empty($primary_segments) ? end($primary_segments) : array();
        $slice_change_note = walabu_duffel_booking_get_slice_change_summary($slices);
        $amount = isset($offer['total_amount']) ? (float) $offer['total_amount'] : 0.0;
        $currency = trim((string) ($offer['total_currency'] ?? ''));
        $passenger_count = !empty($offer['passengers']) && is_array($offer['passengers']) ? count($offer['passengers']) : 1;
        $slice_summaries = array();
        $airline_names = array();
        $stop_count_max = 0;
        $total_duration_minutes = 0;

        foreach ($slices as $slice) {
            $slice_summary = walabu_duffel_booking_get_slice_summary($slice);
            $slice_summaries[] = $slice_summary;
            $airline_names = array_merge($airline_names, $slice_summary['carriers']);
            $stop_count_max = max($stop_count_max, (int) $slice_summary['stop_count']);
            $total_duration_minutes += (int) $slice_summary['duration_minutes'];
        }

        $airline_names = array_values(array_unique(array_filter($airline_names)));
        $airline_filters = array();

        foreach ($airline_names as $airline_name) {
            $airline_filters[] = array(
                'key'   => walabu_duffel_booking_get_airline_key($airline_name),
                'label' => $airline_name,
            );
        }

        if (0 === $total_duration_minutes && !empty($slice_summaries)) {
            foreach ($slice_summaries as $slice_summary) {
                $total_duration_minutes += (int) $slice_summary['duration_minutes'];
            }
        }

        $stop_bucket = '2_plus';

        if (0 === $stop_count_max) {
            $stop_bucket = 'nonstop';
        } elseif (1 === $stop_count_max) {
            $stop_bucket = '1_stop';
        }

        $mapped_offers[] = array(
            'offer_id'       => (string) ($offer['id'] ?? ''),
            'airline_name'   => walabu_duffel_booking_get_offer_airline_name($offer, $primary_segments),
            'airline_display_name' => !empty($airline_names) ? implode(' / ', $airline_names) : walabu_duffel_booking_get_offer_airline_name($offer, $primary_segments),
            'airline_logo_url' => !empty($slice_summaries[0]['carrier_logo_url']) ? $slice_summaries[0]['carrier_logo_url'] : walabu_duffel_booking_get_offer_carrier_logo_url($primary_segments),
            'price'          => walabu_duffel_booking_format_money($currency, $amount),
            'price_amount'   => $amount,
            'price_currency' => $currency,
            'price_per_passenger' => $passenger_count > 0 ? ($amount / $passenger_count) : $amount,
            'departure_time' => walabu_duffel_booking_format_datetime($primary_first_segment['departing_at'] ?? ''),
            'arrival_time'   => walabu_duffel_booking_format_datetime($primary_last_segment['arriving_at'] ?? ''),
            'duration'       => !empty($slice_summaries[0]['duration']) ? $slice_summaries[0]['duration'] : '',
            'stops'          => !empty($slice_summaries[0]['stops']) ? $slice_summaries[0]['stops'] : '',
            'stops_bucket'   => $stop_bucket,
            'stop_count_max' => $stop_count_max,
            'private_fares'  => is_array($offer['private_fares'] ?? null) ? $offer['private_fares'] : array(),
            'change_summary' => walabu_duffel_booking_format_condition_summary($offer['conditions']['change_before_departure'] ?? null, 'change'),
            'refund_summary' => walabu_duffel_booking_format_condition_summary($offer['conditions']['refund_before_departure'] ?? null, 'refund'),
            'slice_change_note' => $slice_change_note,
            'airline_names'  => $airline_names,
            'airline_filters' => $airline_filters,
            'slices'         => $slice_summaries,
            'total_duration_minutes' => $total_duration_minutes,
            'flexibility_score' => walabu_duffel_booking_get_offer_flexibility_score($offer, $slice_change_note),
        );
    }

    return $mapped_offers;
}

function walabu_duffel_booking_get_results_view_state() {
    $allowed_stops = array('nonstop', '1_stop', '2_plus');
    $allowed_sorts = array('best', 'cheapest', 'shortest', 'flexible');
    $filter_stops = isset($_GET['filter_stops']) ? (array) wp_unslash($_GET['filter_stops']) : array();
    $filter_airlines = isset($_GET['filter_airlines']) ? (array) wp_unslash($_GET['filter_airlines']) : array();
    $sort_by = sanitize_key(wp_unslash($_GET['sort_by'] ?? 'best'));

    $filter_stops = array_values(array_intersect($allowed_stops, array_map('sanitize_key', $filter_stops)));
    $filter_airlines = array_values(array_filter(array_map('sanitize_key', $filter_airlines)));

    if (!in_array($sort_by, $allowed_sorts, true)) {
        $sort_by = 'best';
    }

    return array(
        'filter_stops'    => $filter_stops,
        'filter_airlines' => $filter_airlines,
        'sort_by'         => $sort_by,
    );
}

function walabu_duffel_booking_get_selected_offer_id() {
    return sanitize_text_field(wp_unslash($_GET['o'] ?? $_GET['selected_offer'] ?? ''));
}

function walabu_duffel_booking_get_selected_offer_token() {
    return sanitize_text_field(wp_unslash($_GET['t'] ?? $_GET['selected_offer_token'] ?? ''));
}

function walabu_duffel_booking_get_booking_step() {
    $allowed_steps = array('results', 'details', 'summary', 'checkout');
    $step = sanitize_key(wp_unslash($_GET['b'] ?? $_GET['booking_step'] ?? $_GET['s'] ?? 'results'));

    if ('summary' === $step) {
        $step = 'details';
    }

    if (!in_array($step, $allowed_steps, true)) {
        return 'results';
    }

    return $step;
}

function walabu_duffel_booking_find_offer_by_id($offers, $offer_id) {
    $offer_id = (string) $offer_id;

    if ('' === $offer_id) {
        return null;
    }

    foreach ((array) $offers as $offer) {
        if (!is_array($offer)) {
            continue;
        }

        if (!empty($offer['offer_id']) && $offer['offer_id'] === $offer_id) {
            return $offer;
        }
    }

    return null;
}

function walabu_duffel_booking_get_offer_cache_key($search_values, $offer_id) {
    $search_signature = wp_json_encode(array(
        'origin' => $search_values['origin'] ?? '',
        'destination' => $search_values['destination'] ?? '',
        'departure_date' => $search_values['departure_date'] ?? '',
        'return_date' => $search_values['return_date'] ?? '',
        'trip_type' => $search_values['trip_type'] ?? '',
        'passengers' => $search_values['passengers'] ?? 1,
        'cabin_class' => $search_values['cabin_class'] ?? 'economy',
        'max_connections' => $search_values['max_connections'] ?? '',
    ));

    return 'walabu_duffel_offer_' . md5($search_signature . '|' . (string) $offer_id);
}

function walabu_duffel_booking_cache_offer_snapshot($search_values, $offer) {
    if (empty($offer['offer_id'])) {
        return '';
    }

    $cache_key = walabu_duffel_booking_get_offer_cache_key($search_values, $offer['offer_id']);
    set_transient($cache_key, $offer, HOUR_IN_SECONDS);

    return $cache_key;
}

function walabu_duffel_booking_get_cached_offer_snapshot($search_values, $offer_id, $token = '') {
    $offer_id = (string) $offer_id;
    $token = trim((string) $token);

    if ('' !== $token) {
        $cached_offer = get_transient($token);

        if (is_array($cached_offer) && !empty($cached_offer['offer_id']) && $cached_offer['offer_id'] === $offer_id) {
            return $cached_offer;
        }
    }

    if ('' === $offer_id) {
        return null;
    }

    $cached_offer = get_transient(walabu_duffel_booking_get_offer_cache_key($search_values, $offer_id));

    if (is_array($cached_offer) && !empty($cached_offer['offer_id']) && $cached_offer['offer_id'] === $offer_id) {
        return $cached_offer;
    }

    return null;
}

function walabu_duffel_booking_offer_matches_results_filters($offer, $view_state) {
    if (!empty($view_state['filter_stops']) && !in_array($offer['stops_bucket'] ?? '', $view_state['filter_stops'], true)) {
        return false;
    }

    if (!empty($view_state['filter_airlines'])) {
        $offer_airline_keys = array();

        foreach ((array) ($offer['airline_filters'] ?? array()) as $airline_filter) {
            if (!empty($airline_filter['key'])) {
                $offer_airline_keys[] = (string) $airline_filter['key'];
            }
        }

        if (empty(array_intersect($offer_airline_keys, $view_state['filter_airlines']))) {
            return false;
        }
    }

    return true;
}

function walabu_duffel_booking_sort_offers_for_results($offers, $sort_by) {
    $offers = array_values($offers);

    if (empty($offers)) {
        return $offers;
    }

    if ('best' === $sort_by) {
        $prices = array_map(static function ($offer) {
            return (float) ($offer['price_amount'] ?? 0);
        }, $offers);
        $durations = array_map(static function ($offer) {
            return (int) ($offer['total_duration_minutes'] ?? 0);
        }, $offers);
        $flex_scores = array_map(static function ($offer) {
            return (int) ($offer['flexibility_score'] ?? 0);
        }, $offers);
        $min_price = min($prices);
        $max_price = max($prices);
        $min_duration = min($durations);
        $max_duration = max($durations);
        $min_flex = min($flex_scores);
        $max_flex = max($flex_scores);

        usort(
            $offers,
            static function ($left, $right) use ($min_price, $max_price, $min_duration, $max_duration, $min_flex, $max_flex) {
                $left_price = (float) ($left['price_amount'] ?? 0);
                $right_price = (float) ($right['price_amount'] ?? 0);
                $left_duration = (int) ($left['total_duration_minutes'] ?? 0);
                $right_duration = (int) ($right['total_duration_minutes'] ?? 0);
                $left_flex = (int) ($left['flexibility_score'] ?? 0);
                $right_flex = (int) ($right['flexibility_score'] ?? 0);

                $left_price_score = $max_price > $min_price ? (($left_price - $min_price) / ($max_price - $min_price)) : 0;
                $right_price_score = $max_price > $min_price ? (($right_price - $min_price) / ($max_price - $min_price)) : 0;
                $left_duration_score = $max_duration > $min_duration ? (($left_duration - $min_duration) / ($max_duration - $min_duration)) : 0;
                $right_duration_score = $max_duration > $min_duration ? (($right_duration - $min_duration) / ($max_duration - $min_duration)) : 0;
                $left_flex_score = $max_flex > $min_flex ? (($left_flex - $min_flex) / ($max_flex - $min_flex)) : 0;
                $right_flex_score = $max_flex > $min_flex ? (($right_flex - $min_flex) / ($max_flex - $min_flex)) : 0;

                $left_score = (0.55 * $left_price_score) + (0.30 * $left_duration_score) + (0.15 * (1 - $left_flex_score));
                $right_score = (0.55 * $right_price_score) + (0.30 * $right_duration_score) + (0.15 * (1 - $right_flex_score));

                if ($left_score === $right_score) {
                    return $left_price <=> $right_price;
                }

                return $left_score <=> $right_score;
            }
        );

        return $offers;
    }

    usort(
        $offers,
        static function ($left, $right) use ($sort_by) {
            $left_price = (float) ($left['price_amount'] ?? 0);
            $right_price = (float) ($right['price_amount'] ?? 0);
            $left_duration = (int) ($left['total_duration_minutes'] ?? 0);
            $right_duration = (int) ($right['total_duration_minutes'] ?? 0);
            $left_flex = (int) ($left['flexibility_score'] ?? 0);
            $right_flex = (int) ($right['flexibility_score'] ?? 0);

            if ('shortest' === $sort_by) {
                return $left_duration === $right_duration ? ($left_price <=> $right_price) : ($left_duration <=> $right_duration);
            }

            if ('flexible' === $sort_by) {
                return $left_flex === $right_flex ? ($left_price <=> $right_price) : ($right_flex <=> $left_flex);
            }

            return $left_price === $right_price ? ($left_duration <=> $right_duration) : ($left_price <=> $right_price);
        }
    );

    return $offers;
}

function walabu_duffel_booking_get_search_query_args($search_values) {
    $args = array(
        'walabu_flight_search_submit' => '1',
        'origin' => $search_values['origin'] ?? '',
        'origin_label' => $search_values['origin_label'] ?? '',
        'destination' => $search_values['destination'] ?? '',
        'destination_label' => $search_values['destination_label'] ?? '',
        'trip_type' => $search_values['trip_type'] ?? 'one_way',
        'departure_date' => $search_values['departure_date'] ?? '',
        'return_date' => $search_values['return_date'] ?? '',
        'passengers' => $search_values['passengers'] ?? 1,
        'cabin_class' => $search_values['cabin_class'] ?? 'economy',
    );

    if ('' !== (string) ($search_values['max_connections'] ?? '')) {
        $args['max_connections'] = $search_values['max_connections'];
    }

    if (!empty($search_values['fare_type'])) {
        $args['fare_type'] = $search_values['fare_type'];
    }

    $selected_offer_id = walabu_duffel_booking_get_selected_offer_id();

    if ('' !== $selected_offer_id) {
        $args['selected_offer'] = $selected_offer_id;
    }

    if ('one_way' === ($search_values['trip_type'] ?? 'one_way')) {
        unset($args['return_date']);
    }

    return $args;
}

function walabu_duffel_booking_get_current_request_url() {
    $request_uri = wp_unslash($_SERVER['REQUEST_URI'] ?? '/');
    $path = strtok($request_uri, '?');

    return home_url($path ?: '/');
}

function walabu_duffel_booking_get_current_full_request_url() {
    $request_uri = wp_unslash($_SERVER['REQUEST_URI'] ?? '/');

    return home_url($request_uri ?: '/');
}

function walabu_duffel_booking_get_booking_page_url() {
    $page_id = absint(get_option('walabu_duffel_booking_page_id', 0));

    if ($page_id > 0) {
        $page_url = get_permalink($page_id);

        if (!empty($page_url)) {
            return $page_url;
        }
    }

    return walabu_duffel_booking_get_current_request_url();
}

function walabu_duffel_booking_get_refresh_url() {
    $state = walabu_duffel_booking_get_form_state();
    $values = $state['values'] ?? array();

    if (empty($values['origin']) || empty($values['destination']) || empty($values['departure_date'])) {
        return walabu_duffel_booking_get_current_request_url();
    }

    return walabu_duffel_booking_build_results_url($values, walabu_duffel_booking_get_results_view_state());
}

function walabu_duffel_booking_build_results_url($search_values, $view_state, $overrides = array(), $base_url = null) {
    if (null === $base_url || '' === trim((string) $base_url)) {
        $base_url = walabu_duffel_booking_get_current_request_url();
    }

    $args = array_merge(
        walabu_duffel_booking_get_search_query_args($search_values),
        array(
            'sort_by' => $view_state['sort_by'] ?? 'best',
        )
    );

    if (!empty($view_state['filter_stops'])) {
        $args['filter_stops'] = array_values((array) $view_state['filter_stops']);
    }

    if (!empty($view_state['filter_airlines'])) {
        $args['filter_airlines'] = array_values((array) $view_state['filter_airlines']);
    }

    foreach ($overrides as $key => $value) {
        if (null === $value || '' === $value || (is_array($value) && empty($value))) {
            unset($args[$key]);
            continue;
        }

        $args[$key] = $value;
    }

    return add_query_arg($args, $base_url);
}

function walabu_duffel_booking_build_results_context($offers) {
    $view_state = walabu_duffel_booking_get_results_view_state();
    $selected_offer_id = walabu_duffel_booking_get_selected_offer_id();
    $stop_options = array(
        'nonstop' => array(
            'label' => __('Nonstop', 'walabu-travel'),
            'count' => 0,
            'min_price' => null,
            'currency' => '',
        ),
        '1_stop' => array(
            'label' => __('1 Stop', 'walabu-travel'),
            'count' => 0,
            'min_price' => null,
            'currency' => '',
        ),
        '2_plus' => array(
            'label' => __('2+ Stops', 'walabu-travel'),
            'count' => 0,
            'min_price' => null,
            'currency' => '',
        ),
    );
    $airline_options = array();

    foreach ($offers as $offer) {
        $bucket = $offer['stops_bucket'] ?? '2_plus';

        if (isset($stop_options[$bucket])) {
            $stop_options[$bucket]['count']++;

            if (null === $stop_options[$bucket]['min_price'] || $offer['price_amount'] < $stop_options[$bucket]['min_price']) {
                $stop_options[$bucket]['min_price'] = (float) $offer['price_amount'];
                $stop_options[$bucket]['currency'] = (string) ($offer['price_currency'] ?? '');
            }
        }

        foreach ((array) ($offer['airline_filters'] ?? array()) as $airline_filter) {
            $key = (string) ($airline_filter['key'] ?? '');

            if ('' === $key) {
                continue;
            }

            if (!isset($airline_options[$key])) {
                $airline_options[$key] = array(
                    'label' => (string) ($airline_filter['label'] ?? ''),
                    'count' => 0,
                    'min_price' => null,
                    'currency' => '',
                );
            }

            $airline_options[$key]['count']++;

            if (null === $airline_options[$key]['min_price'] || $offer['price_amount'] < $airline_options[$key]['min_price']) {
                $airline_options[$key]['min_price'] = (float) $offer['price_amount'];
                $airline_options[$key]['currency'] = (string) ($offer['price_currency'] ?? '');
            }
        }
    }

    uasort(
        $airline_options,
        static function ($left, $right) {
            return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        }
    );

    $filtered_offers = array_values(
        array_filter(
            $offers,
            static function ($offer) use ($view_state) {
                return walabu_duffel_booking_offer_matches_results_filters($offer, $view_state);
            }
        )
    );

    $sorted_offers = walabu_duffel_booking_sort_offers_for_results($filtered_offers, $view_state['sort_by']);
    $cheapest_offers = walabu_duffel_booking_sort_offers_for_results($filtered_offers, 'cheapest');
    $shortest_offers = walabu_duffel_booking_sort_offers_for_results($filtered_offers, 'shortest');
    $flexible_offers = walabu_duffel_booking_sort_offers_for_results($filtered_offers, 'flexible');

    return array(
        'view_state' => $view_state,
        'selected_offer_id' => $selected_offer_id,
        'stop_options' => $stop_options,
        'airline_options' => $airline_options,
        'offers' => $sorted_offers,
        'raw_count' => count($offers),
        'filtered_count' => count($filtered_offers),
        'displayed_count' => count($sorted_offers),
        'tabs' => array(
            'best' => array(
                'label' => __('Best', 'walabu-travel'),
                'offer' => !empty($sorted_offers) ? $sorted_offers[0] : null,
            ),
            'cheapest' => array(
                'label' => __('Cheapest', 'walabu-travel'),
                'offer' => !empty($cheapest_offers) ? $cheapest_offers[0] : null,
            ),
            'shortest' => array(
                'label' => __('Shortest', 'walabu-travel'),
                'offer' => !empty($shortest_offers) ? $shortest_offers[0] : null,
            ),
            'flexible' => array(
                'label' => __('Flexible', 'walabu-travel'),
                'offer' => !empty($flexible_offers) ? $flexible_offers[0] : null,
            ),
        ),
    );
}

function walabu_duffel_booking_search_offers($values) {
    $access_token = walabu_duffel_booking_get_access_token();

    if ('' === $access_token) {
        return array(
            'success' => false,
            'message' => __('Duffel API key is missing. Add a test key in Settings > Walabu Duffel Booking or define WALABU_DUFFEL_BOOKING_ACCESS_TOKEN.', 'walabu-travel'),
            'offers'  => array(),
        );
    }

    $response = wp_remote_post(
        add_query_arg(
            array(
                'return_offers'    => 'false',
                'supplier_timeout' => WALABU_DUFFEL_BOOKING_SUPPLIER_TIMEOUT,
            ),
            'https://api.duffel.com/air/offer_requests'
        ),
        array(
            'timeout' => 70,
            'headers' => walabu_duffel_booking_get_api_headers($access_token),
            'body'    => wp_json_encode(walabu_duffel_booking_build_offer_request_payload($values)),
        )
    );

    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => sprintf(
                __('Duffel search failed: %s', 'walabu-travel'),
                $response->get_error_message()
            ),
            'offers'  => array(),
        );
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($status_code < 200 || $status_code >= 300) {
        $error_message = walabu_duffel_booking_extract_error_message($body);

        if ('' === $error_message) {
            $error_message = __('Duffel returned an unexpected error response.', 'walabu-travel');
        }

        return array(
            'success' => false,
            'message' => sprintf(
                __('Duffel search failed with HTTP %1$d: %2$s', 'walabu-travel'),
                $status_code,
                $error_message
            ),
            'offers'  => array(),
        );
    }

    $offer_request_id = (string) ($body['data']['id'] ?? '');

    if ('' === $offer_request_id) {
        return array(
            'success' => false,
            'message' => __('Duffel did not return an offer request ID for this search.', 'walabu-travel'),
            'offers'  => array(),
        );
    }

    $offers = array();
    $direct_offers = $body['data']['offers'] ?? array();

    if (is_array($direct_offers) && !empty($direct_offers)) {
        $offers = array_values($direct_offers);
    }

    $after = null;
    $page_limit = 200;
    $max_pages = 10;
    $page = 0;
    $last_error_message = '';

    while ($page < $max_pages) {
        $request_args = array(
            'offer_request_id' => $offer_request_id,
            'limit'            => $page_limit,
            'sort'             => 'total_amount',
        );

        if ('' !== (string) $values['max_connections']) {
            $request_args['max_connections'] = (int) $values['max_connections'];
        }

        if (null !== $after && '' !== $after) {
            $request_args['after'] = $after;
        }

        $offers_response = wp_remote_get(
            add_query_arg(
                $request_args,
                'https://api.duffel.com/air/offers'
            ),
            array(
                'timeout' => 70,
                'headers' => array(
                    'Authorization'  => 'Bearer ' . $access_token,
                    'Duffel-Version' => WALABU_DUFFEL_BOOKING_API_VERSION,
                    'Accept'         => 'application/json',
                ),
            )
        );

        if (is_wp_error($offers_response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Duffel offer list failed: %s', 'walabu-travel'),
                    $offers_response->get_error_message()
                ),
                'offers'  => array(),
            );
        }

        $offers_status_code = (int) wp_remote_retrieve_response_code($offers_response);
        $offers_body = json_decode(wp_remote_retrieve_body($offers_response), true);

        if ($offers_status_code < 200 || $offers_status_code >= 300) {
            $last_error_message = walabu_duffel_booking_extract_error_message($offers_body);

            if ('' === $last_error_message) {
                $last_error_message = __('Duffel returned an unexpected offers response.', 'walabu-travel');
            }

            break;
        }

        $page_offers = $offers_body['data'] ?? array();

        if (!empty($page_offers) && is_array($page_offers)) {
            $offers = array_merge($offers, $page_offers);
        }

        $after = $offers_body['meta']['after'] ?? null;
        $page++;

        if (empty($after)) {
            break;
        }
    }

    if ('' !== $last_error_message) {
        return array(
            'success' => false,
            'message' => sprintf(
                __('Duffel offers request failed: %s', 'walabu-travel'),
                $last_error_message
            ),
            'offers'  => array(),
        );
    }

    if (empty($offers) || !is_array($offers)) {
        return array(
            'success' => true,
            'message' => __('No flight offers were returned for this search.', 'walabu-travel'),
            'offers'  => array(),
        );
    }

    $unique_offers = array();

    foreach ($offers as $offer) {
        if (!is_array($offer)) {
            continue;
        }

        $offer_id = (string) ($offer['id'] ?? '');

        if ('' === $offer_id) {
            continue;
        }

        $unique_offers[$offer_id] = $offer;
    }

        return array(
            'success' => true,
            'message' => sprintf(
                __('Showing %1$d of %2$d available offers returned by Duffel.', 'walabu-travel'),
                count($unique_offers),
                count($unique_offers)
            ),
            'offers'  => walabu_duffel_booking_map_offers(array_values($unique_offers)),
        );
}

function walabu_duffel_booking_fetch_place_suggestions($query) {
    $access_token = walabu_duffel_booking_get_access_token();
    $query = trim((string) $query);

    if ('' === $access_token) {
        return new WP_Error('duffel_missing_token', __('Duffel API key is missing.', 'walabu-travel'));
    }

    if ('' === $query) {
        return array();
    }

    $response = wp_remote_get(
        add_query_arg(
            array(
                'query' => $query,
            ),
            'https://api.duffel.com/places/suggestions'
        ),
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization'  => 'Bearer ' . $access_token,
                'Duffel-Version' => WALABU_DUFFEL_BOOKING_API_VERSION,
                'Accept'         => 'application/json',
            ),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code < 200 || $status_code >= 300) {
        $error_message = walabu_duffel_booking_extract_error_message($body);

        if ('' === $error_message) {
            $error_message = __('Duffel returned an unexpected places response.', 'walabu-travel');
        }

        return new WP_Error('duffel_places_error', $error_message);
    }

    $suggestions = array();

    foreach ((array) ($body['data'] ?? array()) as $place) {
        $name = trim((string) ($place['name'] ?? ''));
        $iata_code = trim((string) ($place['iata_code'] ?? ''));
        $type = trim((string) ($place['type'] ?? ''));
        $city_name = trim((string) ($place['city_name'] ?? ''));
        if ('' === $iata_code || '' === $name) {
            continue;
        }

        if ('city' === $type) {
            $primary_label = sprintf('%s (%s)', $name, $iata_code);
        } elseif ('' !== $city_name && $city_name !== $name) {
            $primary_label = sprintf('%s / %s (%s)', $city_name, $name, $iata_code);
        } else {
            $primary_label = sprintf('%s (%s)', $name, $iata_code);
        }

        $secondary_parts = array();

        if ('city' === $type && !empty($place['airports']) && is_array($place['airports'])) {
            $airport_summaries = array();

            foreach (array_slice($place['airports'], 0, 3) as $airport) {
                if (
                    !empty($airport['iata_code']) && is_string($airport['iata_code']) &&
                    !empty($airport['name']) && is_string($airport['name'])
                ) {
                    $airport_summaries[] = sprintf('%s (%s)', $airport['name'], $airport['iata_code']);
                }
            }

            if (!empty($airport_summaries)) {
                $secondary_parts[] = sprintf(__('Airports: %s', 'walabu-travel'), implode(', ', $airport_summaries));
            }
        } elseif ('airport' === $type) {
            $secondary_parts[] = $name;
        }

        $suggestions[] = array(
            'value'      => $iata_code,
            'label'      => $primary_label,
            'secondary'  => implode(' • ', array_filter($secondary_parts)),
            'type'       => $type,
            'place_name'  => $name,
            'city_name'   => $city_name,
            'latitude'    => isset($place['latitude']) ? (float) $place['latitude'] : null,
            'longitude'   => isset($place['longitude']) ? (float) $place['longitude'] : null,
        );
    }

    usort(
        $suggestions,
        static function ($left, $right) {
            $left_type = $left['type'] ?? '';
            $right_type = $right['type'] ?? '';

            if ($left_type === $right_type) {
                return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
            }

            if ('city' === $left_type) {
                return -1;
            }

            if ('city' === $right_type) {
                return 1;
            }

            return 0;
        }
    );

    return array_slice($suggestions, 0, 8);
}

function walabu_duffel_booking_handle_place_suggestions() {
    check_ajax_referer('walabu_duffel_place_suggestions', 'nonce');
    nocache_headers();

    $query = sanitize_text_field(wp_unslash($_GET['query'] ?? ''));
    $suggestions = walabu_duffel_booking_fetch_place_suggestions($query);

    if (is_wp_error($suggestions)) {
        wp_send_json_error(
            array(
                'message' => $suggestions->get_error_message(),
            ),
            400
        );
    }

    wp_send_json_success(
        array(
            'suggestions' => $suggestions,
        )
    );
}

add_action('wp_ajax_walabu_duffel_place_suggestions', 'walabu_duffel_booking_handle_place_suggestions');
add_action('wp_ajax_nopriv_walabu_duffel_place_suggestions', 'walabu_duffel_booking_handle_place_suggestions');

function walabu_duffel_booking_fetch_nearby_airports($latitude, $longitude, $radius) {
    $access_token = walabu_duffel_booking_get_access_token();

    if ('' === $access_token) {
        return new WP_Error('duffel_missing_token', __('Duffel API key is missing.', 'walabu-travel'));
    }

    $response = wp_remote_get(
        add_query_arg(
            array(
                'lat' => $latitude,
                'lng' => $longitude,
                'rad' => max(1, (int) $radius),
            ),
            'https://api.duffel.com/places/suggestions'
        ),
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization'  => 'Bearer ' . $access_token,
                'Duffel-Version' => WALABU_DUFFEL_BOOKING_API_VERSION,
                'Accept'         => 'application/json',
            ),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code < 200 || $status_code >= 300) {
        $error_message = walabu_duffel_booking_extract_error_message($body);

        if ('' === $error_message) {
            $error_message = __('Duffel returned an unexpected nearby-airports response.', 'walabu-travel');
        }

        return new WP_Error('duffel_nearby_airports_error', $error_message);
    }

    $suggestions = array();

    foreach ((array) ($body['data'] ?? array()) as $place) {
        if ('airport' !== (string) ($place['type'] ?? '')) {
            continue;
        }

        $name = trim((string) ($place['name'] ?? ''));
        $iata_code = trim((string) ($place['iata_code'] ?? ''));
        $city_name = trim((string) ($place['city_name'] ?? ''));

        if ('' === $iata_code || '' === $name) {
            continue;
        }

        $suggestions[] = array(
            'value'     => $iata_code,
            'label'     => '' !== $city_name && $city_name !== $name
                ? sprintf('%s / %s (%s)', $city_name, $name, $iata_code)
                : sprintf('%s (%s)', $name, $iata_code),
            'secondary' => $name,
            'type'      => 'airport',
            'latitude'  => isset($place['latitude']) ? (float) $place['latitude'] : null,
            'longitude' => isset($place['longitude']) ? (float) $place['longitude'] : null,
        );
    }

    return array_slice($suggestions, 0, 8);
}

function walabu_duffel_booking_handle_nearby_airports() {
    check_ajax_referer('walabu_duffel_nearby_airports', 'nonce');
    nocache_headers();

    $latitude = isset($_GET['lat']) ? (float) wp_unslash($_GET['lat']) : 0;
    $longitude = isset($_GET['lng']) ? (float) wp_unslash($_GET['lng']) : 0;
    $radius = isset($_GET['rad']) ? absint($_GET['rad']) : 100000;

    if (0.0 === $latitude && 0.0 === $longitude) {
        wp_send_json_error(
            array(
                'message' => __('Latitude and longitude are required.', 'walabu-travel'),
            ),
            400
        );
    }

    $suggestions = walabu_duffel_booking_fetch_nearby_airports($latitude, $longitude, $radius);

    if (is_wp_error($suggestions)) {
        wp_send_json_error(
            array(
                'message' => $suggestions->get_error_message(),
            ),
            400
        );
    }

    wp_send_json_success(
        array(
            'suggestions' => $suggestions,
        )
    );
}

add_action('wp_ajax_walabu_duffel_nearby_airports', 'walabu_duffel_booking_handle_nearby_airports');
add_action('wp_ajax_nopriv_walabu_duffel_nearby_airports', 'walabu_duffel_booking_handle_nearby_airports');

function walabu_duffel_booking_get_search_result() {
    static $result = null;

    if (null !== $result) {
        return $result;
    }

    $state = walabu_duffel_booking_get_form_state();
    $result = array(
        'state'  => $state,
        'notice' => null,
        'offers' => array(),
    );

    if (!$state['submitted']) {
        return $result;
    }

    if (!empty($state['errors'])) {
        $result['notice'] = array(
            'type'    => 'error',
            'message' => implode(' ', $state['errors']),
        );

        return $result;
    }

    $search = walabu_duffel_booking_search_offers($state['values']);
    $result['offers'] = $search['offers'];
    $result['notice'] = array(
        'type'    => $search['success'] ? 'success' : 'error',
        'message' => $search['message'],
    );

    return $result;
}

function walabu_duffel_booking_icon($type) {
    $icons = array(
        'plane' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 13.5h7.4l8.4 5.1c.7.4 1.6-.2 1.4-1l-1.3-4.1 3.2-2.5c.6-.5.4-1.4-.4-1.6l-4-.8-3.5-5.5c-.4-.6-1.4-.5-1.7.2L10.3 9H3c-.8 0-1.5.7-1.5 1.5v1.5c0 .8.7 1.5 1.5 1.5Z" fill="currentColor"/></svg>',
        'calendar' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2h2v3H7V2Zm8 0h2v3h-2V2ZM4 5h16a1 1 0 0 1 1 1v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Zm0 5v10h16V10H4Z" fill="currentColor"/></svg>',
        'users' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm0 2c-1.7 0-3.2.5-4.2 1.4A5.9 5.9 0 0 1 15 18v2h7v-1c0-4-2.2-7-5-7Zm-8 1c-3.3 0-6 2.7-6 6v1h12v-1c0-3.3-2.7-6-6-6Z" fill="currentColor"/></svg>',
        'swap' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 7h11l-2.5-2.5L17 3l5 5-5 5-1.5-1.5L18 9H7V7Zm10 10H6l2.5 2.5L7 21l-5-5 5-5 1.5 1.5L6 15h11v2Z" fill="currentColor"/></svg>',
        'briefcase' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 5V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v11a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a2 2 0 0 1 2-2h3Zm2 0h4V4h-4v1Zm-5 5v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8h-3v2H8v-2H5Z" fill="currentColor"/></svg>',
    );

    return $icons[$type] ?? '';
}

function walabu_duffel_booking_render_notice($notice) {
    if (empty($notice['message'])) {
        return '';
    }

    $classes = 'booking-widget__notice';

    if ('success' === ($notice['type'] ?? '')) {
        $classes .= ' booking-widget__notice--success';
    }

    return sprintf(
        '<div class="%1$s" role="status"><span class="booking-widget__notice-icon">%2$s</span><span>%3$s</span></div>',
        esc_attr($classes),
        'success' === ($notice['type'] ?? '') ? '&#10003;' : '&#8855;',
        esc_html($notice['message'])
    );
}

function walabu_duffel_booking_render_offer_summary($selected_offer, $search_values, $view_state, $booking_step = 'details') {
    if (empty($selected_offer) || !is_array($selected_offer)) {
        return '';
    }

    $cabin_classes = walabu_duffel_booking_allowed_cabin_classes();
    $fare_label = $cabin_classes[$search_values['cabin_class'] ?? 'economy'] ?? __('Economy', 'walabu-travel');
    $route_origin = !empty($search_values['origin_label']) ? $search_values['origin_label'] : ($search_values['origin'] ?? '');
    $route_destination = !empty($search_values['destination_label']) ? $search_values['destination_label'] : ($search_values['destination'] ?? '');
    $trip_label = 'round_trip' === ($search_values['trip_type'] ?? 'one_way')
        ? __('Round trip', 'walabu-travel')
        : ('multi_city' === ($search_values['trip_type'] ?? 'one_way') ? __('Multi-city', 'walabu-travel') : __('One way', 'walabu-travel'));
    $title_date = !empty($search_values['departure_date']) ? wp_date('d M Y', strtotime($search_values['departure_date'])) : '';
    $breadcrumb = trim(sprintf(
        '%1$s %2$s %3$s',
        $route_origin,
        !empty($route_origin) && !empty($route_destination) ? '›' : '',
        $route_destination
    ));
    $back_url = remove_query_arg(
        array('b', 's', 'booking_step', 'o', 'selected_offer', 't', 'selected_offer_token'),
        walabu_duffel_booking_get_current_full_request_url()
    );
    $details_url = add_query_arg('b', 'details', walabu_duffel_booking_get_current_full_request_url());
    $checkout_url = add_query_arg('b', 'checkout', walabu_duffel_booking_get_current_full_request_url());
    $selected_slices = !empty($selected_offer['slices']) && is_array($selected_offer['slices']) ? $selected_offer['slices'] : array();
    $primary_slice = !empty($selected_slices) ? reset($selected_slices) : array();
    $selected_airline = trim((string) ($selected_offer['airline_display_name'] ?? $selected_offer['airline_name'] ?? __('Unknown airline', 'walabu-travel')));
    $selected_price = trim((string) ($selected_offer['price'] ?? __('Unavailable', 'walabu-travel')));
    $selected_total_amount = sprintf(
        '%s',
        walabu_duffel_booking_format_money(
            $selected_offer['price_currency'] ?? '',
            $selected_offer['price_amount'] ?? null
        )
    );
    $fare_type_label = !empty($search_values['fare_type'])
        ? sprintf(__('Private fare: %s', 'walabu-travel'), $search_values['fare_type'])
        : $fare_label;
    $conditions = array(
        'change' => !empty($selected_offer['change_summary']) ? $selected_offer['change_summary'] : __('No data on changes', 'walabu-travel'),
        'refund' => !empty($selected_offer['refund_summary']) ? $selected_offer['refund_summary'] : __('No data on refunds', 'walabu-travel'),
    );
    $checkout_passenger_count = max(1, min(9, absint($search_values['passengers'] ?? 1)));
    $checkout_passengers = array();
    $checkout_errors = array();
    $checkout_notice = '';
    $checkout_submitted = !empty($_POST['walabu_checkout_submit']) && 'checkout' === $booking_step;

    if ($checkout_submitted) {
        $posted_passengers = isset($_POST['checkout_passengers']) ? (array) wp_unslash($_POST['checkout_passengers']) : array();

        for ($passenger_index = 0; $passenger_index < $checkout_passenger_count; $passenger_index++) {
            $raw_passenger = isset($posted_passengers[$passenger_index]) && is_array($posted_passengers[$passenger_index])
                ? $posted_passengers[$passenger_index]
                : array();

            $passenger = array(
                'title'        => sanitize_key(wp_unslash($raw_passenger['title'] ?? 'mr')),
                'given_name'   => sanitize_text_field(wp_unslash($raw_passenger['given_name'] ?? '')),
                'family_name'  => sanitize_text_field(wp_unslash($raw_passenger['family_name'] ?? '')),
                'email'        => sanitize_email(wp_unslash($raw_passenger['email'] ?? '')),
                'phone_number' => sanitize_text_field(wp_unslash($raw_passenger['phone_number'] ?? '')),
                'born_on'      => sanitize_text_field(wp_unslash($raw_passenger['born_on'] ?? '')),
                'gender'       => sanitize_key(wp_unslash($raw_passenger['gender'] ?? '')),
            );

            if ('' === $passenger['given_name'] || '' === $passenger['family_name'] || '' === $passenger['email'] || '' === $passenger['born_on']) {
                $checkout_errors[] = sprintf(
                    __('Passenger %d is missing required details.', 'walabu-travel'),
                    $passenger_index + 1
                );
            }

            if ('' !== $passenger['email'] && !is_email($passenger['email'])) {
                $checkout_errors[] = sprintf(
                    __('Passenger %d email address is invalid.', 'walabu-travel'),
                    $passenger_index + 1
                );
            }

            $checkout_passengers[] = $passenger;
        }

        if (empty($checkout_errors)) {
            $checkout_notice = __('Passenger details saved for this selected flight. Booking is still disabled until order creation is wired up.', 'walabu-travel');
        }
    }

    if (empty($checkout_passengers)) {
        for ($passenger_index = 0; $passenger_index < $checkout_passenger_count; $passenger_index++) {
            $checkout_passengers[] = array(
                'title'        => 'mr',
                'given_name'   => '',
                'family_name'  => '',
                'email'        => '',
                'phone_number' => '',
                'born_on'      => '',
                'gender'       => '',
            );
        }
    }

    ob_start();
    ?>
    <section class="booking-summary" aria-label="<?php esc_attr_e('Selected flight summary', 'walabu-travel'); ?>">
        <div class="booking-summary__header">
            <a class="booking-summary__back" href="<?php echo esc_url($back_url); ?>">
                <span aria-hidden="true">←</span>
                <span><?php esc_html_e('Back to search', 'walabu-travel'); ?></span>
            </a>
        </div>

        <div class="booking-summary__layout">
            <article class="booking-summary__fare-card">
                <div class="booking-summary__fare-top">
                    <div class="booking-summary__airline-mark <?php echo !empty($selected_offer['airline_logo_url']) ? 'has-logo' : 'has-initials'; ?>">
                        <?php if (!empty($selected_offer['airline_logo_url'])) : ?>
                            <img src="<?php echo esc_url($selected_offer['airline_logo_url']); ?>" alt="<?php echo esc_attr($selected_airline); ?>" loading="lazy">
                        <?php else : ?>
                            <?php echo esc_html(walabu_duffel_booking_get_airline_key($selected_airline) ? strtoupper(substr(preg_replace('/[^A-Z]/', '', $selected_airline), 0, 2)) : 'FL'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="booking-summary__fare-times">
                        <?php foreach ($selected_slices as $slice_index => $slice) : ?>
                            <div class="booking-summary__slice">
                                <div class="booking-summary__slice-kicker">
                                    <?php echo esc_html(0 === $slice_index ? __('Outbound flight', 'walabu-travel') : ('checkout' === $booking_step ? sprintf(__('Leg %d', 'walabu-travel'), $slice_index + 1) : __('Return flight', 'walabu-travel'))); ?>
                                </div>
                                <div class="booking-summary__slice-line">
                                    <strong><?php echo esc_html($slice['departure_time'] ?? ''); ?></strong>
                                    <span><?php echo esc_html($slice['arrival_time'] ?? ''); ?></span>
                                </div>
                                <div class="booking-summary__slice-route">
                                    <?php echo esc_html(trim(($slice['origin_code'] ?? '') . ' - ' . ($slice['destination_code'] ?? ''))); ?>
                                </div>
                                <div class="booking-summary__slice-meta">
                                    <span><?php echo esc_html($slice['duration'] ?? ''); ?></span>
                                    <span><?php echo esc_html($slice['stops'] ?? ''); ?></span>
                                </div>
                                <?php if ($slice_index < (count($selected_slices) - 1)) : ?>
                                    <div class="booking-summary__slice-divider"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="booking-summary__fare-band">
                    <div class="booking-summary__fare-kicker"><?php echo esc_html(strtoupper($fare_label)); ?></div>
                    <div class="booking-summary__fare-name"><?php echo esc_html($fare_type_label); ?></div>
                    <ul class="booking-summary__fare-features">
                        <li><?php echo esc_html(sprintf(__('Changes: %s', 'walabu-travel'), $conditions['change'])); ?></li>
                        <li><?php echo esc_html(sprintf(__('Refunds: %s', 'walabu-travel'), $conditions['refund'])); ?></li>
                        <li><?php echo esc_html(__('Hold space information unavailable', 'walabu-travel')); ?></li>
                        <li><?php echo esc_html(!empty($selected_offer['private_fares']) ? __('Private fare applied', 'walabu-travel') : __('No data about checked bags', 'walabu-travel')); ?></li>
                    </ul>
                </div>

                <div class="booking-summary__fare-total">
                    <span><?php esc_html_e('total amount from', 'walabu-travel'); ?></span>
                    <strong><?php echo esc_html($selected_price); ?></strong>
                </div>
            </article>

            <aside class="booking-summary__panel">
                <h3><?php echo esc_html('checkout' === $booking_step ? __('Passenger details', 'walabu-travel') : __('Summary', 'walabu-travel')); ?></h3>
                <?php if ('' !== $checkout_notice) : ?>
                    <div class="booking-summary__step-success"><?php echo esc_html($checkout_notice); ?></div>
                <?php endif; ?>
                <?php if (!empty($checkout_errors)) : ?>
                    <div class="booking-summary__step-errors">
                        <?php foreach ($checkout_errors as $checkout_error) : ?>
                            <div><?php echo esc_html($checkout_error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="booking-summary__panel-soldby">
                    <span><?php esc_html_e('Sold by', 'walabu-travel'); ?></span>
                    <strong class="booking-summary__panel-soldby-airline">
                        <?php if (!empty($selected_offer['airline_logo_url'])) : ?>
                            <img src="<?php echo esc_url($selected_offer['airline_logo_url']); ?>" alt="<?php echo esc_attr($selected_airline); ?>" loading="lazy">
                        <?php endif; ?>
                        <span><?php echo esc_html($selected_airline); ?></span>
                    </strong>
                </div>

                <div class="booking-summary__panel-section">
                    <div class="booking-summary__panel-row">
                        <span><?php esc_html_e('Changes', 'walabu-travel'); ?></span>
                        <strong><?php echo esc_html($conditions['change']); ?></strong>
                    </div>
                    <div class="booking-summary__panel-row">
                        <span><?php esc_html_e('Refunds', 'walabu-travel'); ?></span>
                        <strong><?php echo esc_html($conditions['refund']); ?></strong>
                    </div>
                    <div class="booking-summary__panel-row">
                        <span><?php esc_html_e('Cabin', 'walabu-travel'); ?></span>
                        <strong><?php echo esc_html($fare_label); ?></strong>
                    </div>
                    <div class="booking-summary__panel-row">
                        <span><?php esc_html_e('Passengers', 'walabu-travel'); ?></span>
                        <strong><?php echo esc_html(sprintf(_n('%d Adult', '%d Adults', (int) ($search_values['passengers'] ?? 1), 'walabu-travel'), (int) ($search_values['passengers'] ?? 1))); ?></strong>
                    </div>
                    <div class="booking-summary__panel-row">
                        <span><?php esc_html_e('Offer ID', 'walabu-travel'); ?></span>
                        <strong><?php echo esc_html((string) ($selected_offer['offer_id'] ?? '')); ?></strong>
                    </div>
                </div>

                <div class="booking-summary__panel-total">
                    <span><?php esc_html_e('total amount', 'walabu-travel'); ?></span>
                    <strong><?php echo esc_html($selected_total_amount); ?></strong>
                </div>

                <?php if ('checkout' === $booking_step) : ?>
                    <form method="post" action="<?php echo esc_url($back_url . (str_contains($back_url, '?') ? '&' : '?') . 'b=checkout'); ?>" class="booking-summary__checkout-form">
                        <input type="hidden" name="walabu_checkout_submit" value="1">
                        <?php for ($passenger_index = 0; $passenger_index < $checkout_passenger_count; $passenger_index++) : ?>
                            <?php $passenger = $checkout_passengers[$passenger_index] ?? array(); ?>
                            <fieldset class="booking-summary__passenger">
                                <legend><?php echo esc_html(sprintf(__('Passenger %d', 'walabu-travel'), $passenger_index + 1)); ?></legend>
                                <div class="booking-summary__field-grid">
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Title', 'walabu-travel'); ?></span>
                                        <select name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][title]">
                                            <?php foreach (array('mr', 'mrs', 'ms', 'mx') as $title_value) : ?>
                                                <option value="<?php echo esc_attr($title_value); ?>" <?php selected($passenger['title'] ?? 'mr', $title_value); ?>>
                                                    <?php echo esc_html(ucfirst($title_value)); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Given name', 'walabu-travel'); ?></span>
                                        <input type="text" name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][given_name]" value="<?php echo esc_attr($passenger['given_name'] ?? ''); ?>" required>
                                    </label>
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Family name', 'walabu-travel'); ?></span>
                                        <input type="text" name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][family_name]" value="<?php echo esc_attr($passenger['family_name'] ?? ''); ?>" required>
                                    </label>
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Email', 'walabu-travel'); ?></span>
                                        <input type="email" name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][email]" value="<?php echo esc_attr($passenger['email'] ?? ''); ?>" required>
                                    </label>
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Phone number', 'walabu-travel'); ?></span>
                                        <input type="tel" name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][phone_number]" value="<?php echo esc_attr($passenger['phone_number'] ?? ''); ?>" required>
                                    </label>
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Date of birth', 'walabu-travel'); ?></span>
                                        <input type="date" name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][born_on]" value="<?php echo esc_attr($passenger['born_on'] ?? ''); ?>" required>
                                    </label>
                                    <label class="booking-summary__field">
                                        <span><?php esc_html_e('Gender', 'walabu-travel'); ?></span>
                                        <select name="checkout_passengers[<?php echo esc_attr((string) $passenger_index); ?>][gender]">
                                            <option value=""><?php esc_html_e('Select', 'walabu-travel'); ?></option>
                                            <option value="m" <?php selected($passenger['gender'] ?? '', 'm'); ?>><?php esc_html_e('Male', 'walabu-travel'); ?></option>
                                            <option value="f" <?php selected($passenger['gender'] ?? '', 'f'); ?>><?php esc_html_e('Female', 'walabu-travel'); ?></option>
                                        </select>
                                    </label>
                                </div>
                            </fieldset>
                        <?php endfor; ?>

                        <button type="submit" class="booking-summary__checkout">
                            <span><?php esc_html_e('Save passenger details', 'walabu-travel'); ?></span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </form>
                    <p class="booking-summary__note"><?php esc_html_e('Passenger details are captured here only. Payment and order creation are not enabled yet.', 'walabu-travel'); ?></p>
                <?php else : ?>
                    <a class="booking-summary__checkout" href="<?php echo esc_url($checkout_url); ?>">
                        <span><?php esc_html_e('Go to checkout', 'walabu-travel'); ?></span>
                        <span aria-hidden="true">→</span>
                    </a>
                    <p class="booking-summary__note"><?php esc_html_e('Checkout is a placeholder until passenger details and payment are wired up.', 'walabu-travel'); ?></p>
                <?php endif; ?>
            </aside>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function walabu_duffel_booking_render_results($offers, $search_values) {
    if (empty($offers)) {
        return '';
    }

    $context = walabu_duffel_booking_build_results_context($offers);
    $view_state = $context['view_state'];
    $selected_offer_id = $context['selected_offer_id'] ?? '';
    $selected_offer_token = walabu_duffel_booking_get_selected_offer_token();
    $selected_offer = walabu_duffel_booking_get_cached_offer_snapshot($search_values, $selected_offer_id, $selected_offer_token);

    if (empty($selected_offer)) {
        $selected_offer = walabu_duffel_booking_find_offer_by_id($offers, $selected_offer_id);
    }

    $booking_step = walabu_duffel_booking_get_booking_step();

    if (('details' === $booking_step || 'checkout' === $booking_step) && !empty($selected_offer)) {
        return walabu_duffel_booking_render_offer_summary($selected_offer, $search_values, $view_state, $booking_step);
    }

    ob_start();
    ?>
    <section class="booking-results" aria-label="<?php esc_attr_e('Flight offers', 'walabu-travel'); ?>">
        <div class="booking-results__layout">
            <aside class="booking-results__sidebar">
                <button type="button" class="booking-results__fare-alerts">
                    <?php esc_html_e('Get fare alerts', 'walabu-travel'); ?>
                </button>

                <div class="booking-results__sidebar-head">
                    <h2 class="booking-results__sidebar-title"><?php esc_html_e('Filter your results', 'walabu-travel'); ?></h2>
                    <p class="booking-results__sidebar-meta">
                        <?php
                        echo esc_html(
                            sprintf(
                                _n('%d result found', '%d results found', $context['filtered_count'], 'walabu-travel'),
                                $context['filtered_count']
                            )
                        );
                        ?>
                    </p>
                    <p class="booking-results__sidebar-debug">
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Duffel returned %1$d offers, showing %2$d after filters.', 'walabu-travel'),
                                $context['raw_count'],
                                $context['displayed_count']
                            )
                        );
                        ?>
                    </p>
                </div>

                <form method="get" action="<?php echo esc_url(walabu_duffel_booking_get_refresh_url()); ?>" class="booking-results-filters__form">
                    <?php foreach (walabu_duffel_booking_get_search_query_args($search_values) as $key => $value) : ?>
                        <?php if (is_array($value)) : ?>
                            <?php foreach ($value as $item) : ?>
                                <input type="hidden" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr((string) $item); ?>">
                            <?php endforeach; ?>
                        <?php else : ?>
                            <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string) $value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <input type="hidden" name="sort_by" value="<?php echo esc_attr($view_state['sort_by']); ?>">

                    <section class="booking-results-filter">
                        <div class="booking-results-filter__heading">
                            <h3><?php esc_html_e('Stops', 'walabu-travel'); ?></h3>
                        </div>
                        <div class="booking-results-filter__options">
                            <?php foreach ($context['stop_options'] as $key => $option) : ?>
                                <?php if (empty($option['count'])) : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <label class="booking-results-filter__option">
                                    <span class="booking-results-filter__check">
                                        <input type="checkbox" name="filter_stops[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $view_state['filter_stops'], true)); ?>>
                                        <span><?php echo esc_html($option['label']); ?></span>
                                    </span>
                                    <span class="booking-results-filter__price"><?php echo esc_html(walabu_duffel_booking_format_money($option['currency'], $option['min_price'])); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <?php if (!empty($context['airline_options'])) : ?>
                        <section class="booking-results-filter">
                            <div class="booking-results-filter__heading">
                                <h3><?php esc_html_e('Airlines', 'walabu-travel'); ?></h3>
                                <?php if (!empty($view_state['filter_airlines'])) : ?>
                                    <a class="booking-results-filter__clear" href="<?php echo esc_url(walabu_duffel_booking_build_results_url($search_values, $view_state, array('filter_airlines' => null))); ?>"><?php esc_html_e('Clear all', 'walabu-travel'); ?></a>
                                <?php endif; ?>
                            </div>
                            <div class="booking-results-filter__options">
                                <?php foreach ($context['airline_options'] as $key => $option) : ?>
                                    <label class="booking-results-filter__option">
                                        <span class="booking-results-filter__check">
                                            <input type="checkbox" name="filter_airlines[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $view_state['filter_airlines'], true)); ?>>
                                            <span><?php echo esc_html($option['label']); ?></span>
                                        </span>
                                        <span class="booking-results-filter__price"><?php echo esc_html(walabu_duffel_booking_format_money($option['currency'], $option['min_price'])); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </form>
            </aside>

            <div class="booking-results__main">
                <div class="booking-results__summary">
                    <?php foreach ($context['tabs'] as $tab_key => $tab) : ?>
                        <a
                            class="booking-results__summary-tab <?php echo $view_state['sort_by'] === $tab_key ? 'is-active' : ''; ?>"
                            href="<?php echo esc_url(walabu_duffel_booking_build_results_url($search_values, $view_state, array('sort_by' => $tab_key))); ?>"
                        >
                            <span class="booking-results__summary-label"><?php echo esc_html($tab['label']); ?></span>
                            <strong class="booking-results__summary-price">
                                <?php echo esc_html(!empty($tab['offer']['price']) ? $tab['offer']['price'] : __('Unavailable', 'walabu-travel')); ?>
                            </strong>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($selected_offer)) : ?>
                    <div class="booking-results__selected">
                        <div class="booking-results__selected-label"><?php esc_html_e('Selected flight', 'walabu-travel'); ?></div>
                        <div class="booking-results__selected-route">
                            <?php echo esc_html(trim(($selected_offer['airline_display_name'] ?? $selected_offer['airline_name'] ?? __('Unknown airline', 'walabu-travel')) . ' • ' . ($selected_offer['price'] ?? __('Unavailable', 'walabu-travel')))); ?>
                        </div>
                        <div class="booking-results__selected-meta">
                            <?php echo esc_html(implode(' • ', array_filter(array(
                                !empty($selected_offer['offer_id']) ? $selected_offer['offer_id'] : '',
                                !empty($selected_offer['slices'][0]['departure_full']) ? $selected_offer['slices'][0]['departure_full'] : '',
                                !empty($selected_offer['slices'][0]['arrival_full']) ? $selected_offer['slices'][0]['arrival_full'] : '',
                            )))); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($context['offers'])) : ?>
                    <div class="booking-results__empty">
                        <h3><?php esc_html_e('No offers match your filters', 'walabu-travel'); ?></h3>
                        <p><?php esc_html_e('Try removing some stop or airline filters to see more flights.', 'walabu-travel'); ?></p>
                        <a class="booking-results__reset" href="<?php echo esc_url(walabu_duffel_booking_build_results_url($search_values, $view_state, array('filter_stops' => null, 'filter_airlines' => null))); ?>">
                            <?php esc_html_e('Reset filters', 'walabu-travel'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="booking-results__cards">
                        <?php foreach ($context['offers'] as $index => $offer) : ?>
                            <?php
                            $primary_carrier = !empty($offer['airline_names'][0]) ? $offer['airline_names'][0] : $offer['airline_name'];
                            $primary_carrier = trim((string) $primary_carrier);
                            $select_url = walabu_duffel_booking_build_results_url(
                                $search_values,
                                $view_state,
                                array(
                                    'o' => !empty($offer['offer_id']) ? $offer['offer_id'] : null,
                                    't' => walabu_duffel_booking_cache_offer_snapshot($search_values, $offer),
                                    'b' => 'details',
                                ),
                                walabu_duffel_booking_get_booking_page_url()
                            );
                            ?>
                            <article
                                class="booking-result-card <?php echo 0 === $index ? 'is-featured' : ''; ?> <?php echo (!empty($offer['offer_id']) && $offer['offer_id'] === $selected_offer_id) ? 'is-selected' : ''; ?> <?php echo $index >= WALABU_DUFFEL_BOOKING_RESULTS_LIMIT ? 'is-hidden-by-default' : ''; ?>"
                                data-offer-id="<?php echo esc_attr($offer['offer_id']); ?>"
                                <?php echo $index >= WALABU_DUFFEL_BOOKING_RESULTS_LIMIT ? 'hidden' : ''; ?>
                            >
                                <?php if (0 === $index) : ?>
                                    <div class="booking-result-card__flag">
                                        <?php echo 'best' === $view_state['sort_by'] ? esc_html__('Best match', 'walabu-travel') : esc_html__('Top option', 'walabu-travel'); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="booking-result-card__body">
                                    <div class="booking-result-card__itinerary">
                                        <?php foreach ($offer['slices'] as $slice_index => $slice) : ?>
                                            <div class="booking-result-card__slice">
                                                <div class="booking-result-card__brand">
                                                    <span class="booking-result-card__badge <?php echo !empty($offer['airline_logo_url']) ? 'has-logo' : 'has-initials'; ?>">
                                                        <?php if (!empty($offer['airline_logo_url'])) : ?>
                                                            <img src="<?php echo esc_url($offer['airline_logo_url']); ?>" alt="<?php echo esc_attr($primary_carrier); ?>" loading="lazy">
                                                        <?php else : ?>
                                                            <?php echo esc_html(strtoupper(substr(preg_replace('/[^A-Z]/', '', $primary_carrier), 0, 2)) ?: 'FL'); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>

                                                <div class="booking-result-card__timeline">
                                                    <div class="booking-result-card__times">
                                                        <span class="booking-result-card__timeblock">
                                                            <strong><?php echo esc_html($slice['departure_time']); ?></strong>
                                                            <span class="booking-result-card__datetime"><?php echo esc_html($slice['departure_full']); ?></span>
                                                            <?php if (!empty($slice['departure_timezone'])) : ?>
                                                                <span class="booking-result-card__timezone"><?php echo esc_html($slice['departure_timezone']); ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="booking-result-card__times-separator">&mdash;</span>
                                                        <span class="booking-result-card__timeblock">
                                                            <strong>
                                                                <?php echo esc_html($slice['arrival_time']); ?>
                                                                <?php if ('' !== $slice['arrival_offset']) : ?>
                                                                    <sup><?php echo esc_html($slice['arrival_offset']); ?></sup>
                                                                <?php endif; ?>
                                                            </strong>
                                                            <span class="booking-result-card__datetime"><?php echo esc_html($slice['arrival_full']); ?></span>
                                                            <?php if (!empty($slice['arrival_timezone'])) : ?>
                                                                <span class="booking-result-card__timezone"><?php echo esc_html($slice['arrival_timezone']); ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="booking-result-card__route"><?php echo esc_html(trim($slice['origin_code'] . ' - ' . $slice['destination_code'])); ?></div>
                                                    <div class="booking-result-card__carriers"><?php echo esc_html($slice['carriers_text']); ?></div>
                                                </div>

                                                <div class="booking-result-card__stops">
                                                    <strong><?php echo esc_html($slice['stops']); ?></strong>
                                                    <?php if ('' !== $slice['layovers']) : ?>
                                                        <span><?php echo esc_html($slice['layovers']); ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="booking-result-card__duration"><?php echo esc_html($slice['duration']); ?></div>
                                            </div>
                                            <?php if ($slice_index < (count($offer['slices']) - 1)) : ?>
                                                <div class="booking-result-card__slice-divider"></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                        <details class="booking-result-card__details">
                                            <summary><?php esc_html_e('Show flight details', 'walabu-travel'); ?></summary>
                                            <div class="booking-result-card__details-grid">
                                                <div class="booking-result-card__details-item">
                                                    <span><?php esc_html_e('Offer ID', 'walabu-travel'); ?></span>
                                                    <code><?php echo esc_html($offer['offer_id']); ?></code>
                                                </div>
                                                <div class="booking-result-card__details-item">
                                                    <span><?php esc_html_e('Changes', 'walabu-travel'); ?></span>
                                                    <strong><?php echo esc_html($offer['change_summary']); ?></strong>
                                                </div>
                                                <div class="booking-result-card__details-item">
                                                    <span><?php esc_html_e('Refunds', 'walabu-travel'); ?></span>
                                                    <strong><?php echo esc_html($offer['refund_summary']); ?></strong>
                                                </div>
                                                <?php if (!empty($offer['private_fares'])) : ?>
                                                    <div class="booking-result-card__details-item">
                                                        <span><?php esc_html_e('Fare type', 'walabu-travel'); ?></span>
                                                        <strong><?php esc_html_e('Private fare applied', 'walabu-travel'); ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($offer['slice_change_note'])) : ?>
                                                <p class="booking-result-card__details-note"><?php echo esc_html($offer['slice_change_note']); ?></p>
                                            <?php endif; ?>
                                        </details>
                                    </div>

                                    <aside class="booking-result-card__pricepane">
                                        <div class="booking-result-card__price"><?php echo esc_html($offer['price']); ?></div>
                                        <div class="booking-result-card__price-note">
                                            <?php
                                            echo esc_html(
                                                sprintf(
                                                    __('Approx. %s per traveler', 'walabu-travel'),
                                                    walabu_duffel_booking_format_money($offer['price_currency'], $offer['price_per_passenger'])
                                                )
                                            );
                                            ?>
                                        </div>
                                        <button
                                            type="button"
                                            class="booking-result-card__select"
                                            data-select-url="<?php echo esc_url($select_url); ?>"
                                            <?php echo (!empty($offer['offer_id']) && $offer['offer_id'] === $selected_offer_id) ? 'disabled aria-disabled="true"' : ''; ?>
                                        >
                                            <?php echo (!empty($offer['offer_id']) && $offer['offer_id'] === $selected_offer_id) ? esc_html__('Selected', 'walabu-travel') : esc_html__('Select', 'walabu-travel'); ?>
                                        </button>
                                        <div class="booking-result-card__offer-id"><?php echo esc_html($offer['offer_id']); ?></div>
                                    </aside>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($context['offers']) > WALABU_DUFFEL_BOOKING_RESULTS_LIMIT) : ?>
                        <div class="booking-results__load-more-wrap">
                            <button type="button" class="booking-results__load-more" data-load-more>
                                <?php echo esc_html(sprintf(__('Load more flights (%d more)', 'walabu-travel'), count($context['offers']) - WALABU_DUFFEL_BOOKING_RESULTS_LIMIT)); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function walabu_duffel_booking_form($atts = array()) {
    $atts = shortcode_atts(array(
        'variant' => 'default',
    ), $atts, 'walabu_flight_search');
    $search_result = walabu_duffel_booking_get_search_result();
    $values = $search_result['state']['values'];
    $cabin_classes = walabu_duffel_booking_allowed_cabin_classes();
    $max_connection_options = walabu_duffel_booking_allowed_max_connections();

    ob_start();
    ?>
    <div class="booking-widget booking-widget--<?php echo esc_attr($atts['variant']); ?>">
        <?php echo walabu_duffel_booking_render_notice($search_result['notice']); ?>

        <div class="booking-widget__panel">
            <ul class="booking-widget__service-tabs" aria-label="<?php esc_attr_e('Travel services', 'walabu-travel'); ?>">
                <li class="booking-widget__service-tab is-active"><?php echo walabu_duffel_booking_icon('plane'); ?> <?php esc_html_e('Flights', 'walabu-travel'); ?></li>
                <li class="booking-widget__service-tab"><?php echo walabu_duffel_booking_icon('briefcase'); ?> <?php esc_html_e('Flights + Hotel', 'walabu-travel'); ?> <span class="booking-widget__service-badge"><?php esc_html_e('Save more', 'walabu-travel'); ?></span></li>
                <li class="booking-widget__service-tab"><?php esc_html_e('Hotels', 'walabu-travel'); ?></li>
                <li class="booking-widget__service-tab"><?php esc_html_e('Cars', 'walabu-travel'); ?></li>
                <li class="booking-widget__service-tab"><?php esc_html_e('Cruises', 'walabu-travel'); ?></li>
            </ul>

            <div class="booking-widget__form-shell">
                <form method="get" action="<?php echo esc_url(walabu_duffel_booking_get_current_request_url()); ?>" class="booking-widget__form" novalidate>
                    <div class="booking-widget__topline">
                        <div class="booking-widget__trip-types" role="group" aria-label="<?php esc_attr_e('Trip type', 'walabu-travel'); ?>">
                            <button class="booking-widget__trip-button <?php echo 'round_trip' === $values['trip_type'] ? 'is-active' : ''; ?>" type="button" data-trip-type="round_trip"><?php esc_html_e('Round trip', 'walabu-travel'); ?></button>
                            <button class="booking-widget__trip-button <?php echo 'one_way' === $values['trip_type'] ? 'is-active' : ''; ?>" type="button" data-trip-type="one_way"><?php esc_html_e('One way', 'walabu-travel'); ?></button>
                            <button class="booking-widget__trip-button <?php echo 'multi_city' === $values['trip_type'] ? 'is-active' : ''; ?>" type="button" data-trip-type="multi_city"><?php esc_html_e('Multi-city', 'walabu-travel'); ?></button>
                        </div>

                        <div class="booking-widget__utility-row">
                            <label class="booking-widget__utility" for="walabu-cabin-class">
                                <select id="walabu-cabin-class" name="cabin_class">
                                    <?php foreach ($cabin_classes as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($values['cabin_class'], $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="booking-widget__utility" for="walabu-max-connections">
                                <select id="walabu-max-connections" name="max_connections">
                                    <?php foreach ($max_connection_options as $value => $label) : ?>
                                        <option value="<?php echo esc_attr((string) $value); ?>" <?php selected((string) $values['max_connections'], (string) $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="booking-widget__utility booking-widget__utility--input" for="walabu-fare-type">
                                <input id="walabu-fare-type" type="text" name="fare_type" value="<?php echo esc_attr($values['fare_type']); ?>" placeholder="<?php esc_attr_e('Private fare type', 'walabu-travel'); ?>">
                            </label>

                            <label class="booking-widget__bundle" for="walabu-bundle-hotel">
                                <input id="walabu-bundle-hotel" type="checkbox" value="1">
                                <span><?php esc_html_e('Bundle with a hotel', 'walabu-travel'); ?></span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="trip_type" value="<?php echo esc_attr($values['trip_type']); ?>" data-trip-type-input>
                    <input type="hidden" name="walabu_flight_search_submit" value="1">

                    <div class="booking-widget__grid">
                        <label class="booking-field booking-field--autocomplete" for="walabu-origin-label">
                            <span class="booking-field__icon"><?php echo walabu_duffel_booking_icon('plane'); ?></span>
                            <span class="booking-field__meta">
                                <span class="booking-field__label"><?php esc_html_e('Leaving from', 'walabu-travel'); ?></span>
                                <input id="walabu-origin-label" type="text" name="origin_label" value="<?php echo esc_attr($values['origin_label'] ?: $values['origin']); ?>" placeholder="City or airport" autocomplete="off" data-place-input="origin" required>
                                <input id="walabu-origin" type="hidden" name="origin" value="<?php echo esc_attr($values['origin']); ?>" data-place-code="origin">
                                <button type="button" class="booking-field__nearby-button" data-nearby-button="origin"><?php esc_html_e('Nearby airports', 'walabu-travel'); ?></button>
                                <div class="booking-field__suggestions" data-suggestions="origin" hidden></div>
                            </span>
                        </label>

                        <button class="booking-widget__swap" type="button" aria-label="<?php esc_attr_e('Swap origin and destination', 'walabu-travel'); ?>">
                            <?php echo walabu_duffel_booking_icon('swap'); ?>
                        </button>

                        <label class="booking-field booking-field--autocomplete" for="walabu-destination-label">
                            <span class="booking-field__icon"><?php echo walabu_duffel_booking_icon('plane'); ?></span>
                            <span class="booking-field__meta">
                                <span class="booking-field__label"><?php esc_html_e('Going to', 'walabu-travel'); ?></span>
                                <input id="walabu-destination-label" type="text" name="destination_label" value="<?php echo esc_attr($values['destination_label'] ?: $values['destination']); ?>" placeholder="City or airport" autocomplete="off" data-place-input="destination" required>
                                <input id="walabu-destination" type="hidden" name="destination" value="<?php echo esc_attr($values['destination']); ?>" data-place-code="destination">
                                <button type="button" class="booking-field__nearby-button" data-nearby-button="destination"><?php esc_html_e('Nearby airports', 'walabu-travel'); ?></button>
                                <div class="booking-field__suggestions" data-suggestions="destination" hidden></div>
                            </span>
                        </label>

                        <div class="booking-field booking-field--dates booking-field--date-range">
                            <span class="booking-field__icon"><?php echo walabu_duffel_booking_icon('calendar'); ?></span>
                            <div class="booking-field__date-split">
                                <label class="booking-field__date-item" for="walabu-departure-date">
                                    <span class="booking-field__label"><?php esc_html_e('Departing', 'walabu-travel'); ?></span>
                                    <input id="walabu-departure-date" type="date" name="departure_date" min="<?php echo esc_attr(wp_date('Y-m-d')); ?>" value="<?php echo esc_attr($values['departure_date']); ?>" required>
                                </label>
                                <div class="booking-field__date-divider <?php echo 'one_way' === $values['trip_type'] ? 'is-hidden' : ''; ?>" aria-hidden="true" data-return-divider>&#8594;</div>
                                <label class="booking-field__date-item <?php echo 'one_way' === $values['trip_type'] ? 'is-hidden' : ''; ?>" for="walabu-return-date" data-return-wrap>
                                    <span class="booking-field__label" data-return-label><?php echo 'multi_city' === $values['trip_type'] ? esc_html__('Second leg date', 'walabu-travel') : esc_html__('Returning', 'walabu-travel'); ?></span>
                                    <input id="walabu-return-date" type="date" name="return_date" min="<?php echo esc_attr($values['departure_date']); ?>" value="<?php echo esc_attr($values['return_date']); ?>" <?php echo 'one_way' === $values['trip_type'] ? 'disabled' : ''; ?> <?php echo 'one_way' === $values['trip_type'] ? '' : 'required'; ?> data-return-date-input>
                                </label>
                            </div>
                        </div>

                        <label class="booking-field booking-field--compact booking-field--passengers" for="walabu-passengers">
                            <span class="booking-field__icon"><?php echo walabu_duffel_booking_icon('users'); ?></span>
                            <span class="booking-field__meta">
                                <span class="booking-field__label"><?php esc_html_e('Passenger(s)', 'walabu-travel'); ?></span>
                                <select id="walabu-passengers" name="passengers">
                                    <?php for ($i = 1; $i <= 9; $i++) : ?>
                                        <option value="<?php echo esc_attr((string) $i); ?>" <?php selected((int) $values['passengers'], $i); ?>>
                                            <?php echo esc_html(sprintf(_n('%d Adult', '%d Adults', $i, 'walabu-travel'), $i)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </span>
                        </label>

                        <button class="booking-widget__submit" type="submit">
                            <?php esc_html_e('Search', 'walabu-travel'); ?>
                        </button>
                    </div>

                    <div class="booking-widget__help">
                        <span class="booking-pill"><?php esc_html_e('Live Duffel offers', 'walabu-travel'); ?></span>
                        <span class="booking-pill"><?php esc_html_e('Refresh-safe search URL', 'walabu-travel'); ?></span>
                        <span class="booking-pill"><?php esc_html_e('No payment yet', 'walabu-travel'); ?></span>
                    </div>
                </form>
            </div>
        </div>

        <?php echo walabu_duffel_booking_render_results($search_result['offers'], $values); ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('walabu_flight_search', 'walabu_duffel_booking_form');
