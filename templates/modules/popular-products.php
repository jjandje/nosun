<?php
$popularTemplates = get_field('option_popular', 'option');
global $post;
if (!empty($popularTemplates)): ?>
	<section class="trip-blocks bg-grey slider-popular-products">
		<div class="container">
			<div class="title-wrapper">
				<h3 class="page-title-border">Populaire reizen</h3>
			</div>
			<?php foreach ($popularTemplates as $template): ?>
                <?php                 /** @var array $template */
                $post = get_post($template['template']);
                if (!empty($post)) {
                    setup_postdata($post);
                    get_template_part('templates/content-product');
                    wp_reset_postdata();
                }
                ?>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>