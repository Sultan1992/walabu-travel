<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$upcoming_trips = array(
    array(
        'title'          => 'My Trip to Addis Ababa (ADD)',
        'travel_window'  => 'Mon 23 Feb, 2026 - Tue 23 Jun, 2026',
        'reference'      => '293-311-201',
        'status'         => 'Confirmed',
    ),
    array(
        'title'          => 'My Trip to Addis Ababa (ADD)',
        'travel_window'  => 'Sat 21 Mar, 2026 - Wed 10 Jun, 2026',
        'reference'      => '295-309-342',
        'status'         => 'Confirmed',
    ),
);

$past_trips = array(
    array(
        'title'          => 'My Trip to Addis Ababa (ADD)',
        'travel_window'  => 'Sat 04 Apr, 2026 - Sat 09 May, 2026',
        'reference'      => '294-529-451',
        'status'         => 'Confirmed',
    ),
);
?>
<main class="content-shell my-trips-page">
    <div class="walabu-container">
        <section class="my-trips-hero" data-auth-required-signedout>
            <p class="my-trips-hero__eyebrow">My Trips</p>
            <h1 class="my-trips-hero__title">View your trip history after sign in.</h1>
            <p class="my-trips-hero__text">
                Sign in with the same email you used for booking to unlock your upcoming trips,
                saved itineraries, and previous reservations.
            </p>
        </section>

        <section class="my-trips-gate" data-auth-required-signedout>
            <div class="my-trips-gate__card">
                <h2 class="my-trips-gate__title">Sign in to view trip history</h2>
                <p class="my-trips-gate__text">
                    Your travel history is only available after login. Use Email, Google, or Apple
                    with the same address used for your booking.
                </p>
                <button class="my-trips-gate__button" type="button" data-auth-open>Sign in to continue</button>
            </div>
        </section>

        <section class="my-trips-dashboard is-hidden" data-auth-required-signedin>
            <div class="my-trips-shell">
                <aside class="my-trips-sidebar">
                    <div class="my-trips-sidebar__header">
                        <p class="my-trips-sidebar__eyebrow">Signed in</p>
                        <h2 class="my-trips-sidebar__name" data-auth-account-name>Traveler</h2>
                        <p class="my-trips-sidebar__email" data-auth-account-email></p>
                    </div>

                    <nav class="my-trips-sidebar__nav" aria-label="My trips menu">
                        <button class="my-trips-sidebar__item is-active" type="button">
                            <span aria-hidden="true">✈</span>
                            <span>My Bookings</span>
                        </button>
                        <button class="my-trips-sidebar__item" type="button">
                            <span aria-hidden="true">↺</span>
                            <span>Search History</span>
                        </button>
                        <button class="my-trips-sidebar__item" type="button">
                            <span aria-hidden="true">✉</span>
                            <span>Alerts & Notifications</span>
                        </button>
                        <button class="my-trips-sidebar__item" type="button">
                            <span aria-hidden="true">⚙</span>
                            <span>Settings</span>
                        </button>
                        <button class="my-trips-sidebar__item" type="button" data-auth-signout-inline>
                            <span aria-hidden="true">⇥</span>
                            <span>Logout</span>
                        </button>
                    </nav>

                    <div class="my-trips-sidebar__promo">
                        <h3>DOWNLOAD OUR FREE APP</h3>
                        <p>Get your booking information and stay updated on the go!</p>
                    </div>
                </aside>

                <div class="my-trips-main">
                    <div class="my-trips-tabs" role="tablist" aria-label="Trip history">
                        <button class="my-trips-tab is-active" type="button" role="tab" aria-selected="true" data-trip-tab="upcoming">UPCOMING TRIPS</button>
                        <button class="my-trips-tab" type="button" role="tab" aria-selected="false" data-trip-tab="past">PAST TRIPS</button>
                    </div>

                    <div class="my-trips-panel" data-trip-panel="upcoming">
                        <?php foreach ($upcoming_trips as $trip) : ?>
                            <article class="my-trips-booking">
                                <h3 class="my-trips-booking__title"><?php echo esc_html($trip['title']); ?></h3>
                                <div class="my-trips-booking__row">
                                    <div class="my-trips-booking__summary">
                                        <div class="my-trips-booking__plane" aria-hidden="true">✈</div>
                                        <div class="my-trips-booking__info">
                                            <p><?php echo esc_html($trip['travel_window']); ?></p>
                                            <p>Reference Number: <strong><?php echo esc_html($trip['reference']); ?></strong></p>
                                            <p>Booking Status: <strong><?php echo esc_html($trip['status']); ?></strong></p>
                                        </div>
                                    </div>
                                    <div class="my-trips-booking__actions">
                                        <a href="<?php echo esc_url(walabu_travel_get_support_url()); ?>">ADD A CAR</a>
                                        <a href="<?php echo esc_url(walabu_travel_get_support_url()); ?>">BOOK A HOTEL</a>
                                        <a class="is-primary" href="<?php echo esc_url(walabu_travel_get_support_url()); ?>">VIEW MY BOOKING</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="my-trips-panel is-hidden" data-trip-panel="past">
                        <?php foreach ($past_trips as $trip) : ?>
                            <article class="my-trips-booking">
                                <h3 class="my-trips-booking__title"><?php echo esc_html($trip['title']); ?></h3>
                                <div class="my-trips-booking__row">
                                    <div class="my-trips-booking__summary">
                                        <div class="my-trips-booking__plane" aria-hidden="true">✈</div>
                                        <div class="my-trips-booking__info">
                                            <p><?php echo esc_html($trip['travel_window']); ?></p>
                                            <p>Reference Number: <strong><?php echo esc_html($trip['reference']); ?></strong></p>
                                            <p>Booking Status: <strong><?php echo esc_html($trip['status']); ?></strong></p>
                                        </div>
                                    </div>
                                    <div class="my-trips-booking__actions">
                                        <a href="<?php echo esc_url(walabu_travel_get_support_url()); ?>">ADD A CAR</a>
                                        <a href="<?php echo esc_url(walabu_travel_get_support_url()); ?>">BOOK A HOTEL</a>
                                        <a class="is-primary" href="<?php echo esc_url(walabu_travel_get_support_url()); ?>">VIEW MY BOOKING</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<?php get_footer(); ?>
