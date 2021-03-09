<?php get_template_part('templates/page-header-regular'); ?>
<section class="tourguides-wrapper">
	<?php if ( have_posts() ) : ?>
		<div class="container">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'templates/content', get_post_type() != 'post' ? get_post_type() : get_post_format() ); ?>
			<?php endwhile; ?>
		</div>
	<?php endif; ?>
</section>