<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="content-shell">
    <div class="walabu-container">
        <div class="content-card">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <article <?php post_class(); ?>>
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <h1 class="entry-title"><?php esc_html_e('Nothing found', 'walabu-travel'); ?></h1>
                <p><?php esc_html_e('Content will appear here once pages or posts are published.', 'walabu-travel'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php get_footer(); ?>
