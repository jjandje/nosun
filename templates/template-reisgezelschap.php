<?php
/**
 * Template Name: Reisgezelschap
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
            <?php $termId = get_field('age_group_category');
            $templates = Template::get_by_terms('age-group', [$termId]);
            global $post;
            if (!empty($templates)):
                foreach ($templates as $post):
                    get_template_part( 'templates/content-product' );
                endforeach;
            else: ?>
                <div class="no-results-found no-results-found--no-padding">
                    <h2>Helaas zijn er momenteel geen reizen beschikbaar voor <?= get_the_title(); ?>.</h2>
                </div>
            <?php endif;
            $allTripsPage = get_page_by_path('groepsreizen/alle-reizen');
            if (!empty($allTripsPage)): ?>
                <div class="button-wrapper">
                    <a href="<?= get_the_permalink($allTripsPage); ?>" class="btn btn--pink btn--padding btn--arrow-white">Bekijk alle reizen</a>
                </div>
            <?php endif; ?>
		</div>
	</section>
</div>