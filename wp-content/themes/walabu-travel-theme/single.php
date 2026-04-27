<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="content-shell">
    <div class="walabu-container">
        <div class="content-card">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class(); ?>>
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </div>
</main>
<?php get_footer(); ?>
