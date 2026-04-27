<?php
if (!defined('ABSPATH')) {
    exit;
}

$terms_file = get_template_directory() . '/assets/terms-and-conditions.txt';
$terms_content = file_exists($terms_file) ? file_get_contents($terms_file) : '';

$terms_lines = preg_split('/\R/', (string) $terms_content);
$terms_sections = array();
$current_section = null;
$has_started = false;

foreach ($terms_lines as $line) {
    $trimmed = trim($line);

    if (!$has_started) {
        if ($trimmed !== 'INTRODUCTION') {
            continue;
        }

        $has_started = true;
    }

    if ($trimmed === '') {
        if ($current_section !== null) {
            $terms_sections[$current_section]['blocks'][] = '';
        }

        continue;
    }

    if ($trimmed === 'INTRODUCTION' || preg_match('/^\d+\.\s+/', $trimmed)) {
        $section_id = 'section-' . sanitize_title($trimmed);

        $current_section = $section_id;
        $terms_sections[$section_id] = array(
            'title'  => $trimmed,
            'blocks' => array(),
        );

        continue;
    }

    if ($current_section !== null) {
        $terms_sections[$current_section]['blocks'][] = $trimmed;
    }
}

get_header();
?>
<main class="content-shell">
    <div class="walabu-container">
        <article id="legal-page-top" class="content-card legal-page">
            <p class="legal-page__eyebrow">Terms & Conditions</p>
            <h1 class="entry-title">Terms & Conditions</h1>
            <p class="legal-page__lead">
                Review the terms that govern use of the website, bookings, payments, schedule changes,
                refunds, travel provider policies, and related services.
            </p>

            <?php if (!empty($terms_sections)) : ?>
                <nav class="legal-page__toc" aria-label="Terms and conditions contents">
                    <h2 class="legal-page__toc-title">Contents</h2>
                    <div class="legal-page__toc-grid">
                        <?php foreach ($terms_sections as $section_id => $section) : ?>
                            <a href="#<?php echo esc_attr($section_id); ?>"><?php echo esc_html($section['title']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </nav>

                <div class="legal-page__content legal-page__document">
                    <?php foreach ($terms_sections as $section_id => $section) : ?>
                        <section id="<?php echo esc_attr($section_id); ?>" class="legal-page__section">
                            <h2 class="legal-page__section-title"><?php echo esc_html($section['title']); ?></h2>

                            <?php foreach ($section['blocks'] as $block) : ?>
                                <?php if ($block === '') : ?>
                                    <div class="legal-page__spacer" aria-hidden="true"></div>
                                <?php else : ?>
                                    <p><?php echo wp_kses_post(make_clickable(esc_html($block))); ?></p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <pre class="legal-page__content legal-page__document"><?php echo esc_html($terms_content); ?></pre>
            <?php endif; ?>

            <a class="legal-page__back-to-top" href="#legal-page-top" aria-label="Back to top">
                <span aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 15V5M10 5 5.5 9.5M10 5l4.5 4.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </a>
        </article>
    </div>
</main>
<?php get_footer(); ?>
