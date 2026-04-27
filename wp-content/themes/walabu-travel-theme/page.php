<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$booking_step = '';

if (function_exists('walabu_duffel_booking_get_booking_step')) {
    $booking_step = walabu_duffel_booking_get_booking_step();
}

$is_booking_detail_page = in_array($booking_step, array('details', 'checkout'), true);
?>
<main class="<?php echo $is_booking_detail_page ? 'booking-shell' : 'content-shell'; ?>">
    <?php if ($is_booking_detail_page) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <div class="booking-shell__content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; ?>
    <?php else : ?>
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
    <?php endif; ?>
</main>
<?php get_footer(); ?>
