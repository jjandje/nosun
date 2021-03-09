<?php
/**
 * Template Name: Groepsreizen
 */

use lib\controllers\Template;

get_template_part( 'templates/page-header' ); ?>
<div class="page-wrapper">
	<?php get_template_part( 'templates/modules/page-content' ); ?>
	<section class="trip-blocks">
		<div class="title-wrapper padding-bottom">
			<h2 class="page-title-border"></h2>
		</div>
		<div class="clearfix"></div>
		<div class="container">
            <?php
            $linkedTerm = get_field('trip_group_categorie');
            $templatePosts = [];
            if (!empty($linkedTerm)):
                $templatePosts = Template::get_by_terms('trip-type', [$linkedTerm]);
                if (empty($templatePosts)):
                    $templatePosts = Template::get_alternatives($linkedTerm);
                    if (empty($templatePosts)): ?>
                        <div class="no-results-found no-results-found--no-padding">
                            <h2>Helaas zijn er momenteel geen reizen beschikbaar voor <?= get_the_title(); ?>.</h2>
                        </div>
                    <?php else: ?>
                        <div class="no-results-found no-results-found--no-padding">
                            <h2>Helaas zijn er momenteel geen reizen beschikbaar voor <?= get_the_title(); ?>.</h2>
                            <h2>Hier zijn wat leuke alternatieven:</h2>
                        </div>
                    <?php endif; ?>
            <?php endif;
            endif;

            if (!empty($templatePosts)):
                foreach ($templatePosts as $post):
                    setup_postdata($post);
                    get_template_part('templates/content-product');
                    wp_reset_postdata();
                endforeach;
            endif;
            $allTripsPage = get_page_by_path('groepsreizen/alle-reizen');
            if (!empty($allTripsPage)): ?>
                <div class="button-wrapper">
                    <a href="<?= get_the_permalink($allTripsPage); ?>" class="btn btn--pink btn--padding btn--arrow-white">Bekijk alle reizen</a>
                </div>
            <?php endif; ?>
		</div>
	</section>
</div>
