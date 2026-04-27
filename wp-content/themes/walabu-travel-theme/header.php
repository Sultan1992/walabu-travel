<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_front_page   = is_front_page()
    && '' === (string) get_query_var('walabu_legal_page')
    && '' === (string) get_query_var('walabu_site_page');
$header_variant  = $is_front_page ? 'site-masthead--floating' : 'site-masthead--solid';
$account_label   = __('Sign in', 'walabu-travel');
$language_options = array(
    array(
        'code'  => 'en',
        'label' => __('English', 'walabu-travel'),
    ),
    array(
        'code'  => 'am',
        'label' => __('Amharic', 'walabu-travel'),
    ),
    array(
        'code'  => 'fr',
        'label' => __('French', 'walabu-travel'),
    ),
    array(
        'code'  => 'ar',
        'label' => __('Arabic', 'walabu-travel'),
    ),
);
$region_options = array(
    array(
        'code'     => 'US',
        'label'    => __('United States', 'walabu-travel'),
        'flag'     => '🇺🇸',
        'currency' => 'USD',
    ),
    array(
        'code'     => 'CA',
        'label'    => __('Canada', 'walabu-travel'),
        'flag'     => '🇨🇦',
        'currency' => 'CAD',
    ),
    array(
        'code'     => 'GB',
        'label'    => __('United Kingdom', 'walabu-travel'),
        'flag'     => '🇬🇧',
        'currency' => 'GBP',
    ),
    array(
        'code'     => 'FR',
        'label'    => __('France', 'walabu-travel'),
        'flag'     => '🇫🇷',
        'currency' => 'EUR',
    ),
    array(
        'code'     => 'IE',
        'label'    => __('Ireland', 'walabu-travel'),
        'flag'     => '🇮🇪',
        'currency' => 'EUR',
    ),
    array(
        'code'     => 'ET',
        'label'    => __('Ethiopia', 'walabu-travel'),
        'flag'     => '🇪🇹',
        'currency' => 'ETB',
    ),
    array(
        'code'     => 'KE',
        'label'    => __('Kenya', 'walabu-travel'),
        'flag'     => '🇰🇪',
        'currency' => 'KES',
    ),
    array(
        'code'     => 'SA',
        'label'    => __('Saudi Arabia', 'walabu-travel'),
        'flag'     => '🇸🇦',
        'currency' => 'SAR',
    ),
    array(
        'code'     => 'AE',
        'label'    => __('United Arab Emirates', 'walabu-travel'),
        'flag'     => '🇦🇪',
        'currency' => 'AED',
    ),
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site-shell">
    <header class="site-masthead <?php echo esc_attr($header_variant); ?>">
        <div class="walabu-container site-masthead__inner">
            <div class="site-brand-wrap">
                <?php echo walabu_travel_get_brand_logo_markup('header'); ?>
            </div>

            <nav class="site-nav" aria-label="<?php esc_attr_e('Primary navigation', 'walabu-travel'); ?>">
                <?php
                if (has_nav_menu('primary')) {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'items_wrap'     => '%3$s',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ));
                } else {
                    walabu_travel_fallback_menu();
                }
                ?>
            </nav>

            <div class="site-actions">
                <button
                    class="site-currency"
                    type="button"
                    data-currency-trigger
                    aria-expanded="false"
                    aria-controls="site-preferences-panel"
                >
                    <span class="site-currency__value">USD</span>
                </button>
                <button
                    class="site-user"
                    type="button"
                    data-auth-trigger
                    aria-expanded="false"
                    aria-controls="site-auth-panel"
                >
                    <span data-auth-label><?php echo esc_html($account_label); ?></span>
                </button>
            </div>
        </div>
    </header>

    <div class="site-preferences is-hidden" data-preferences-panel>
        <div class="site-preferences__backdrop" data-preferences-close></div>
        <section
            id="site-preferences-panel"
            class="site-preferences__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="site-preferences-title"
        >
            <div class="site-preferences__header">
                <h2 id="site-preferences-title" class="site-preferences__title"><?php esc_html_e('Language and Currency', 'walabu-travel'); ?></h2>
                <button class="site-preferences__dismiss" type="button" data-preferences-close aria-label="<?php esc_attr_e('Close preferences', 'walabu-travel'); ?>">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6 18 18M18 6 6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="site-preferences__section">
                <label class="site-preferences__label"><?php esc_html_e('Language', 'walabu-travel'); ?></label>
                <div class="site-preferences__select" data-select-root>
                    <button
                        class="site-preferences__select-trigger"
                        type="button"
                        data-select-trigger
                        aria-expanded="false"
                    >
                        <span class="site-preferences__option-icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M5 5h8m-4 0c0 5.2-2.6 10-6 13m6-13c1.2 4 3.4 7.5 6 10m-8 3h10m-8-4h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="site-preferences__select-text" data-select-text><?php esc_html_e('English', 'walabu-travel'); ?></span>
                        <span class="site-preferences__chevron" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="m4 7 6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="site-preferences__menu is-hidden" data-select-menu>
                        <?php foreach ($language_options as $index => $language) : ?>
                            <button
                                class="site-preferences__menu-item <?php echo 0 === $index ? 'is-selected' : ''; ?>"
                                type="button"
                                data-value="<?php echo esc_attr($language['code']); ?>"
                                data-label="<?php echo esc_attr($language['label']); ?>"
                            >
                                <span><?php echo esc_html($language['label']); ?></span>
                                <span class="site-preferences__check" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                        <path d="m3.5 9.2 3.2 3.3 7.8-8.1" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="site-preferences__section">
                <label class="site-preferences__label"><?php esc_html_e('Country / Region', 'walabu-travel'); ?></label>
                <div class="site-preferences__select" data-select-root>
                    <button
                        class="site-preferences__select-trigger"
                        type="button"
                        data-select-trigger
                        aria-expanded="false"
                    >
                        <span class="site-preferences__option-flag" data-region-flag aria-hidden="true">🇺🇸</span>
                        <span class="site-preferences__select-text" data-select-text><?php esc_html_e('United States', 'walabu-travel'); ?></span>
                        <span class="site-preferences__chevron" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="m4 7 6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <div class="site-preferences__menu site-preferences__menu--region is-hidden" data-select-menu>
                        <?php foreach ($region_options as $index => $region) : ?>
                            <button
                                class="site-preferences__menu-item <?php echo 0 === $index ? 'is-selected' : ''; ?>"
                                type="button"
                                data-value="<?php echo esc_attr($region['code']); ?>"
                                data-label="<?php echo esc_attr($region['label']); ?>"
                                data-currency="<?php echo esc_attr($region['currency']); ?>"
                                data-flag="<?php echo esc_attr($region['flag']); ?>"
                            >
                                <span class="site-preferences__menu-flag" aria-hidden="true"><?php echo esc_html($region['flag']); ?></span>
                                <span><?php echo esc_html($region['label']); ?></span>
                                <span class="site-preferences__check" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                        <path d="m3.5 9.2 3.2 3.3 7.8-8.1" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="site-auth is-hidden" data-auth-panel>
        <div class="site-auth__backdrop" data-auth-close></div>
        <section
            id="site-auth-panel"
            class="site-auth__dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="site-auth-title"
        >
            <div class="site-auth__header">
                <h2 id="site-auth-title" class="site-auth__title">Sign in or register!</h2>
                <button class="site-auth__dismiss" type="button" data-auth-close aria-label="<?php esc_attr_e('Close sign in', 'walabu-travel'); ?>">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6 18 18M18 6 6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="site-auth__screen" data-auth-screen="providers">
                <p class="site-auth__intro">Make sure to use the same email you used for booking</p>

                <div class="site-auth__providers">
                    <button class="site-auth__provider" type="button" data-auth-provider="Email">
                        <span class="site-auth__provider-icon" aria-hidden="true">
                            <svg width="34" height="34" viewBox="0 0 34 34" fill="none">
                                <rect x="4.5" y="7.5" width="25" height="19" rx="3.5" stroke="currentColor" stroke-width="3"/>
                                <path d="m7 10 8.5 6.4a2.8 2.8 0 0 0 3 0L27 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="site-auth__provider-copy">
                            <strong>Email</strong>
                        </span>
                        <span class="site-auth__provider-arrow" aria-hidden="true">
                            <svg width="16" height="28" viewBox="0 0 16 28" fill="none">
                                <path d="m3 3 10 11-10 11" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>

                    <button class="site-auth__provider" type="button" data-auth-provider="Google">
                        <span class="site-auth__provider-icon site-auth__provider-icon--google" aria-hidden="true">G</span>
                        <span class="site-auth__provider-copy">
                            <strong>Google</strong>
                        </span>
                        <span class="site-auth__provider-arrow" aria-hidden="true">
                            <svg width="16" height="28" viewBox="0 0 16 28" fill="none">
                                <path d="m3 3 10 11-10 11" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>

                    <button class="site-auth__provider" type="button" data-auth-provider="Apple">
                        <span class="site-auth__provider-icon" aria-hidden="true">
                            <svg width="34" height="34" viewBox="0 0 34 34" fill="currentColor">
                                <path d="M22.7 17.9c0-3 2.4-4.5 2.5-4.5-1.4-2-3.5-2.3-4.2-2.3-1.8-.2-3.5 1.1-4.4 1.1-.9 0-2.3-1.1-3.8-1-2 .1-3.8 1.1-4.8 2.9-2.1 3.6-.5 9 1.5 11.9 1 1.4 2.1 3 3.6 2.9 1.4-.1 2-.9 3.8-.9s2.3.9 3.8.9c1.6 0 2.5-1.4 3.5-2.9 1.1-1.6 1.6-3.2 1.6-3.3-.1 0-3.1-1.2-3.1-4.8Zm-3-8.9c.8-1 1.4-2.3 1.2-3.7-1.2 0-2.6.8-3.5 1.8-.8.9-1.5 2.3-1.3 3.6 1.3.1 2.7-.7 3.6-1.7Z"/>
                            </svg>
                        </span>
                        <span class="site-auth__provider-copy">
                            <strong>Apple</strong>
                        </span>
                        <span class="site-auth__provider-arrow" aria-hidden="true">
                            <svg width="16" height="28" viewBox="0 0 16 28" fill="none">
                                <path d="m3 3 10 11-10 11" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <div class="site-auth__screen is-hidden" data-auth-screen="form">
                <button class="site-auth__back" type="button" data-auth-back>Back</button>
                <p class="site-auth__chip" data-auth-provider-label>Email</p>
                <form class="site-auth__form" data-auth-form>
                    <label class="site-auth__field">
                        <span>Full name</span>
                        <input type="text" name="display_name" autocomplete="name" required>
                    </label>
                    <label class="site-auth__field">
                        <span>Email address</span>
                        <input type="email" name="email" autocomplete="email" required>
                    </label>
                    <button class="site-auth__submit" type="submit">Continue</button>
                </form>
            </div>

            <div class="site-auth__screen is-hidden" data-auth-screen="profile">
                <p class="site-auth__signed-in-label">Signed in as</p>
                <h3 class="site-auth__signed-in-name" data-auth-name>Traveler</h3>
                <p class="site-auth__signed-in-email" data-auth-email></p>
                <div class="site-auth__profile-actions">
                    <button class="site-auth__submit" type="button" data-auth-close>Continue</button>
                    <button class="site-auth__signout" type="button" data-auth-signout>Sign out</button>
                </div>
            </div>
        </section>
    </div>
