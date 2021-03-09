<?php /** @noinspection SqlNoDataSourceInspection */
/**
 * Template Name: Reiskalender
 */

use lib\controllers\Template;
use Vazquez\NosunAssumaxConnector\Api\Templates as TemplateAPI;

get_template_part( 'templates/page-header-regular' );
$months = Template::get_grouped_by_month();
?>
<div class="page-wrapper">
	<?php get_template_part('templates/modules/page-content');
	if (!empty($months)):
	foreach ($months as $month => $assumaxIds): ?>
		<div class="container">
			<div class="trip-month-wrapper">
				<h2 class="trip-month-title regular-title page-title-border">Groepsreizen <?=  $month; ?></h2>
			</div>
		</div>
		<section class="trip-blocks">
			<div class="container">
				<?php                 global $post;
				foreach ($assumaxIds as $assumaxId) {
                    $post = TemplateAPI::get_by_assumax_id($assumaxId);
                    if (!empty($post)) {
                        setup_postdata($post);
                        get_template_part('templates/content-product');
                    }
                    wp_reset_postdata();
                } ?>
			</div>
		</section>
	<?php endforeach;
	else: ?>
        <div class="container">
            <div class="content-row__inner content-row__inner--left">
                <h2 class="regular-title">Er zijn helaas geen geplande reizen op het moment, kijk snel terug voor veranderingen!</h2>
            </div>
        </div>
    <?php endif; ?>
</div>
