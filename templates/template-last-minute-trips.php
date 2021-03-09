<?php
/**
 * Template Name: Last Minute Reizen
 */

use lib\controllers\Template;

get_template_part('templates/page-header');
?>
<div class="page-wrapper">
    <?php get_template_part('templates/modules/page-content'); ?>
    <section class="trip-blocks">
        <div class="title-wrapper padding-bottom">
            <h2 class="page-title-border"></h2>
        </div>
        <div class="clearfix"></div>
        <div class="container">
            <?php
            $templates = Template::get_last_minutes();
            if (!empty($templates)):
                global $post;
                foreach ($templates as $post):
                    setup_postdata($post);
                    get_template_part('templates/content-product');
                endforeach;
                wp_reset_postdata();
            else: ?>
                <div class="no-results-found no-results-found--no-padding">
                    <h2>Helaas zijn er momenteel geen last minute reizen beschikbaar.</h2>
                </div>
            <?php endif; ?>
            <?php $allTripsPage = get_page_by_path('groepsreizen/alle-reizen');
            if (!empty($allTripsPage)): ?>
                <div class="button-wrapper">
                    <a href="<?= get_the_permalink($allTripsPage); ?>"
                       class="btn btn--pink btn--padding btn--arrow-white">Bekijk alle reizen</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
