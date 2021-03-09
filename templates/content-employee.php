<div class="tourguides-wrapper__block">
	<div class="inner">
		<?php $tourguide_image = get_field( 'employee_image' );

        use Roots\Sage\Helpers; ?>
		<div class="tourguides-wrapper__image">
			<img src="<?php echo wp_get_attachment_image_url( $tourguide_image, 'tourguides-thumb' ); ?>" alt="">
		</div>
		<span class="tourguide-wrapper__slogan" style="font-size: 18px;"><?= get_the_title(); ?></span>
		<span class="tourguides-wrapper__countries"><?= Helpers::custom_excerpt(false, 100, get_field('employee_slogan'));?></span>
		<a href="<?= get_the_permalink(); ?>" class="btn btn--yellow">
			Meer over mij
		</a>
	</div>
</div>

