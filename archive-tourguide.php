<?php use Roots\Sage\Helpers;

get_template_part('templates/page-header-regular'); ?>
<section class="tourguides-wrapper">
    <div class="container">
        <?php $args = array(
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_type' => 'tourguide',
            'post_status' => 'publish',
        );
        $posts_array = get_posts($args);

        foreach ($posts_array as $post):
            setup_postdata($post); ?>
            <div class="tourguides-wrapper__block">
                <div class="inner">
                    <?php $tourguide_images = get_field('tourguide_images');
                    if (!empty($tourguide_images)): ?>
                    <div class="tourguides-wrapper__image">
                        <img src="<?= wp_get_attachment_image_url($tourguide_images[0]['image'], 'tourguides-thumb'); ?>"
                             alt="<?= get_field('tourguide_nickname'); ?> profielfoto">
                    </div>
                    <?php endif; ?>
                    <span class="tourguides-wrapper__countries"><?= Helpers::custom_excerpt(get_the_ID(), 100, true, 'tourguide_nosun'); ?></span>
                    <a href="<?= get_the_permalink(); ?>" class="btn btn--yellow"><?= get_field('tourguide_nickname'); ?></a>
                </div>
            </div>
        <?php endforeach;
        wp_reset_postdata(); ?>
    </div>
</section>


