<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="content-shell support-page">
    <div class="walabu-container">
        <section class="support-hero">
            <h1 class="support-hero__title">How can we help?</h1>
        </section>

        <section class="support-trip-card">
            <div class="support-trip-card__copy">
                <h2 class="support-trip-card__title">Looking for your trip details?</h2>
                <p class="support-trip-card__text">To access your upcoming reservation use Find My Trip</p>
            </div>
            <a
                class="support-trip-card__cta"
                href="<?php echo esc_url(walabu_travel_get_my_trips_url()); ?>"
                data-auth-gated-link="my-trips"
                data-auth-redirect="<?php echo esc_attr(walabu_travel_get_my_trips_url()); ?>"
            >Find my trip</a>
        </section>

        <section class="support-section">
            <h2 class="support-section__title">Contact our customer support</h2>

            <div class="support-grid">
                <article class="support-card">
                    <div class="support-card__icon" aria-hidden="true">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                            <rect x="12" y="8" width="28" height="44" rx="6" stroke="currentColor" stroke-width="3"/>
                            <path d="M20 18h12M26 42h.1" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M45 24c4.4 0 8 3.6 8 8v10a6 6 0 0 1-6 6H35l-10 8v-8h-1a8 8 0 0 1-8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="m39 35 4 4 8-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="support-card__content">
                        <h3 class="support-card__title">Messaging</h3>
                        <a class="support-card__link" href="#messaging">Message us 24/7</a>
                    </div>
                </article>

                <article class="support-card">
                    <div class="support-card__icon" aria-hidden="true">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                            <rect x="20" y="6" width="24" height="52" rx="6" stroke="currentColor" stroke-width="3"/>
                            <path d="M28 14h8M32 50h.1" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            <path d="M24 24h16v14H24z" stroke="currentColor" stroke-width="3"/>
                            <path d="M26.5 34v-7h2.8a2 2 0 0 1 0 4h-2.8m7.5 3v-7l4.5 7v-7m4.5 7v-7h4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="support-card__content">
                        <h3 class="support-card__title">Text</h3>
                        <a class="support-card__link" href="sms:7028353125">Click here to text us</a>
                    </div>
                </article>

                <article class="support-card">
                    <div class="support-card__icon support-card__icon--whatsapp" aria-hidden="true">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                            <circle cx="32" cy="32" r="24" fill="currentColor"/>
                            <path d="M21 50l2.7-8.1A18 18 0 1 1 32 50a18 18 0 0 1-8.8-2.3L21 50Z" fill="#fff"/>
                            <path d="M38.8 35.9c-.4-.2-2.4-1.2-2.8-1.3-.4-.2-.7-.2-1 .2s-1.1 1.3-1.4 1.6c-.2.3-.5.3-.9.1-2.6-1.3-4.2-2.3-5.9-5.2-.4-.7.4-.7 1.1-2 .1-.2.2-.5.1-.7-.1-.2-1-2.3-1.4-3.2-.3-.8-.7-.7-1-.7h-.8c-.3 0-.7.1-1.1.5-.4.4-1.4 1.4-1.4 3.4s1.5 3.9 1.7 4.2c.2.3 3 4.7 7.3 6.5 2.7 1.2 3.8 1.3 5.2 1.1.9-.1 2.4-1 2.7-2s.3-1.8.2-2c-.1-.2-.4-.3-.8-.5Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="support-card__content">
                        <h3 class="support-card__title">WhatsApp</h3>
                        <a class="support-card__link" href="https://wa.me/17028353125">Message us at 702-835-3125</a>
                    </div>
                </article>

                <article class="support-card">
                    <div class="support-card__icon" aria-hidden="true">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                            <path d="M21.4 16.7a5 5 0 0 1 5.5 1l4.8 4.8a5 5 0 0 1 0 7.1l-2.4 2.4a3 3 0 0 0-.4 3.7 35.5 35.5 0 0 0 8.5 8.5 3 3 0 0 0 3.7-.4l2.4-2.4a5 5 0 0 1 7.1 0l4.8 4.8a5 5 0 0 1 1 5.5 9.7 9.7 0 0 1-9.4 6.3c-8.8-.4-17.2-4.3-24-11.1C16.8 40.6 13 32.2 12.6 23.4a9.7 9.7 0 0 1 8.8-6.7Z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M42 18a12 12 0 0 1 12 12M42 10a20 20 0 0 1 20 20" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="support-card__content">
                        <h3 class="support-card__title">Call</h3>
                        <a class="support-card__link" href="tel:7028353125">702-835-3125</a>
                    </div>
                </article>
            </div>
        </section>
    </div>
</main>
<?php get_footer(); ?>
