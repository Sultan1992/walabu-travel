<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$recent_searches = array(
    array(
        'route' => 'Minneapolis MSP to Addis Ababa ADD',
        'date'  => 'Apr 30 - Aug 05',
        'meta'  => 'Round trip for 2 adults',
    ),
    array(
        'route' => 'Chicago ORD to Dubai DXB',
        'date'  => 'May 12 - May 28',
        'meta'  => 'Premium economy bundle',
    ),
    array(
        'route' => 'Houston IAH to Lagos LOS',
        'date'  => 'Jun 08',
        'meta'  => 'One way with 1 checked bag',
    ),
    array(
        'route' => 'Seattle SEA to Nairobi NBO',
        'date'  => 'Jul 02 - Jul 19',
        'meta'  => 'Family trip for 3 travelers',
    ),
);
?>
<main class="travel-home">
    <section class="hero">
        <div class="walabu-container hero__layout">
            <div class="hero__copy">
                <div class="hero__brand-chip">
                    <?php echo walabu_travel_get_brand_logo_markup('hero'); ?>
                    <span class="hero__brand-copy">
                        <strong><?php esc_html_e('Travel. Explore. Connect.', 'walabu-travel'); ?></strong>
                        <span><?php esc_html_e('Your journey, our passion.', 'walabu-travel'); ?></span>
                    </span>
                </div>
                <span class="hero__eyebrow">Global routes with Walabu precision</span>
                <h1 class="hero__title">Book international trips with a travel brand built around connection.</h1>
                <p class="hero__text">
                    Walabu Travel is designed for long-haul family visits, reunion trips, and cross-continental planning with clearer search, better bundling, and concierge-style support.
                </p>

                <div class="hero__metrics">
                    <div class="hero__metric">
                        <strong>400+</strong>
                        <span>airlines ready to compare</span>
                    </div>
                    <div class="hero__metric">
                        <strong>24/7</strong>
                        <span>support for booking changes</span>
                    </div>
                    <div class="hero__metric">
                        <strong>1 place</strong>
                        <span>for flights, hotels, and trip notes</span>
                    </div>
                </div>

                <div class="hero__route-strip">
                    <span class="hero__route-pill">MSP to ADD</span>
                    <span class="hero__route-pill">ORD to DXB</span>
                    <span class="hero__route-pill">IAH to LOS</span>
                </div>
            </div>

            <div class="hero__search">
                <?php echo walabu_travel_render_flight_search('hero'); ?>
            </div>
        </div>
    </section>

    <section class="section section--tight" id="trips">
        <div class="walabu-container">
            <div class="section__header">
                <div>
                    <span class="section__eyebrow">Travel. Explore. Connect.</span>
                    <h2 class="section__title">Previous searches</h2>
                </div>
                <p class="section__text">
                    Keep your most relevant routes close by and return to them when prices improve or travel dates shift.
                </p>
            </div>

            <div class="card-grid">
                <?php foreach ($recent_searches as $search) : ?>
                    <article class="trip-card">
                        <div class="trip-card__icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                <path d="M3 13.5h7.4l8.4 5.1c.7.4 1.6-.2 1.4-1l-1.3-4.1 3.2-2.5c.6-.5.4-1.4-.4-1.6l-4-.8-3.5-5.5c-.4-.6-1.4-.5-1.7.2L10.3 9H3c-.8 0-1.5.7-1.5 1.5v1.5c0 .8.7 1.5 1.5 1.5Z" fill="currentColor"/>
                            </svg>
                        </div>
                        <h3 class="trip-card__route"><?php echo esc_html($search['route']); ?></h3>
                        <p class="trip-card__date"><?php echo esc_html($search['date']); ?></p>
                        <p class="trip-card__meta"><?php echo esc_html($search['meta']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="deals">
        <div class="walabu-container spotlight-grid">
            <article class="spotlight-card spotlight-card--feature">
                <span class="spotlight-card__label">Walabu bundles</span>
                <h2 class="spotlight-card__title">Bundle long-haul trips and save more on the expensive legs.</h2>
                <p class="spotlight-card__text">
                    Pair airfare with hotel stays when you are traveling across continents or planning family visits with flexible return dates.
                </p>
                <a class="spotlight-card__cta" href="#support">Build a trip plan</a>
            </article>

            <article class="spotlight-card spotlight-card--warm">
                <span class="spotlight-card__label">Best use case</span>
                <h2 class="spotlight-card__title">Designed for diaspora travel, reunions, and complex itineraries.</h2>
                <p class="spotlight-card__text">
                    The layout is ready for real search APIs, fare calendars, and managed trip notes when you are ready to connect the backend.
                </p>
                <a class="spotlight-card__cta" href="#support">See how it works</a>
            </article>
        </div>
    </section>

    <section class="section" id="support">
        <div class="walabu-container">
            <div class="section__header">
                <div>
                    <span class="section__eyebrow">Why Walabu</span>
                    <h2 class="section__title">Built for travelers who need more than a basic booking form.</h2>
                </div>
                <p class="section__text">
                    The theme focuses on trust, clarity, and easy repeat search behavior instead of throwing raw form fields on a blank page.
                </p>
            </div>

            <div class="value-grid">
                <article class="value-card">
                    <div class="value-card__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3 class="value-card__title">Clear booking confidence</h3>
                    <p class="value-card__text">Surface flexible fares, future-date validation, and route details in a UI that feels trustworthy from the first interaction.</p>
                </article>

                <article class="value-card">
                    <div class="value-card__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M7 4h10v4h3v12H4V8h3V4Zm2 4h6V6H9v2Zm-3 4v6h12v-6H6Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3 class="value-card__title">Bundle-ready UI</h3>
                    <p class="value-card__text">The search widget already accounts for hotel bundling, trip type switching, passenger counts, and cabin preferences.</p>
                </article>

                <article class="value-card">
                    <div class="value-card__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 4a8 8 0 1 0 8 8h-3l4 4 4-4h-3a10 10 0 1 1-2.9-7.1L17.7 7A7.96 7.96 0 0 0 12 4Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3 class="value-card__title">Ready for real API flows</h3>
                    <p class="value-card__text">This structure can be wired into Duffel offers, saved searches, user trips, and payment flows without redesigning the front end.</p>
                </article>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
