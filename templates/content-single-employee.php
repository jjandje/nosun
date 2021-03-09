<?php
if ( function_exists( 'yoast_breadcrumb' ) ) : ?>
	<div class="container">
		<div class="breadcrumb-wrapper">
			<?php yoast_breadcrumb( '
<p id="breadcrumbs">', '</p>
' ); ?>
		</div>
	</div>
<?php endif;

use Roots\Sage\Helpers; ?>
<div class="tourguide-wrapper">

	<h1 class="tourguide-wrapper__title page-title-border"><?= get_the_title(); ?></h1>

	<div class="tourguide-wrapper__about">
		<div class="container">
			<?php $employee_image = get_field( 'employee_image' ); ?>
			<?php if ( $employee_image ) { ?>
				<img src="<?php echo wp_get_attachment_image_url( $employee_image, 'tourguide-thumb' ); ?>" class="tourguide-wrapper__image" alt="<?= get_the_title(); ?>">
			<?php } ?>
			<div class="clearfix"></div>
			<div class="tourguide-wrapper__container">
				<h2 class="tourguide-wrapper__slogan">
					<?php the_field( 'employee_slogan' ); ?>
				</h2>
				<?php the_field( 'employee_text' ); ?>
			</div>
			<ul class="tourguide-wrapper__column">
				<?php if ( get_field( 'employee_function' ) ): ?>
					<li>
						<span class="tourguide-wrapper__property">Functie</span><span class="tourguide-wrapper__value"><?php the_field( 'employee_function' ); ?></span>
					</li>
				<?php endif; ?>
				<?php if ( get_field( 'employee_destinations' ) ): ?>
					<li>
						<span class="tourguide-wrapper__property">Bestemmingen</span><span class="tourguide-wrapper__value"><?php the_field( 'employee_destinations' ); ?></span>
					</li>
				<?php endif; ?>
			</ul>
			<ul class="tourguide-wrapper__column">
				<?php if ( get_field( 'employee_nosun_since' ) ): ?>
					<li>
						<span class="tourguide-wrapper__property">Bij noSun sinds</span><span class="tourguide-wrapper__value"><?php the_field( 'employee_nosun_since' ); ?></span>
					</li>
				<?php endif; ?>
				<?php if ( get_field( 'employee_hobbies' ) ): ?>
					<li>
						<span class="tourguide-wrapper__property">Hobby's</span><span class="tourguide-wrapper__value"><?php the_field( 'employee_hobbies' ); ?></span>
					</li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

	<?php
	$currentID = get_the_ID();

	$the_query = new WP_Query( array (
		'post_type'    => 'employee',
		'showposts'    => '10',
		'post__not_in' => array ( $currentID ),
		'orderby'      => 'rand'
	) );

	if ( $the_query->have_posts() ) : ?>
		<section class="employee-slider">
			<div class="container">

				<?php while ( $the_query->have_posts() ) :
					$the_query->the_post(); ?>
					<div class="slide-item">
						<div class="inner">
							<img src="<?php echo wp_get_attachment_image_url(  get_field( 'employee_image' ), 'tourguide-thumb' ); ?>" alt="<?= get_the_title(); ?>" class="slide-item__image">
							<?php /**<span class="slide-item__title"><?= get_the_title(); ?></span>**/ ?>
							<p class="slide-item__text"><?= Helpers::custom_excerpt(false, 70, get_field('employee_slogan'));?></p>
							<a href="<?= get_the_permalink(); ?>" class="btn btn--blue btn--padding btn--arrow-white"><?= get_the_title(); ?></a>
						</div>
					</div>
				<?php endwhile;
				wp_reset_postdata(); ?>
			</div>
		</section>

	<?php endif; ?>
</div>

